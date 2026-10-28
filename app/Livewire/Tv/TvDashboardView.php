<?php

namespace App\Livewire\Tv;

use App\Models\Anggota;
use App\Models\AnggotaPenempatan;
use App\Models\Bahagian;
use App\Models\CalendarEvent;
use App\Models\RekodKehadiran;
use App\Models\Unit;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TvDashboardView extends Component
{
    public string $currentDate;

    public string $mode = 'executive'; // 'executive' | 'department' | 'lobby'

    public ?int $bahagianId = null;

    public bool $isKiosk = false;

    public int $carouselPage = 0;

    public int $perPage = 6;

    public function mount(
        ?string $date = null,
        string $mode = 'executive',
        ?int $bahagianId = null,
        bool $isKiosk = false
    ): void {
        $this->currentDate = $date ?: now()->toDateString();
        $this->mode = in_array($mode, ['executive', 'department', 'lobby']) ? $mode : 'executive';
        $this->bahagianId = $bahagianId;
        $this->isKiosk = $isKiosk;
    }

    public function setMode(string $newMode): void
    {
        if ($this->isKiosk) {
            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        // Scoped admins are locked to department mode
        $scopedBahagianIds = $user->getScopedBahagianIds();
        if (count($scopedBahagianIds) > 0 && ! $user->isSuperAdmin()) {
            $this->mode = 'department';

            return;
        }

        if (in_array($newMode, ['executive', 'department', 'lobby'])) {
            $this->mode = $newMode;
        }
    }

    public function setBahagian(int $id): void
    {
        $user = Auth::user();
        if ($user && ! $user->isSuperAdmin() && count($user->getScopedBahagianIds()) > 0) {
            if (! in_array($id, $user->getScopedBahagianIds())) {
                return;
            }
        }

        $this->bahagianId = $id;
    }

    public function nextPage(): void
    {
        $this->carouselPage++;
    }

    public function render(): View
    {
        $today = $this->currentDate;

        // Self-healing JIT default priming: if viewing TV dashboard on a weekday, ensure defaults are primed
        if ($today === now()->toDateString() && ! now()->isWeekend()) {
            app(AttendanceService::class)->initializeDailyDefaults($today);
        }

        $now = now();
        $user = Auth::user();
        $canSwitchModes = ! $this->isKiosk && $user && ($user->isSuperAdmin() || count($user->getScopedBahagianIds()) === 0);

        $bahagianList = Bahagian::where('aktif', true)->orderBy('nama')->get();

        // 1. EXECUTIVE MODE DATA
        if ($this->mode === 'executive') {
            $totalStaff = Anggota::where('aktif', true)->count();

            $todayRecords = RekodKehadiran::with(['status', 'anggota'])
                ->whereDate('tarikh', $today)
                ->get();

            $presentCount = $todayRecords->where('status.kod', 'PEJABAT')->count();
            $leaveCount = $todayRecords->whereIn('status.kod', ['CUTI_REHAT', 'MC'])->count();
            $dutyCount = $todayRecords->whereIn('status.kod', ['KERJA_LUAR', 'EMPAT_JAM'])->count();
            $recordedCount = $todayRecords->count();
            $unrecordedCount = max(0, $totalStaff - $recordedCount);
            $presenceRate = $totalStaff > 0 ? round(($presentCount / $totalStaff) * 100, 1) : 0.0;

            $departmentStats = [];
            foreach ($bahagianList as $b) {
                $deptAnggotaIds = AnggotaPenempatan::where('bahagian_id', $b->id)
                    ->where('tarikh_mula', '<=', $today)
                    ->where(function ($q) use ($today) {
                        $q->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $today);
                    })
                    ->pluck('anggota_id')
                    ->unique()
                    ->all();

                $deptTotal = count($deptAnggotaIds);
                if ($deptTotal === 0) {
                    continue;
                }

                $deptPresent = $todayRecords->whereIn('anggota_id', $deptAnggotaIds)->where('status.kod', 'PEJABAT')->count();
                $deptLeave = $todayRecords->whereIn('anggota_id', $deptAnggotaIds)->whereIn('status.kod', ['CUTI_REHAT', 'MC'])->count();
                $deptDuty = $todayRecords->whereIn('anggota_id', $deptAnggotaIds)->whereIn('status.kod', ['KERJA_LUAR', 'EMPAT_JAM'])->count();
                $deptRate = $deptTotal > 0 ? round(($deptPresent / $deptTotal) * 100, 1) : 0.0;

                $departmentStats[] = [
                    'id' => $b->id,
                    'kod' => $b->kod,
                    'nama' => $b->nama,
                    'total' => $deptTotal,
                    'present' => $deptPresent,
                    'leave' => $deptLeave,
                    'duty' => $deptDuty,
                    'rate' => $deptRate,
                    'health' => $deptRate >= 85 ? 'emerald' : ($deptRate >= 70 ? 'amber' : 'rose'),
                ];
            }

            usort($departmentStats, fn ($a, $b) => $b['rate'] <=> $a['rate']);

            $todayEvents = CalendarEvent::with(['attendees', 'creator'])
                ->whereDate('mula', '<=', $today)
                ->whereDate('tamat', '>=', $today)
                ->orderBy('mula')
                ->get()
                ->map(function ($ev) use ($now) {
                    $ev->is_live = ($ev->mula <= $now && $ev->tamat >= $now) || $ev->sepanjang_hari;

                    return $ev;
                });

            $liveEventsCount = $todayEvents->where('is_live', true)->count();

            $tickerMessages = [
                'Pusat Pemantauan Eksekutif eHadir v5.0 — Sasaran Kehadiran Organisasi: &ge;85.0% di pejabat.',
                'Peringatan Pentadbir: Sila pastikan kehadiran harian direkodkan sebelum jam 09:30 pagi setiap hari bekerja.',
            ];

            foreach ($todayEvents as $ev) {
                $timeStr = $ev->sepanjang_hari ? 'Sepanjang Hari' : $ev->mula->format('H:i').' - '.$ev->tamat->format('H:i');
                $statusPrefix = $ev->is_live ? '🔴 SEDANG BERLANGSUNG: ' : '📅 AKAN DATANG: ';
                $tickerMessages[] = $statusPrefix.$ev->tajuk.' ('.($ev->lokasi ?: 'Lokasi Tidak Dinyatakan').', '.$timeStr.')';
            }

            return view('livewire.tv.tv-dashboard-view', [
                'totalStaff' => $totalStaff,
                'presentCount' => $presentCount,
                'leaveCount' => $leaveCount,
                'dutyCount' => $dutyCount,
                'unrecordedCount' => $unrecordedCount,
                'presenceRate' => $presenceRate,
                'departmentStats' => $departmentStats,
                'todayEvents' => $todayEvents,
                'liveEventsCount' => $liveEventsCount,
                'tickerMessages' => $tickerMessages,
                'bahagianList' => $bahagianList,
                'canSwitchModes' => $canSwitchModes,
                'formattedDate' => Carbon::parse($today)->translatedFormat('l, d F Y'),
                'lastUpdated' => now()->format('H:i:s'),
            ]);
        }

        // 2. DEPARTMENT MODE DATA
        if ($this->mode === 'department') {
            $currentBahagian = null;
            if ($this->bahagianId) {
                $currentBahagian = $bahagianList->firstWhere('id', $this->bahagianId);
            }
            if (! $currentBahagian && $user && count($user->getScopedBahagianIds()) > 0) {
                $currentBahagian = $bahagianList->firstWhere('id', $user->getScopedBahagianIds()[0]);
            }
            if (! $currentBahagian) {
                $currentBahagian = $bahagianList->first();
            }

            if ($currentBahagian) {
                $this->bahagianId = $currentBahagian->id;
            }

            $deptPenempatan = $currentBahagian ? AnggotaPenempatan::with(['anggota', 'unit'])
                ->where('bahagian_id', $currentBahagian->id)
                ->where('tarikh_mula', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $today);
                })
                ->get() : collect();

            $deptStaffMembers = $deptPenempatan
                ->map(fn ($p) => $p->anggota)
                ->filter(fn ($a) => $a && $a->aktif)
                ->unique('id')
                ->sortBy('susunan')
                ->values();

            $deptStaffIds = $deptStaffMembers->pluck('id')->all();

            $deptStaffRecords = RekodKehadiran::with('status')
                ->whereIn('anggota_id', $deptStaffIds)
                ->whereDate('tarikh', $today)
                ->get()
                ->keyBy('anggota_id');

            $deptTotal = $deptStaffMembers->count();
            $deptPresent = $deptStaffRecords->where('status.kod', 'PEJABAT')->count();
            $deptDuty = $deptStaffRecords->whereIn('status.kod', ['KERJA_LUAR', 'EMPAT_JAM'])->count();
            $deptLeave = $deptStaffRecords->whereIn('status.kod', ['CUTI_REHAT', 'MC'])->count();
            $deptUnrecorded = max(0, $deptTotal - ($deptPresent + $deptDuty + $deptLeave));
            $deptRate = $deptTotal > 0 ? round(($deptPresent / $deptTotal) * 100, 1) : 0.0;

            // Roster list combining staff with their today's record & unit
            $staffRoster = $deptStaffMembers->map(function ($staff) use ($deptPenempatan, $deptStaffRecords) {
                $placement = $deptPenempatan->firstWhere('anggota_id', $staff->id);
                $record = $deptStaffRecords->get($staff->id);

                return [
                    'id' => $staff->id,
                    'nama_penuh' => $staff->nama_penuh_gelaran,
                    'jawatan' => $staff->jawatan ?: 'Anggota',
                    'no_pekerja' => $staff->no_pekerja ?: '-',
                    'unit_nama' => $placement?->unit?->nama ?: 'Bahagian Terus',
                    'unit_kod' => $placement?->unit?->kod ?: '',
                    'status_kod' => $record?->status?->kod ?? 'BELUM_REKOD',
                    'status_nama' => $record?->status?->nama ?? 'Belum Rekod',
                    'status_warna' => $record?->status?->warna ?? '#94A3B8',
                    'masa_masuk' => $record?->masa_masuk ? substr($record->masa_masuk, 0, 5) : null,
                    'keterangan' => $record?->lokasi ?: ($record?->catatan ?: null),
                ];
            });

            // Prestasi Unit Bebas (Independent Units Attendance Performance)
            $activeUnits = Unit::where('aktif', true)->orderBy('nama')->get();
            $unitIds = $activeUnits->pluck('id')->all();

            $unitPenempatan = ! empty($unitIds)
                ? AnggotaPenempatan::whereIn('unit_id', $unitIds)
                    ->where('tarikh_mula', '<=', $today)
                    ->where(function ($q) use ($today) {
                        $q->whereNull('tarikh_tamat')->orWhere('tarikh_tamat', '>=', $today);
                    })
                    ->get()
                : collect();

            $allUnitStaffIds = $unitPenempatan->pluck('anggota_id')->unique()->all();

            $unitRecords = ! empty($allUnitStaffIds)
                ? RekodKehadiran::with('status')
                    ->whereIn('anggota_id', $allUnitStaffIds)
                    ->whereDate('tarikh', $today)
                    ->get()
                : collect();

            $deptUnits = $activeUnits->map(function ($u) use ($unitPenempatan, $unitRecords) {
                $uAnggotaIds = $unitPenempatan->where('unit_id', $u->id)->pluck('anggota_id')->unique()->all();
                $uTotal = count($uAnggotaIds);
                $uPresent = $uTotal > 0
                    ? $unitRecords->whereIn('anggota_id', $uAnggotaIds)->where('status.kod', 'PEJABAT')->count()
                    : 0;
                $uRate = $uTotal > 0 ? round(($uPresent / $uTotal) * 100, 1) : 0.0;

                return [
                    'nama' => $u->nama,
                    'kod' => $u->kod,
                    'total' => $uTotal,
                    'present' => $uPresent,
                    'rate' => $uRate,
                ];
            });

            // Department Events (attending or created)
            $deptEvents = ! empty($deptStaffIds)
                ? CalendarEvent::with(['attendees', 'creator'])
                    ->whereDate('mula', '<=', $today)
                    ->whereDate('tamat', '>=', $today)
                    ->whereHas('attendees', function ($aq) use ($deptStaffIds) {
                        $aq->whereIn('anggota_id', $deptStaffIds);
                    })
                    ->orderBy('mula')
                    ->get()
                    ->map(function ($ev) use ($now) {
                        $ev->is_live = ($ev->mula <= $now && $ev->tamat >= $now) || $ev->sepanjang_hari;

                        return $ev;
                    })
                : collect();

            $tickerMessages = [
                'Paparan Operasi '.($currentBahagian?->nama ?? 'Bahagian').' ('.($currentBahagian?->kod ?? '-').') &middot; Kadar Kehadiran: '.$deptRate.'%',
                'Status Semasa: '.$deptPresent.' Hadir Di Pejabat &middot; '.$deptDuty.' Bertugas Luar &middot; '.$deptLeave.' Cuti/MC &middot; '.$deptUnrecorded.' Belum Direkodkan.',
            ];

            foreach ($deptEvents as $ev) {
                $timeStr = $ev->sepanjang_hari ? 'Sepanjang Hari' : $ev->mula->format('H:i').' - '.$ev->tamat->format('H:i');
                $statusPrefix = $ev->is_live ? '🔴 SEDANG BERLANGSUNG: ' : '📅 ';
                $tickerMessages[] = $statusPrefix.$ev->tajuk.' ('.$timeStr.')';
            }

            return view('livewire.tv.tv-dashboard-view', [
                'currentBahagian' => $currentBahagian,
                'deptTotal' => $deptTotal,
                'deptPresent' => $deptPresent,
                'deptDuty' => $deptDuty,
                'deptLeave' => $deptLeave,
                'deptUnrecorded' => $deptUnrecorded,
                'deptRate' => $deptRate,
                'staffRoster' => $staffRoster,
                'deptUnits' => $deptUnits,
                'deptEvents' => $deptEvents,
                'tickerMessages' => $tickerMessages,
                'bahagianList' => $bahagianList,
                'canSwitchModes' => $canSwitchModes,
                'formattedDate' => Carbon::parse($today)->translatedFormat('l, d F Y'),
                'lastUpdated' => now()->format('H:i:s'),
            ]);
        }

        // 3. LOBBY MODE DATA (Public & Reception Kiosks - PDPA Compliant)
        $totalStaff = Anggota::where('aktif', true)->count();
        $todayRecords = RekodKehadiran::with('status')->whereDate('tarikh', $today)->get();
        $presentCount = $todayRecords->where('status.kod', 'PEJABAT')->count();
        $dutyCount = $todayRecords->whereIn('status.kod', ['KERJA_LUAR', 'EMPAT_JAM'])->count();
        $servingCount = $presentCount + $dutyCount;
        $readinessRate = $totalStaff > 0 ? round(($servingCount / $totalStaff) * 100, 1) : 0.0;

        // Public official events only
        $publicEvents = CalendarEvent::whereDate('mula', '<=', $today)
            ->whereDate('tamat', '>=', $today)
            ->whereIn('jenis', ['MESYUARAT', 'BENGKEL', 'KURSUS', 'LAWATAN_TAPAK'])
            ->orderBy('mula')
            ->get()
            ->map(function ($ev) use ($now) {
                $ev->is_live = ($ev->mula <= $now && $ev->tamat >= $now) || $ev->sepanjang_hari;

                return $ev;
            });

        $tickerMessages = [
            'Selamat Datang ke Kompleks Pentadbiran eHadir.',
            'Waktu Perkhidmatan Kaunter: 08:00 Pagi - 05:00 Petang (Isnin - Jumaat).',
            'Sila dapatkan pas pelawat di kaunter keselamatan utama sebelum berurusan.',
            'Kesiapsiagaan Perkhidmatan Hari Ini: '.$readinessRate.'% pegawai bertugas di kaunter & operasi.',
        ];

        return view('livewire.tv.tv-dashboard-view', [
            'totalStaff' => $totalStaff,
            'servingCount' => $servingCount,
            'readinessRate' => $readinessRate,
            'publicEvents' => $publicEvents,
            'tickerMessages' => $tickerMessages,
            'bahagianList' => $bahagianList,
            'canSwitchModes' => $canSwitchModes,
            'formattedDate' => Carbon::parse($today)->translatedFormat('l, d F Y'),
            'lastUpdated' => now()->format('H:i:s'),
        ]);
    }
}
