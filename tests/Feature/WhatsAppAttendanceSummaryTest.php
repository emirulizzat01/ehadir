<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\AnggotaPenempatan;
use App\Models\Bahagian;
use App\Models\RekodKehadiran;
use App\Models\Role;
use App\Models\StatusKehadiran;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserScope;
use App\Services\AttendanceService;
use App\Services\WhatsAppSummaryService;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StatusKehadiranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WhatsAppAttendanceSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected User $scopedAdmin;

    protected Bahagian $bahagian1;

    protected Bahagian $bahagian2;

    protected Unit $unit1;

    protected Anggota $anggota1;

    protected Anggota $anggota2;

    protected Anggota $anggota3;

    protected StatusKehadiran $statusPejabat;

    protected StatusKehadiran $statusCuti;

    protected StatusKehadiran $statusKerjaLuar;

    protected StatusKehadiran $status4Jam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(StatusKehadiranSeeder::class);

        $this->statusPejabat = StatusKehadiran::where('kod', 'PEJABAT')->first();
        $this->statusCuti = StatusKehadiran::where('kod', 'CUTI_REHAT')->first();
        $this->statusKerjaLuar = StatusKehadiran::where('kod', 'KERJA_LUAR')->first();
        $this->status4Jam = StatusKehadiran::where('kod', 'EMPAT_JAM')->first();

        $this->bahagian1 = Bahagian::create(['kod' => 'BTM', 'nama' => 'Teknologi Maklumat', 'aktif' => true]);
        $this->bahagian2 = Bahagian::create(['kod' => 'BKP', 'nama' => 'Khidmat Pengurusan', 'aktif' => true]);
        $this->unit1 = Unit::create(['bahagian_id' => $this->bahagian1->id, 'kod' => 'UKK', 'nama' => 'Komunikasi Korporat', 'aktif' => true]);

        $this->superadmin = User::create([
            'nama' => 'Super Admin',
            'email' => 'admin@kejora.gov.my',
            'password_hash' => Hash::make('password'),
            'aktif' => true,
        ]);
        $this->superadmin->roles()->attach(Role::where('kod', 'SUPERADMIN')->first());

        $this->scopedAdmin = User::create([
            'nama' => 'Admin BTM',
            'email' => 'adminbtm@kejora.gov.my',
            'password_hash' => Hash::make('password'),
            'aktif' => true,
        ]);
        $this->scopedAdmin->roles()->attach(Role::where('kod', 'ADMIN')->first());
        UserScope::create([
            'user_id' => $this->scopedAdmin->id,
            'bahagian_id' => $this->bahagian1->id,
        ]);

        // Anggota 1 (BTM)
        $this->anggota1 = Anggota::create([
            'no_pekerja' => 'STAFF-001',
            'nama_penuh' => 'Ahmad Razak',
            'aktif' => true,
        ]);
        AnggotaPenempatan::create([
            'anggota_id' => $this->anggota1->id,
            'bahagian_id' => $this->bahagian1->id,
            'unit_id' => $this->unit1->id,
            'jawatan_hakiki' => 'Pegawai IT',
            'jenis_penempatan' => 'UNIT',
            'tarikh_mula' => '2026-01-01',
        ]);

        // Anggota 2 (BTM)
        $this->anggota2 = Anggota::create([
            'no_pekerja' => 'STAFF-002',
            'nama_penuh' => 'Siti Nurhaliza',
            'aktif' => true,
        ]);
        AnggotaPenempatan::create([
            'anggota_id' => $this->anggota2->id,
            'bahagian_id' => $this->bahagian1->id,
            'jawatan_hakiki' => 'Penolong Pegawai IT',
            'jenis_penempatan' => 'BAHAGIAN',
            'tarikh_mula' => '2026-01-01',
        ]);

        // Anggota 3 (BKP)
        $this->anggota3 = Anggota::create([
            'no_pekerja' => 'STAFF-003',
            'nama_penuh' => 'Mohd Azman',
            'aktif' => true,
        ]);
        AnggotaPenempatan::create([
            'anggota_id' => $this->anggota3->id,
            'bahagian_id' => $this->bahagian2->id,
            'jawatan_hakiki' => 'Pegawai Tadbir',
            'jenis_penempatan' => 'BAHAGIAN',
            'tarikh_mula' => '2026-01-01',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_whatsapp_summary(): void
    {
        $response = $this->getJson(route('attendance.whatsapp-summary'));
        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_fetch_whatsapp_summary(): void
    {
        $response = $this->actingAs($this->superadmin)->getJson(route('attendance.whatsapp-summary', [
            'tarikh' => '2026-10-28',
        ]));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'tarikh',
                'scope_label',
                'format',
                'text',
                'stats' => [
                    'total',
                    'pejabat',
                    'duty',
                    'leave',
                    'unrecorded',
                    'presence_rate',
                ],
            ]);
    }

    public function test_whatsapp_summary_contains_correct_statistics_and_emojis(): void
    {
        $date = '2026-10-28';
        $attendanceService = app(AttendanceService::class);

        // Anggota 1: PEJABAT
        $attendanceService->recordAttendance([
            'anggota_id' => $this->anggota1->id,
            'status_id' => $this->statusPejabat->id,
            'tarikh' => $date,
        ], $this->superadmin);

        // Anggota 2: KERJA_LUAR with location
        $attendanceService->recordAttendance([
            'anggota_id' => $this->anggota2->id,
            'status_id' => $this->statusKerjaLuar->id,
            'tarikh' => $date,
            'lokasi' => 'Putrajaya',
            'catatan' => 'Mesyuarat ICU',
        ], $this->superadmin);

        // Anggota 3: CUTI_REHAT
        $attendanceService->recordAttendance([
            'anggota_id' => $this->anggota3->id,
            'status_id' => $this->statusCuti->id,
            'tarikh' => $date,
        ], $this->superadmin);

        $response = $this->actingAs($this->superadmin)->getJson(route('attendance.whatsapp-summary', [
            'tarikh' => $date,
            'format' => 'exceptions',
        ]));

        $response->assertOk();
        $text = $response->json('text');

        $this->assertStringContainsString('STATUS KEHADIRAN HARIAN KEJORA', $text);
        $this->assertStringContainsString('Jumlah Anggota: *3 orang*', $text);
        $this->assertStringContainsString('Hadir di Pejabat: *1 orang*', $text);
        $this->assertStringContainsString('Bertugas Luar: *1 orang*', $text);
        $this->assertStringContainsString('Cuti / MC: *1 orang*', $text);
        $this->assertStringContainsString('BERTUGAS RASMI / KERJA LUAR (1)', $text);
        $this->assertStringContainsString('Putrajaya', $text);
        $this->assertStringContainsString('Siti Nurhaliza', $text);
        $this->assertStringContainsString('CUTI REHAT / SAKIT (MC) (1)', $text);
        $this->assertStringContainsString('Mohd Azman', $text);
        $this->assertStringContainsString('1 orang anggota bertugas di pejabat', $text);
    }

    public function test_whatsapp_summary_supports_different_format_modes(): void
    {
        $date = '2026-10-28';
        $attendanceService = app(AttendanceService::class);

        $attendanceService->recordAttendance([
            'anggota_id' => $this->anggota1->id,
            'status_id' => $this->statusPejabat->id,
            'tarikh' => $date,
        ], $this->superadmin);

        // Format stats only
        $resStats = $this->actingAs($this->superadmin)->getJson(route('attendance.whatsapp-summary', [
            'tarikh' => $date,
            'format' => 'stats',
        ]));
        $textStats = $resStats->json('text');
        $this->assertStringContainsString('RINGKASAN KEHADIRAN', $textStats);
        $this->assertStringNotContainsString('Ahmad Razak', $textStats);

        // Format full (includes names under Pejabat)
        $resFull = $this->actingAs($this->superadmin)->getJson(route('attendance.whatsapp-summary', [
            'tarikh' => $date,
            'format' => 'full',
        ]));
        $textFull = $resFull->json('text');
        $this->assertStringContainsString('HADIR DI PEJABAT (1)', $textFull);
        $this->assertStringContainsString('1. Ahmad Razak', $textFull);
    }

    public function test_whatsapp_summary_respects_user_scope(): void
    {
        $date = '2026-10-28';
        $attendanceService = app(AttendanceService::class);

        // Both in BTM and BKP have records
        $attendanceService->recordAttendance([
            'anggota_id' => $this->anggota1->id,
            'status_id' => $this->statusPejabat->id,
            'tarikh' => $date,
        ], $this->superadmin);

        $attendanceService->recordAttendance([
            'anggota_id' => $this->anggota3->id,
            'status_id' => $this->statusPejabat->id,
            'tarikh' => $date,
        ], $this->superadmin);

        // Scoped admin for BTM only sees 2 members (anggota1 & anggota2), not anggota3 (BKP)
        $response = $this->actingAs($this->scopedAdmin)->getJson(route('attendance.whatsapp-summary', [
            'tarikh' => $date,
        ]));

        $response->assertOk();
        $this->assertEquals(2, $response->json('stats.total'));
        $this->assertStringContainsString('Bahagian Teknologi Maklumat', $response->json('scope_label'));
        $this->assertStringNotContainsString('Mohd Azman', $response->json('text'));
    }

    public function test_whatsapp_summary_filters_by_specific_unit(): void
    {
        $date = '2026-10-28';

        $response = $this->actingAs($this->superadmin)->getJson(route('attendance.whatsapp-summary', [
            'tarikh' => $date,
            'unit_id' => $this->unit1->id,
        ]));

        $response->assertOk();
        $this->assertEquals(1, $response->json('stats.total'));
        $this->assertStringContainsString('Unit Komunikasi Korporat', $response->json('scope_label'));
    }
}

