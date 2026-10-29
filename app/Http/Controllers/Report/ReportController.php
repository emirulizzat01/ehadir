<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Bahagian;
use App\Models\RekodKehadiran;
use App\Models\StatusKehadiran;
use App\Models\Unit;
use App\Services\ScopeService;
use App\Traits\HasDataTableQueries;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use HasDataTableQueries;

    public function __construct(
        protected ScopeService $scopeService
    ) {}

    public function daily(Request $request): View
    {
        $this->authorizePermission('attendance.view');
        $user = Auth::user();
        $date = $request->input('tarikh', Carbon::today()->toDateString());

        $anggotaQuery = Anggota::where('aktif', true);
        $this->scopeService->scopeAnggota($anggotaQuery, $user, $date);

        if ($search = $request->input('search')) {
            $anggotaQuery->where(function ($q) use ($search) {
                $q->where('nama_penuh', 'like', "%{$search}%")
                    ->orWhere('no_pekerja', 'like', "%{$search}%")
                    ->orWhere('jawatan', 'like', "%{$search}%");
            });
        }

        if ($bahagianId = $request->input('bahagian_id')) {
            $anggotaQuery->whereHas('penempatan', function ($q) use ($bahagianId, $date) {
                $q->where('bahagian_id', $bahagianId)->where('tarikh_mula', '<=', $date)
                    ->where(function ($sq) use ($date) {
                        $sq->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $date);
                    });
            });
        } elseif ($unitId = $request->input('unit_id')) {
            $anggotaQuery->whereHas('penempatan', function ($q) use ($unitId, $date) {
                $q->where('unit_id', $unitId)->where('tarikh_mula', '<=', $date)
                    ->where(function ($sq) use ($date) {
                        $sq->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $date);
                    });
            });
        }

        $sortableColumns = [
            'nama' => 'nama_penuh',
            'no_pekerja' => 'no_pekerja',
            'jawatan' => 'jawatan',
            'gred' => 'tahap_hierarki',
            'hierarki' => 'tahap_hierarki',
            'susunan' => 'susunan',
        ];

        if (! $request->has('sort')) {
            $anggotaQuery->orderByDesc('tahap_hierarki')->orderBy('susunan')->orderBy('nama_penuh');
            $perPage = (int) $request->input('per_page', 25);
            $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;
            $anggotaList = $anggotaQuery->paginate($perPage)->withQueryString();
        } else {
            $anggotaList = $this->applyDataTable(
                $anggotaQuery,
                $request,
                $sortableColumns,
                defaultSort: 'nama',
                defaultDirection: 'asc',
                defaultPerPage: 25
            );
        }
        $anggotaIds = $anggotaList->getCollection()->pluck('id')->all();

        $records = RekodKehadiran::with(['status', 'penempatan.bahagian', 'penempatan.unit'])
            ->whereDate('tarikh', $date)
            ->whereIn('anggota_id', $anggotaIds)
            ->get()
            ->keyBy('anggota_id');

        $statuses = StatusKehadiran::where('aktif', true)->orderBy('susunan')->get();
        $bahagianOptions = Bahagian::where('aktif', true)->orderBy('nama')->get();
        $unitOptions = Unit::where('aktif', true)->orderBy('nama')->get();

        return view('reports.daily', compact(
            'anggotaList',
            'records',
            'statuses',
            'date',
            'bahagianOptions',
            'unitOptions'
        ));
    }

    public function monthly(Request $request): View
    {
        $this->authorizePermission('attendance.view');
        $user = Auth::user();

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        $anggotaQuery = Anggota::where('aktif', true);
        $this->scopeService->scopeAnggota($anggotaQuery, $user, $startOfMonth->toDateString());
        $anggotaList = $anggotaQuery->orderByDesc('tahap_hierarki')->orderBy('susunan')->orderBy('nama_penuh')->get();
        $anggotaIds = $anggotaList->pluck('id')->all();
        $mobileAnggota = (clone $anggotaQuery)->reorder()->orderByDesc('tahap_hierarki')->orderBy('susunan')->orderBy('nama_penuh')->paginate(25, ['*'], 'mobile_page')->withQueryString();

        $records = RekodKehadiran::with('status')
            ->whereYear('tarikh', $year)
            ->whereMonth('tarikh', $month)
            ->whereIn('anggota_id', $anggotaIds)
            ->get()
            ->groupBy('anggota_id');

        $statuses = StatusKehadiran::where('aktif', true)->orderBy('susunan')->get();

        return view('reports.monthly', compact(
            'anggotaList',
            'mobileAnggota',
            'records',
            'statuses',
            'year',
            'month',
            'daysInMonth',
            'startOfMonth'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizePermission('reports.export');
        $user = Auth::user();
        $date = $request->input('tarikh', Carbon::today()->toDateString());

        $anggotaQuery = Anggota::where('aktif', true);
        $this->scopeService->scopeAnggota($anggotaQuery, $user, $date);
        $anggotaList = $anggotaQuery->orderByDesc('tahap_hierarki')->orderBy('susunan')->orderBy('nama_penuh')->get();
        $anggotaIds = $anggotaList->pluck('id')->all();

        $records = RekodKehadiran::with(['status', 'penempatan.bahagian', 'penempatan.unit'])
            ->whereDate('tarikh', $date)
            ->whereIn('anggota_id', $anggotaIds)
            ->get()
            ->keyBy('anggota_id');

        $filename = "Laporan_Kehadiran_{$date}.csv";

        return response()->streamDownload(function () use ($anggotaList, $records, $date) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['BIL', 'NO PEKERJA', 'NAMA ANGGOTA', 'JAWATAN', 'PENEMPATAN', 'TARIKH', 'STATUS', 'WAKTU', 'LOKASI / CATATAN']);

            $i = 1;
            foreach ($anggotaList as $a) {
                $rec = $records->get($a->id);
                fputcsv($handle, [
                    $i++,
                    $a->no_pekerja ?? '-',
                    $a->nama_penuh_gelaran,
                    $a->jawatan ?? '-',
                    $rec?->penempatan?->lokasi_penempatan ?? 'Tiada Penempatan',
                    $date,
                    $rec?->status?->nama ?? 'Belum Direkod',
                    $rec ? ($rec->sepanjang_hari ? 'Sepanjang Hari' : "{$rec->masa_mula} - {$rec->masa_tamat}") : '-',
                    $rec?->lokasi ?? $rec?->catatan ?? '-',
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function dailyPrint(Request $request): View
    {
        $this->authorizePermission('attendance.view');
        $user = Auth::user();
        $date = $request->input('tarikh', Carbon::today()->toDateString());

        $anggotaQuery = Anggota::where('aktif', true);
        $this->scopeService->scopeAnggota($anggotaQuery, $user, $date);

        if ($bahagianId = $request->input('bahagian_id')) {
            $anggotaQuery->whereHas('penempatan', function ($q) use ($bahagianId, $date) {
                $q->where('bahagian_id', $bahagianId)->where('tarikh_mula', '<=', $date)
                    ->where(function ($sq) use ($date) {
                        $sq->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $date);
                    });
            });
        } elseif ($unitId = $request->input('unit_id')) {
            $anggotaQuery->whereHas('penempatan', function ($q) use ($unitId, $date) {
                $q->where('unit_id', $unitId)->where('tarikh_mula', '<=', $date)
                    ->where(function ($sq) use ($date) {
                        $sq->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $date);
                    });
            });
        }

        $anggotaList = $anggotaQuery->orderByDesc('tahap_hierarki')->orderBy('susunan')->orderBy('nama_penuh')->get();
        $anggotaIds = $anggotaList->pluck('id')->all();

        $records = RekodKehadiran::with(['status', 'penempatan.bahagian', 'penempatan.unit'])
            ->whereDate('tarikh', $date)
            ->whereIn('anggota_id', $anggotaIds)
            ->get()
            ->keyBy('anggota_id');

        return view('reports.daily_print', compact('anggotaList', 'records', 'date'));
    }

    public function monthlyPrint(Request $request): View
    {
        $this->authorizePermission('attendance.view');
        $user = Auth::user();

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        $anggotaQuery = Anggota::where('aktif', true);
        $this->scopeService->scopeAnggota($anggotaQuery, $user, $startOfMonth->toDateString());
        $anggotaList = $anggotaQuery->orderByDesc('tahap_hierarki')->orderBy('susunan')->orderBy('nama_penuh')->get();
        $anggotaIds = $anggotaList->pluck('id')->all();

        $records = RekodKehadiran::with('status')
            ->whereYear('tarikh', $year)
            ->whereMonth('tarikh', $month)
            ->whereIn('anggota_id', $anggotaIds)
            ->get()
            ->groupBy('anggota_id');

        $statuses = StatusKehadiran::where('aktif', true)->orderBy('susunan')->get();

        return view('reports.monthly_print', compact(
            'anggotaList',
            'records',
            'statuses',
            'year',
            'month',
            'daysInMonth',
            'startOfMonth'
        ));
    }

    protected function authorizePermission(string $perm): void
    {
        if (! Auth::user()?->hasPermission($perm)) {
            abort(403, 'Anda tidak mempunyai kebenaran untuk mengakses fungsi ini.');
        }
    }
}
