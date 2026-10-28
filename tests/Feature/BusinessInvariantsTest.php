<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\AnggotaPenempatan;
use App\Models\AuditLog;
use App\Models\Bahagian;
use App\Models\Role;
use App\Models\StatusKehadiran;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserScope;
use App\Services\AttendanceService;
use App\Services\CalendarEventService;
use App\Services\PenempatanService;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StatusKehadiranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BusinessInvariantsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected User $scopedAdmin;

    protected Bahagian $bahagian1;

    protected Bahagian $bahagian2;

    protected Unit $unit1;

    protected Anggota $anggota1;

    protected Anggota $anggota2;

    protected StatusKehadiran $statusPejabat;

    protected StatusKehadiran $statusCuti;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & status
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(StatusKehadiranSeeder::class);

        $this->statusPejabat = StatusKehadiran::where('kod', 'PEJABAT')->first();
        $this->statusCuti = StatusKehadiran::where('kod', 'CUTI_REHAT')->first();

        // Create Bahagian & Unit
        $this->bahagian1 = Bahagian::create(['kod' => 'BTM', 'nama' => 'Bahagian Teknologi Maklumat', 'aktif' => true]);
        $this->bahagian2 = Bahagian::create(['kod' => 'BPSM', 'nama' => 'Bahagian Pengurusan Sumber Manusia', 'aktif' => true]);
        $this->unit1 = Unit::create(['kod' => 'UINT', 'nama' => 'Unit Integriti', 'aktif' => true]);

        // Create Superadmin
        $this->superadmin = User::create([
            'nama' => 'Superadmin',
            'email' => 'super@test.gov.my',
            'password_hash' => Hash::make('password'),
            'aktif' => true,
        ]);
        $this->superadmin->roles()->attach(Role::where('kod', 'SUPERADMIN')->first());

        // Create Scoped Admin (Scoped to Bahagian 1 only)
        $this->scopedAdmin = User::create([
            'nama' => 'Admin BTM',
            'email' => 'btm_admin@test.gov.my',
            'password_hash' => Hash::make('password'),
            'aktif' => true,
        ]);
        $this->scopedAdmin->roles()->attach(Role::where('kod', 'ADMIN')->first());
        UserScope::create([
            'user_id' => $this->scopedAdmin->id,
            'bahagian_id' => $this->bahagian1->id,
        ]);

        // Create Anggota 1 (posted to Bahagian 1)
        $this->anggota1 = Anggota::create([
            'no_pekerja' => 'STAFF001',
            'nama_penuh' => 'Ahmad Bin Ali',
            'gelaran' => 'En.',
            'jawatan' => 'Pegawai Teknologi Maklumat',
            'aktif' => true,
            'susunan' => 1,
        ]);

        AnggotaPenempatan::create([
            'anggota_id' => $this->anggota1->id,
            'bahagian_id' => $this->bahagian1->id,
            'tarikh_mula' => '2026-01-01',
            'tarikh_tamat' => '2026-06-30',
        ]);

        // Create Anggota 2 (posted to Bahagian 2)
        $this->anggota2 = Anggota::create([
            'no_pekerja' => 'STAFF002',
            'nama_penuh' => 'Siti Binti Kassim',
            'gelaran' => 'Pn.',
            'jawatan' => 'Penolong Pegawai Tadbir',
            'aktif' => true,
            'susunan' => 2,
        ]);

        AnggotaPenempatan::create([
            'anggota_id' => $this->anggota2->id,
            'bahagian_id' => $this->bahagian2->id,
            'tarikh_mula' => '2026-01-01',
            'tarikh_tamat' => null, // active
        ]);
    }

    /**
     * Invariant 1: No overlapping anggota_penempatan periods per anggota.
     */
    public function test_invariant_1_prevents_overlapping_placements(): void
    {
        $penempatanService = app(PenempatanService::class);

        // Anggota1 already has placement: 2026-01-01 to 2026-06-30
        // Attempting to add 2026-05-01 to 2026-08-01 should fail
        $this->expectException(ValidationException::class);

        $penempatanService->createPenempatan([
            'anggota_id' => $this->anggota1->id,
            'bahagian_id' => $this->bahagian2->id,
            'tarikh_mula' => '2026-05-01',
            'tarikh_tamat' => '2026-08-01',
        ], $this->superadmin->id);
    }

    public function test_invariant_1_allows_contiguous_non_overlapping_placement(): void
    {
        $penempatanService = app(PenempatanService::class);

        // Anggota1 has placement ending on 2026-06-30
        // Starting next placement on 2026-07-01 should succeed!
        $newPlacement = $penempatanService->createPenempatan([
            'anggota_id' => $this->anggota1->id,
            'bahagian_id' => $this->bahagian2->id,
            'tarikh_mula' => '2026-07-01',
            'tarikh_tamat' => null,
        ], $this->superadmin->id);

        $this->assertNotNull($newPlacement->id);
        $this->assertEquals('2026-07-01', $newPlacement->tarikh_mula->toDateString());
    }

    /**
     * Invariant 2: A record's penempatan must cover rekod_kehadiran.tarikh.
     */
    public function test_invariant_2_requires_valid_placement_covering_attendance_date(): void
    {
        $attendanceService = app(AttendanceService::class);

        // Anggota1 has placement 2026-01-01 to 2026-06-30
        // Recording attendance on 2026-10-15 (no active placement) must fail
        $this->expectException(ValidationException::class);

        $attendanceService->recordAttendance([
            'anggota_id' => $this->anggota1->id,
            'status_id' => $this->statusPejabat->id,
            'tarikh' => '2026-10-15',
        ], $this->superadmin);
    }

    /**
     * Invariant 3: ADMIN operations must enforce user_scopes for the historical placement.
     */
    public function test_invariant_3_enforces_admin_scope(): void
    {
        $attendanceService = app(AttendanceService::class);

        // Scoped admin has scope for Bahagian 1 only.
        // Anggota 2 is in Bahagian 2.
        // Recording attendance for Anggota 2 by scoped admin must fail!
        $this->expectException(ValidationException::class);

        $attendanceService->recordAttendance([
            'anggota_id' => $this->anggota2->id,
            'status_id' => $this->statusPejabat->id,
            'tarikh' => '2026-03-01',
        ], $this->scopedAdmin);
    }

    public function test_invariant_3_allows_scoped_admin_for_authorized_staff(): void
    {
        $attendanceService = app(AttendanceService::class);

        // Anggota 1 is in Bahagian 1 on 2026-03-01. Scoped admin manages Bahagian 1.
        $record = $attendanceService->recordAttendance([
            'anggota_id' => $this->anggota1->id,
            'status_id' => $this->statusPejabat->id,
            'tarikh' => '2026-03-01',
        ], $this->scopedAdmin);

        $this->assertNotNull($record->id);
        $this->assertEquals($this->statusPejabat->id, $record->status_id);
    }

    /**
     * Invariant 4: Calendar event attendees must be in actor's authorized scope.
     */
    public function test_invariant_4_validates_attendees_in_actor_scope(): void
    {
        $calendarService = app(CalendarEventService::class);

        // Scoped admin attempts to create an event inviting Anggota 2 (Bahagian 2)
        $this->expectException(ValidationException::class);

        $calendarService->createEvent([
            'tajuk' => 'Mesyuarat Penyelarasan',
            'mula' => '2026-04-10 09:00:00',
            'tamat' => '2026-04-10 11:00:00',
            'jenis' => 'MESYUARAT',
        ], [$this->anggota2->id], $this->scopedAdmin);
    }

    /**
     * Invariant 5: Soft-deleted attendance retains uq_harian; restore/update instead of duplicate key error.
     */
    public function test_invariant_5_restores_and_updates_soft_deleted_attendance(): void
    {
        $attendanceService = app(AttendanceService::class);

        // Create attendance record
        $record = $attendanceService->recordAttendance([
            'anggota_id' => $this->anggota1->id,
            'status_id' => $this->statusPejabat->id,
            'tarikh' => '2026-02-15',
        ], $this->superadmin);

        // Soft delete it
        $attendanceService->deleteAttendance($record, $this->superadmin);
        $this->assertSoftDeleted('rekod_kehadiran', ['id' => $record->id]);

        // Now record attendance on the same date for the same staff with CUTI_REHAT
        $restoredRecord = $attendanceService->recordAttendance([
            'anggota_id' => $this->anggota1->id,
            'status_id' => $this->statusCuti->id,
            'tarikh' => '2026-02-15',
        ], $this->superadmin);

        // Must reuse the same record ID, restore it, and update the status
        $this->assertEquals($record->id, $restoredRecord->id);
        $this->assertNull($restoredRecord->deleted_at);
        $this->assertEquals($this->statusCuti->id, $restoredRecord->status_id);
    }

    /**
     * Invariant 6: Audit logs recorded in same transaction.
     */
    public function test_invariant_6_records_audit_trail_on_mutations(): void
    {
        $attendanceService = app(AttendanceService::class);

        $record = $attendanceService->recordAttendance([
            'anggota_id' => $this->anggota1->id,
            'status_id' => $this->statusPejabat->id,
            'tarikh' => '2026-02-20',
        ], $this->superadmin);

        $log = AuditLog::where('nama_jadual', 'rekod_kehadiran')
            ->where('record_id', $record->id)
            ->where('tindakan', 'CREATE')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->superadmin->id, $log->user_id);
        $this->assertNotNull($log->data_baru);
    }
}
