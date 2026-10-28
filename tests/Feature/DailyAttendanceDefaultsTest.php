<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\AnggotaPenempatan;
use App\Models\AuditLog;
use App\Models\Bahagian;
use App\Models\RekodKehadiran;
use App\Models\Role;
use App\Models\StatusKehadiran;
use App\Models\User;
use App\Services\AttendanceService;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StatusKehadiranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DailyAttendanceDefaultsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected Bahagian $bahagian;

    protected Anggota $anggota1;

    protected Anggota $anggota2;

    protected Anggota $anggotaNoPlacement;

    protected StatusKehadiran $statusPejabat;

    protected StatusKehadiran $statusCuti;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(StatusKehadiranSeeder::class);

        $this->statusPejabat = StatusKehadiran::where('kod', 'PEJABAT')->first();
        $this->statusCuti = StatusKehadiran::where('kod', 'CUTI_REHAT')->first();

        $this->bahagian = Bahagian::create([
            'kod' => 'BTM',
            'nama' => 'Bahagian Teknologi Maklumat',
            'aktif' => true,
        ]);

        $this->superadmin = User::create([
            'nama' => 'Super Admin',
            'email' => 'admin@kejora.gov.my',
            'password_hash' => Hash::make('password'),
            'aktif' => true,
        ]);
        $this->superadmin->roles()->attach(Role::where('kod', 'SUPERADMIN')->first());

        $this->anggota1 = Anggota::create([
            'no_pekerja' => 'STAFF-001',
            'nama_penuh' => 'Ahmad Razak',
            'aktif' => true,
        ]);
        AnggotaPenempatan::create([
            'anggota_id' => $this->anggota1->id,
            'bahagian_id' => $this->bahagian->id,
            'jawatan_hakiki' => 'Pegawai IT',
            'jenis_penempatan' => 'BAHAGIAN',
            'tarikh_mula' => '2026-01-01',
            'tarikh_tamat' => null,
        ]);

        $this->anggota2 = Anggota::create([
            'no_pekerja' => 'STAFF-002',
            'nama_penuh' => 'Siti Nurhaliza',
            'aktif' => true,
        ]);
        AnggotaPenempatan::create([
            'anggota_id' => $this->anggota2->id,
            'bahagian_id' => $this->bahagian->id,
            'jawatan_hakiki' => 'Penolong Pegawai IT',
            'jenis_penempatan' => 'BAHAGIAN',
            'tarikh_mula' => '2026-01-01',
            'tarikh_tamat' => null,
        ]);

        $this->anggotaNoPlacement = Anggota::create([
            'no_pekerja' => 'STAFF-003',
            'nama_penuh' => 'Tanpa Penempatan',
            'aktif' => true,
        ]);
    }

    public function test_command_initializes_pejabat_for_active_staff_on_workday(): void
    {
        $targetDate = '2026-10-28'; // Wednesday

        $this->artisan('attendance:default-pejabat', ['--date' => $targetDate])
            ->assertSuccessful()
            ->expectsOutputToContain('Penetapan Status Lalai: PEJABAT')
            ->expectsOutputToContain('Rekod Pejabat Baharu Dijana');

        $this->assertDatabaseHas('rekod_kehadiran', [
            'anggota_id' => $this->anggota1->id,
            'status_id' => $this->statusPejabat->id,
            'tarikh' => $targetDate,
            'created_by' => null,
            'updated_by' => null,
        ]);

        $this->assertDatabaseHas('rekod_kehadiran', [
            'anggota_id' => $this->anggota2->id,
            'status_id' => $this->statusPejabat->id,
            'tarikh' => $targetDate,
            'created_by' => null,
            'updated_by' => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'tindakan' => 'BATCH_DEFAULT_PEJABAT',
            'nama_jadual' => 'rekod_kehadiran',
        ]);
    }

    public function test_command_is_idempotent_and_does_not_duplicate_records(): void
    {
        $targetDate = '2026-10-28';

        $this->artisan('attendance:default-pejabat', ['--date' => $targetDate])
            ->assertSuccessful();

        $this->assertEquals(2, RekodKehadiran::count());

        // Second run should skip already existing records
        $this->artisan('attendance:default-pejabat', ['--date' => $targetDate])
            ->assertSuccessful()
            ->expectsOutputToContain('Rekod Sedia Ada (Terpelihara)');

        $this->assertEquals(2, RekodKehadiran::count());
    }

    public function test_command_preserves_existing_exceptions_like_cuti_rehat(): void
    {
        $targetDate = '2026-10-28';
        $attendanceService = app(AttendanceService::class);

        // Pre-record staff 1 as on Cuti Rehat
        $attendanceService->recordAttendance([
            'anggota_id' => $this->anggota1->id,
            'status_id' => $this->statusCuti->id,
            'tarikh' => $targetDate,
            'sepanjang_hari' => true,
        ], $this->superadmin);

        // Run default command
        $this->artisan('attendance:default-pejabat', ['--date' => $targetDate])
            ->assertSuccessful();

        // Staff 1 must still have CUTI_REHAT
        $rec1 = RekodKehadiran::where('anggota_id', $this->anggota1->id)->whereDate('tarikh', $targetDate)->first();
        $this->assertNotNull($rec1);
        $this->assertEquals($this->statusCuti->id, $rec1->status_id);
        $this->assertEquals($this->superadmin->id, $rec1->created_by);

        // Staff 2 must be initialized to PEJABAT
        $rec2 = RekodKehadiran::where('anggota_id', $this->anggota2->id)->whereDate('tarikh', $targetDate)->first();
        $this->assertNotNull($rec2);
        $this->assertEquals($this->statusPejabat->id, $rec2->status_id);
        $this->assertNull($rec2->created_by);
    }

    public function test_command_skips_staff_without_valid_placement(): void
    {
        $targetDate = '2026-10-28';

        $this->artisan('attendance:default-pejabat', ['--date' => $targetDate])
            ->assertSuccessful();

        $this->assertDatabaseMissing('rekod_kehadiran', [
            'anggota_id' => $this->anggotaNoPlacement->id,
            'tarikh' => $targetDate,
        ]);
    }

    public function test_command_skips_weekends_unless_forced(): void
    {
        $saturday = '2026-10-31';

        $this->artisan('attendance:default-pejabat', ['--date' => $saturday])
            ->assertSuccessful()
            ->expectsOutputToContain('adalah hari hujung minggu');

        $this->assertEquals(0, RekodKehadiran::whereDate('tarikh', $saturday)->count());

        // With --force
        $this->artisan('attendance:default-pejabat', ['--date' => $saturday, '--force' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Rekod Pejabat Baharu Dijana');

        $this->assertEquals(2, RekodKehadiran::whereDate('tarikh', $saturday)->count());
    }

    public function test_dry_run_mode_does_not_persist_records(): void
    {
        $targetDate = '2026-10-28';

        $this->artisan('attendance:default-pejabat', ['--date' => $targetDate, '--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Mod Simulasi (DRY-RUN) Aktif');

        $this->assertEquals(0, RekodKehadiran::count());
    }

    public function test_admin_can_update_exception_on_auto_generated_record(): void
    {
        $targetDate = '2026-10-28';

        // 1. Run automated daily defaults
        $this->artisan('attendance:default-pejabat', ['--date' => $targetDate])
            ->assertSuccessful();

        $autoRecord = RekodKehadiran::where('anggota_id', $this->anggota1->id)->whereDate('tarikh', $targetDate)->first();
        $this->assertNotNull($autoRecord);
        $this->assertNull($autoRecord->created_by);
        $this->assertEquals($this->statusPejabat->id, $autoRecord->status_id);

        // 2. Admin updates exception via AttendanceService / UI
        $attendanceService = app(AttendanceService::class);
        $updatedRecord = $attendanceService->recordAttendance([
            'anggota_id' => $this->anggota1->id,
            'status_id' => $this->statusCuti->id,
            'tarikh' => $targetDate,
            'catatan' => 'Cuti kecemasan diluluskan',
        ], $this->superadmin);

        $this->assertEquals($autoRecord->id, $updatedRecord->id);
        $this->assertEquals($this->statusCuti->id, $updatedRecord->status_id);
        $this->assertNull($updatedRecord->created_by); // Preserves original creation info
        $this->assertEquals($this->superadmin->id, $updatedRecord->updated_by); // Stamped by admin

        $this->assertDatabaseHas('audit_logs', [
            'tindakan' => 'UPDATE',
            'nama_jadual' => 'rekod_kehadiran',
            'record_id' => $autoRecord->id,
            'user_id' => $this->superadmin->id,
        ]);
    }

    public function test_web_request_to_today_roster_auto_primes_pejabat_defaults_jit(): void
    {
        \Carbon\Carbon::setTestNow('2026-10-28 09:00:00'); // Wednesday

        $this->assertEquals(0, RekodKehadiran::count());

        $response = $this->actingAs($this->superadmin)->get(route('attendance.index'));

        $response->assertOk();
        $this->assertEquals(2, RekodKehadiran::whereDate('tarikh', '2026-10-28')->count());

        $this->assertDatabaseHas('rekod_kehadiran', [
            'anggota_id' => $this->anggota1->id,
            'status_id' => $this->statusPejabat->id,
            'tarikh' => '2026-10-28',
        ]);
    }

    public function test_prime_defaults_post_endpoint(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('attendance.prime-defaults'), [
            'tarikh' => '2026-10-28',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(2, RekodKehadiran::whereDate('tarikh', '2026-10-28')->count());
    }
}
