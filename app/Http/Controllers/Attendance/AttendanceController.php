<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Bahagian;
use App\Models\RekodKehadiran;
use App\Models\StatusKehadiran;
use App\Models\Unit;
use App\Services\AttendanceService;
use App\Services\ScopeService;
use App\Services\WhatsAppSummaryService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService,
        protected ScopeService $scopeService
    ) {}

    public function index(Request $request): View
    {
        $this->authorizePermission('attendance.view');
        $user = Auth::user();
        $date = $request->input('tarikh', Carbon::today()->toDateString());
        $today = Carbon::today()->toDateString();

        // Self-healing JIT default priming: if viewing today on a working day, ensure defaults are primed
        if ($date === $today && ! Carbon::today()->isWeekend()) {
            $this->attendanceService->initializeDailyDefaults($today);
        }

        // 1. Load active statuses
        $statuses = StatusKehadiran::where('aktif', true)->orderBy('susunan')->get();

        // 2. Query scoped Anggota active on this date
        $anggotaQuery = Anggota::query()
            ->where('aktif', true)
            ->with(['penempatan' => function ($q) use ($date) {
                $q->with(['bahagian', 'unit'])
                    ->where('tarikh_mula', '<=', $date)
                    ->where(function ($sq) use ($date) {
                        $sq->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $date);
                    });
            }]);

        // Filter by user's assigned scope (Invariant 3)
        $this->scopeService->scopeAnggota($anggotaQuery, $user, $date);

        // Optional filter by Bahagian / Unit dropdown
        if ($bahagianId = $request->input('bahagian_id')) {
            $anggotaQuery->whereHas('penempatan', function ($q) use ($bahagianId, $date) {
                $q->where('bahagian_id', $bahagianId)
                    ->where('tarikh_mula', '<=', $date)
                    ->where(function ($sq) use ($date) {
                        $sq->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $date);
                    });
            });
        } elseif ($unitId = $request->input('unit_id')) {
            $anggotaQuery->whereHas('penempatan', function ($q) use ($unitId, $date) {
                $q->where('unit_id', $unitId)
                    ->where('tarikh_mula', '<=', $date)
                    ->where(function ($sq) use ($date) {
                        $sq->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $date);
                    });
            });
        }

        $anggotaList = $anggotaQuery->orderBy('susunan')->orderBy('nama_penuh')->paginate(25)->withQueryString();
        $anggotaIds = $anggotaList->getCollection()->pluck('id')->all();

        // 3. Load attendance records for this date
        $records = RekodKehadiran::with(['status', 'penempatan.bahagian', 'penempatan.unit'])
            ->whereDate('tarikh', $date)
            ->whereIn('anggota_id', $anggotaIds)
            ->get()
            ->keyBy('anggota_id');

        // Filter options
        $bahagianOptions = Bahagian::where('aktif', true)->orderBy('nama')->get();
        $unitOptions = Unit::where('aktif', true)->orderBy('nama')->get();

        return view('attendance.index', compact(
            'anggotaList',
            'records',
            'statuses',
            'date',
            'bahagianOptions',
            'unitOptions'
        ));
    }

    public function create(Request $request): View
    {
        $this->authorizePermission('attendance.manage');
        $user = Auth::user();
        $today = now()->toDateString();

        $statuses = StatusKehadiran::where('aktif', true)->orderBy('susunan')->get();

        $anggotaQuery = Anggota::where('aktif', true);
        $this->scopeService->scopeAnggota($anggotaQuery, $user, $today);
        $anggotaList = $anggotaQuery->orderBy('nama_penuh')->get();

        $selectedAnggotaId = $request->input('anggota_id');
        $selectedDate = $request->input('tarikh', $today);

        return view('attendance.create', compact('statuses', 'anggotaList', 'selectedAnggotaId', 'selectedDate'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('attendance.manage');

        $validated = $request->validate([
            'anggota_id' => ['required', 'exists:anggota,id'],
            'status_id' => ['required', 'exists:status_kehadiran,id'],
            'tarikh' => ['required', 'date'],
            'sepanjang_hari' => ['boolean'],
            'masa_mula' => ['nullable', 'required_if:sepanjang_hari,0'],
            'masa_tamat' => ['nullable', 'required_if:sepanjang_hari,0', 'after:masa_mula'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
        ], [
            'masa_tamat.after' => 'Masa tamat mesti selepas masa mula.',
        ]);

        $validated['sepanjang_hari'] = $request->boolean('sepanjang_hari', true);

        $record = $this->attendanceService->recordAttendance($validated, Auth::user());

        return redirect()->route('attendance.show', $record)
            ->with('success', 'Rekod kehadiran berjaya disimpan.');
    }

    public function show(RekodKehadiran $attendance): View
    {
        $this->authorizePermission('attendance.view');

        $attendance->load(['anggota', 'status', 'penempatan.bahagian', 'penempatan.unit', 'creator', 'updater']);

        return view('attendance.show', compact('attendance'));
    }

    public function edit(RekodKehadiran $attendance): View
    {
        $this->authorizePermission('attendance.manage');

        $attendance->load(['anggota', 'status']);
        $statuses = StatusKehadiran::where('aktif', true)->orderBy('susunan')->get();

        return view('attendance.edit', compact('attendance', 'statuses'));
    }

    public function update(Request $request, RekodKehadiran $attendance): RedirectResponse
    {
        $this->authorizePermission('attendance.manage');

        $validated = $request->validate([
            'status_id' => ['required', 'exists:status_kehadiran,id'],
            'sepanjang_hari' => ['boolean'],
            'masa_mula' => ['nullable', 'required_if:sepanjang_hari,0'],
            'masa_tamat' => ['nullable', 'required_if:sepanjang_hari,0', 'after:masa_mula'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
        ]);

        $validated['anggota_id'] = $attendance->anggota_id;
        $validated['tarikh'] = $attendance->tarikh->toDateString();
        $validated['sepanjang_hari'] = $request->boolean('sepanjang_hari', true);

        $record = $this->attendanceService->recordAttendance($validated, Auth::user());

        return redirect()->route('attendance.show', $record)
            ->with('success', 'Rekod kehadiran berjaya dikemaskini.');
    }

    public function destroy(RekodKehadiran $attendance): RedirectResponse
    {
        $this->authorizePermission('attendance.manage');

        $this->attendanceService->deleteAttendance($attendance, Auth::user());

        return redirect()->route('attendance.index')
            ->with('success', 'Rekod kehadiran berjaya dipadam.');
    }

    public function batch(Request $request): RedirectResponse
    {
        $this->authorizePermission('attendance.manage');

        $validated = $request->validate([
            'status_id' => ['required', 'exists:status_kehadiran,id'],
            'tarikh' => ['required', 'date'],
            'anggota_ids' => ['required', 'array'],
            'anggota_ids.*' => ['exists:anggota,id'],
        ]);

        $count = $this->attendanceService->batchRecordAttendance(
            $validated['anggota_ids'],
            $validated['status_id'],
            $validated['tarikh'],
            Auth::user()
        );

        return back()->with('success', "Sebanyak {$count} rekod kehadiran berjaya dikemas kini.");
    }

    public function whatsappSummary(Request $request, WhatsAppSummaryService $whatsAppService): JsonResponse
    {
        $this->authorizePermission('attendance.view');

        $tarikh = $request->input('tarikh', Carbon::today()->toDateString());
        $bahagianId = $request->filled('bahagian_id') ? (int) $request->input('bahagian_id') : null;
        $unitId = $request->filled('unit_id') ? (int) $request->input('unit_id') : null;
        $format = $request->input('format', 'exceptions');

        $result = $whatsAppService->generateForDate(
            tarikh: $tarikh,
            bahagianId: $bahagianId,
            unitId: $unitId,
            format: $format,
            user: Auth::user()
        );

        return response()->json($result);
    }

    public function primeDefaults(Request $request): RedirectResponse
    {
        $this->authorizePermission('attendance.manage');

        $date = $request->input('tarikh', Carbon::today()->toDateString());
        $force = $request->boolean('force', false);

        if (Carbon::parse($date)->isWeekend() && ! $force) {
            return back()->with('error', 'Tarikh yang dipilih adalah hari hujung minggu. Gunakan parameter force jika perlu.');
        }

        $stats = $this->attendanceService->initializeDailyDefaults($date, Auth::user());

        return back()->with('success', "Penetapan status Pejabat selesai: {$stats['created']} rekod baharu dijana ({$stats['already_recorded']} sedia ada dikekalkan).");
    }

    protected function authorizePermission(string $perm): void
    {
        if (! Auth::user()?->hasPermission($perm)) {
            abort(403, 'Anda tidak mempunyai kebenaran untuk mengakses fungsi ini.');
        }
    }
}
