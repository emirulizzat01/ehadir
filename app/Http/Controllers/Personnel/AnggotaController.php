<?php

namespace App\Http\Controllers\Personnel;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Bahagian;
use App\Models\Unit;
use App\Services\AuditLoggerService;
use App\Services\OneStack\OneStackSyncService;
use App\Services\PenempatanService;
use App\Services\ScopeService;
use App\Traits\HasDataTableQueries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnggotaController extends Controller
{
    use HasDataTableQueries;

    public function __construct(
        protected ScopeService $scopeService,
        protected PenempatanService $penempatanService
    ) {}

    public function index(Request $request): View
    {
        $this->authorizePermission('anggota.view');
        $user = Auth::user();
        $today = now()->toDateString();

        $query = Anggota::query()
            ->with(['penempatan' => function ($q) {
                $q->with(['bahagian', 'unit'])->orderBy('tarikh_mula', 'desc');
            }]);

        // Apply Scope (Invariant 3)
        $this->scopeService->scopeAnggota($query, $user, $today);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_penuh', 'like', "%{$search}%")
                    ->orWhere('no_pekerja', 'like', "%{$search}%")
                    ->orWhere('jawatan', 'like', "%{$search}%")
                    ->orWhereHas('penempatan.bahagian', function ($bq) use ($search) {
                        $bq->where('nama', 'like', "%{$search}%")->orWhere('kod', 'like', "%{$search}%");
                    })
                    ->orWhereHas('penempatan.unit', function ($uq) use ($search) {
                        $uq->where('nama', 'like', "%{$search}%")->orWhere('kod', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('bahagian_id')) {
            $bahagianId = $request->input('bahagian_id');
            $query->whereHas('penempatan', function ($q) use ($bahagianId, $today) {
                $q->where('bahagian_id', $bahagianId)
                    ->where('tarikh_mula', '<=', $today)
                    ->where(function ($sq) use ($today) {
                        $sq->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $today);
                    });
            });
        } elseif ($request->filled('unit_id')) {
            $unitId = $request->input('unit_id');
            $query->whereHas('penempatan', function ($q) use ($unitId, $today) {
                $q->where('unit_id', $unitId)
                    ->where('tarikh_mula', '<=', $today)
                    ->where(function ($sq) use ($today) {
                        $sq->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $today);
                    });
            });
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('aktif', $request->boolean('status'));
        }

        $sortableColumns = [
            'no_pekerja' => 'no_pekerja',
            'nama' => 'nama_penuh',
            'jawatan' => 'jawatan',
            'susunan' => 'susunan',
            'aktif' => 'aktif',
        ];

        if (! $request->has('sort')) {
            $query->orderByDesc('aktif')->orderBy('susunan')->orderBy('nama_penuh');
            $perPage = (int) $request->input('per_page', 15);
            $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 15;
            $anggotaList = $query->paginate($perPage)->withQueryString();
        } else {
            $anggotaList = $this->applyDataTable(
                $query,
                $request,
                $sortableColumns,
                defaultSort: 'nama',
                defaultDirection: 'asc',
                defaultPerPage: 15
            );
        }

        $bahagianList = Bahagian::where('aktif', true)->orderBy('nama')->get();
        $unitList = Unit::where('aktif', true)->orderBy('nama')->get();
        $totalOneStackSynced = Anggota::whereNotNull('onestack_id')->count();
        $latestSync = Anggota::max('last_synced_at');

        return view('personnel.anggota.index', compact(
            'anggotaList',
            'bahagianList',
            'unitList',
            'totalOneStackSynced',
            'latestSync'
        ));
    }

    public function syncOneStack(OneStackSyncService $syncService): RedirectResponse
    {
        $this->authorizePermission('anggota.manage');

        try {
            $stats = $syncService->syncAll(['actor_id' => Auth::id()]);

            if (isset($stats['status']) && $stats['status'] === false) {
                return back()->with('error', $stats['message'] ?? 'Ralat semasa penyegerakan OneStack.');
            }

            $msg = "Penyegerakan OneStack berjaya: {$stats['synced']} anggota diselaraskan ({$stats['created']} rekod baharu, {$stats['updated']} dikemaskini).";

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', "Gagal menghubungi OneStack API: {$e->getMessage()}");
        }
    }

    public function refreshOneStack(Anggota $anggota, OneStackSyncService $syncService): RedirectResponse
    {
        $this->authorizePermission('anggota.view');

        if (empty($anggota->no_pekerja)) {
            return back()->with('error', 'Anggota ini tidak mempunyai No. Pekerja untuk disegerakkan.');
        }

        try {
            $stats = $syncService->syncUserById($anggota->no_pekerja, ['actor_id' => Auth::id()]);

            if (isset($stats['status']) && $stats['status'] === false) {
                return back()->with('error', $stats['message'] ?? 'Rekod tidak dijumpai di OneStack.');
            }

            return back()->with('success', "Maklumat '{$anggota->nama_penuh}' berjaya disegerakkan daripada OneStack.");
        } catch (\Throwable $e) {
            return back()->with('error', "Ralat semasa menyegerakkan rekod dari OneStack: {$e->getMessage()}");
        }
    }

    public function create(): View
    {
        $this->authorizePermission('anggota.manage');

        $bahagianList = Bahagian::where('aktif', true)->orderBy('nama')->get();
        $unitList = Unit::where('aktif', true)->orderBy('nama')->get();

        return view('personnel.anggota.create', compact('bahagianList', 'unitList'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('anggota.manage');
        $user = Auth::user();

        $validated = $request->validate([
            'no_pekerja' => ['nullable', 'string', 'max:60', 'unique:anggota,no_pekerja'],
            'nama_penuh' => ['required', 'string', 'max:200'],
            'gelaran' => ['nullable', 'string', 'max:40'],
            'jawatan' => ['nullable', 'string', 'max:150'],
            'gred' => ['nullable', 'string', 'max:20'],
            'emel' => ['nullable', 'email', 'max:200'],
            'no_telefon' => ['nullable', 'string', 'max:30'],
            'susunan' => ['nullable', 'integer', 'min:0'],
            'aktif' => ['boolean'],

            // Initial placement (optional on creation)
            'penempatan_type' => ['nullable', 'in:bahagian,unit'],
            'bahagian_id' => ['nullable', 'required_if:penempatan_type,bahagian', 'exists:bahagian,id'],
            'unit_id' => ['nullable', 'required_if:penempatan_type,unit', 'exists:unit,id'],
            'tarikh_mula' => ['nullable', 'required_with:penempatan_type', 'date'],
        ], [
            'nama_penuh.required' => 'Nama penuh anggota wajib diisi.',
            'no_pekerja.unique' => 'No. pekerja ini telah didaftarkan.',
            'bahagian_id.required_if' => 'Sila pilih bahagian untuk penempatan.',
            'unit_id.required_if' => 'Sila pilih unit untuk penempatan.',
            'tarikh_mula.required_with' => 'Tarikh mula penempatan wajib diisi.',
        ]);

        $anggota = DB::transaction(function () use ($validated, $user) {
            $anggotaData = [
                'no_pekerja' => $validated['no_pekerja'] ?? null,
                'nama_penuh' => $validated['nama_penuh'],
                'gelaran' => $validated['gelaran'] ?? null,
                'jawatan' => $validated['jawatan'] ?? null,
                'gred' => $validated['gred'] ?? null,
                'emel' => $validated['emel'] ?? null,
                'no_telefon' => $validated['no_telefon'] ?? null,
                'susunan' => $validated['susunan'] ?? 0,
                'aktif' => $validated['aktif'] ?? true,
            ];

            $item = Anggota::create($anggotaData);

            AuditLoggerService::log(
                tindakan: 'CREATE',
                namaJadual: 'anggota',
                recordId: $item->id,
                dataLama: null,
                dataBaru: $item->toArray()
            );

            // Create initial placement if provided
            if (! empty($validated['penempatan_type'])) {
                $placementData = [
                    'anggota_id' => $item->id,
                    'bahagian_id' => $validated['penempatan_type'] === 'bahagian' ? $validated['bahagian_id'] : null,
                    'unit_id' => $validated['penempatan_type'] === 'unit' ? $validated['unit_id'] : null,
                    'tarikh_mula' => $validated['tarikh_mula'],
                    'tarikh_tamat' => null,
                ];

                $this->penempatanService->createPenempatan($placementData, $user->id);
            }

            return $item;
        });

        return redirect()->route('personnel.anggota.show', $anggota)
            ->with('success', "Anggota '{$anggota->nama_penuh}' berjaya didaftarkan.");
    }

    public function show(Anggota $anggota): View
    {
        $this->authorizePermission('anggota.view');

        $anggota->load([
            'user',
            'penempatan' => function ($q) {
                $q->with(['bahagian', 'unit'])->orderBy('tarikh_mula', 'desc');
            },
            'kehadiran' => function ($q) {
                $q->with(['status', 'penempatan'])->orderBy('tarikh', 'desc')->take(10);
            },
        ]);

        $bahagianList = Bahagian::where('aktif', true)->orderBy('nama')->get();
        $unitList = Unit::where('aktif', true)->orderBy('nama')->get();
        $currentPlacement = $anggota->penempatanSemasa();

        return view('personnel.anggota.show', compact('anggota', 'bahagianList', 'unitList', 'currentPlacement'));
    }

    public function edit(Anggota $anggota): View
    {
        $this->authorizePermission('anggota.manage');

        return view('personnel.anggota.edit', compact('anggota'));
    }

    public function update(Request $request, Anggota $anggota): RedirectResponse
    {
        $this->authorizePermission('anggota.manage');

        $validated = $request->validate([
            'no_pekerja' => ['nullable', 'string', 'max:60', 'unique:anggota,no_pekerja,'.$anggota->id],
            'nama_penuh' => ['required', 'string', 'max:200'],
            'gelaran' => ['nullable', 'string', 'max:40'],
            'jawatan' => ['nullable', 'string', 'max:150'],
            'gred' => ['nullable', 'string', 'max:20'],
            'emel' => ['nullable', 'email', 'max:200'],
            'no_telefon' => ['nullable', 'string', 'max:30'],
            'susunan' => ['nullable', 'integer', 'min:0'],
            'aktif' => ['boolean'],
        ]);

        $validated['aktif'] = $request->boolean('aktif', true);
        $validated['susunan'] = $validated['susunan'] ?? 0;

        DB::transaction(function () use ($anggota, $validated) {
            $oldData = $anggota->toArray();
            $anggota->update($validated);

            AuditLoggerService::log(
                tindakan: 'UPDATE',
                namaJadual: 'anggota',
                recordId: $anggota->id,
                dataLama: $oldData,
                dataBaru: $anggota->fresh()->toArray()
            );
        });

        return redirect()->route('personnel.anggota.show', $anggota)
            ->with('success', 'Maklumat anggota berjaya dikemaskini.');
    }

    public function destroy(Anggota $anggota): RedirectResponse
    {
        $this->authorizePermission('anggota.manage');

        if ($anggota->kehadiran()->exists()) {
            return back()->with('error', 'Anggota tidak boleh dipadam kerana mempunyai sejarah rekod kehadiran.');
        }

        DB::transaction(function () use ($anggota) {
            $oldData = $anggota->toArray();
            // Delete placements first if any
            $anggota->penempatan()->delete();
            $anggota->delete();

            AuditLoggerService::log(
                tindakan: 'DELETE',
                namaJadual: 'anggota',
                recordId: $anggota->id,
                dataLama: $oldData,
                dataBaru: null
            );
        });

        return redirect()->route('personnel.anggota.index')
            ->with('success', 'Anggota berjaya dipadam.');
    }

    protected function authorizePermission(string $perm): void
    {
        if (! Auth::user()?->hasPermission($perm)) {
            abort(403, 'Anda tidak mempunyai kebenaran untuk mengakses fungsi ini.');
        }
    }
}
