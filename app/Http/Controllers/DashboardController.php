<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\AuditLog;
use App\Models\CalendarEvent;
use App\Models\RekodKehadiran;
use App\Models\StatusKehadiran;
use App\Services\AttendanceService;
use App\Services\ScopeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ScopeService $scopeService,
        protected AttendanceService $attendanceService
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // Self-healing JIT default priming: if viewing dashboard on a weekday, ensure defaults are primed
        if (! Carbon::today()->isWeekend()) {
            $this->attendanceService->initializeDailyDefaults($today);
        }

        // 1. Get scoped anggota
        $anggotaQuery = Anggota::query()->where('aktif', true);
        $this->scopeService->scopeAnggota($anggotaQuery, $user, $today);
        $totalAnggota = $anggotaQuery->count();
        $scopedAnggotaIds = $anggotaQuery->pluck('id')->all();

        // 2. Today's attendance records for these anggota
        $todayRecords = RekodKehadiran::with(['status', 'anggota'])
            ->whereDate('tarikh', $today)
            ->whereIn('anggota_id', $scopedAnggotaIds)
            ->get();

        $allStatuses = StatusKehadiran::where('aktif', true)->orderBy('susunan')->get();

        $statusCounts = [];
        foreach ($allStatuses as $st) {
            $statusCounts[$st->kod] = [
                'nama' => $st->nama,
                'warna' => $st->warna,
                'count' => $todayRecords->where('status_id', $st->id)->count(),
            ];
        }

        $recordedCount = $todayRecords->count();
        $unrecordedCount = max(0, $totalAnggota - $recordedCount);
        $presentCount = $statusCounts['PEJABAT']['count'] ?? 0;
        $presenceRate = $totalAnggota > 0 ? round(($presentCount / $totalAnggota) * 100) : 0;

        // 3. Upcoming events (next 7 days)
        $upcomingEvents = CalendarEvent::with('attendees')
            ->whereDate('mula', '>=', $today)
            ->whereDate('mula', '<=', Carbon::today()->addDays(7)->toDateString())
            ->orderBy('mula')
            ->take(5)
            ->get();

        // 4. Recent audit activity (for superadmin)
        $recentAudits = $user->isSuperAdmin()
            ? AuditLog::with('user')->orderBy('created_at', 'desc')->take(6)->get()
            : collect();

        return view('dashboard', compact(
            'totalAnggota',
            'statusCounts',
            'allStatuses',
            'recordedCount',
            'unrecordedCount',
            'presenceRate',
            'upcomingEvents',
            'recentAudits',
            'todayRecords'
        ));
    }
}
