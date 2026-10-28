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
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StatusKehadiranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CruWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected User $admin;

    protected Bahagian $bahagian;

    protected Unit $unit;

    protected StatusKehadiran $status;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(StatusKehadiranSeeder::class);

        $this->status = StatusKehadiran::where('kod', 'PEJABAT')->first();

        $this->bahagian = Bahagian::create(['kod' => 'BTM', 'nama' => 'Bahagian Teknologi Maklumat', 'aktif' => true]);
        $this->unit = Unit::create(['kod' => 'UINT', 'nama' => 'Unit Integriti', 'aktif' => true]);

        $this->superadmin = User::create([
            'nama' => 'Superadmin User',
            'email' => 'super@ehadir.gov.my',
            'password_hash' => Hash::make('Password@123'),
            'aktif' => true,
        ]);
        $this->superadmin->roles()->attach(Role::where('kod', 'SUPERADMIN')->first());

        $this->admin = User::create([
            'nama' => 'Admin User',
            'email' => 'admin@ehadir.gov.my',
            'password_hash' => Hash::make('Password@123'),
            'aktif' => true,
        ]);
        $this->admin->roles()->attach(Role::where('kod', 'ADMIN')->first());
        UserScope::create([
            'user_id' => $this->admin->id,
            'bahagian_id' => $this->bahagian->id,
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'super@ehadir.gov.my',
            'password' => 'Password@123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->superadmin);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'super@ehadir.gov.my',
            'password' => 'WrongPassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_dashboard_displays_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->superadmin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Ringkasan Kehadiran Harian');
    }

    public function test_bahagian_cru_lifecycle(): void
    {
        // 1. Create page
        $this->actingAs($this->superadmin)->get(route('organization.bahagian.create'))->assertStatus(200);

        // 2. Store
        $postResponse = $this->actingAs($this->superadmin)->post(route('organization.bahagian.store'), [
            'kod' => 'BPSM',
            'nama' => 'Bahagian Pengurusan Sumber Manusia',
            'aktif' => 1,
        ]);
        $postResponse->assertRedirect();
        $this->assertDatabaseHas('bahagian', ['kod' => 'BPSM']);

        $b = Bahagian::where('kod', 'BPSM')->first();

        // 3. Show & Edit
        $this->actingAs($this->superadmin)->get(route('organization.bahagian.show', $b))->assertStatus(200);
        $this->actingAs($this->superadmin)->get(route('organization.bahagian.edit', $b))->assertStatus(200);

        // 4. Update
        $this->actingAs($this->superadmin)->put(route('organization.bahagian.update', $b), [
            'kod' => 'BPSM_NEW',
            'nama' => 'Bahagian Sumber Manusia Kemaskini',
            'aktif' => 1,
        ]);
        $this->assertDatabaseHas('bahagian', ['kod' => 'BPSM_NEW']);

        // 5. Delete
        $this->actingAs($this->superadmin)->delete(route('organization.bahagian.destroy', $b));
        $this->assertDatabaseMissing('bahagian', ['id' => $b->id]);
    }

    public function test_anggota_and_penempatan_cru_lifecycle(): void
    {
        // 1. Create Anggota with initial placement
        $response = $this->actingAs($this->superadmin)->post(route('personnel.anggota.store'), [
            'nama_penuh' => 'Mohd Azlan Bin Ahmad',
            'no_pekerja' => 'STAFF999',
            'gelaran' => 'En.',
            'jawatan' => 'Penolong Pegawai Tadbir',
            'susunan' => 1,
            'aktif' => 1,
            'penempatan_type' => 'bahagian',
            'bahagian_id' => $this->bahagian->id,
            'tarikh_mula' => '2026-01-01',
        ]);

        $anggota = Anggota::where('no_pekerja', 'STAFF999')->first();
        $this->assertNotNull($anggota);
        $response->assertRedirect(route('personnel.anggota.show', $anggota));

        $this->assertDatabaseHas('anggota_penempatan', [
            'anggota_id' => $anggota->id,
            'bahagian_id' => $this->bahagian->id,
        ]);
        $initialPlacement = AnggotaPenempatan::where('anggota_id', $anggota->id)->first();
        $this->assertEquals('2026-01-01', $initialPlacement->tarikh_mula->toDateString());

        // 2. Transfer Anggota to Unit with auto_transfer
        $transferResponse = $this->actingAs($this->superadmin)->post(route('personnel.penempatan.store', $anggota), [
            'penempatan_type' => 'unit',
            'unit_id' => $this->unit->id,
            'tarikh_mula' => '2026-06-01',
            'auto_transfer' => 1,
        ]);

        $transferResponse->assertRedirect(route('personnel.anggota.show', $anggota));

        // Old placement should now have tarikh_tamat = 2026-05-31
        $oldPlacement = AnggotaPenempatan::where('anggota_id', $anggota->id)
            ->where('bahagian_id', $this->bahagian->id)
            ->first();
        $this->assertEquals('2026-05-31', $oldPlacement->tarikh_tamat->toDateString());

        // New placement created
        $this->assertDatabaseHas('anggota_penempatan', [
            'anggota_id' => $anggota->id,
            'unit_id' => $this->unit->id,
        ]);
        $newPlacement = AnggotaPenempatan::where('anggota_id', $anggota->id)
            ->where('unit_id', $this->unit->id)
            ->first();
        $this->assertEquals('2026-06-01', $newPlacement->tarikh_mula->toDateString());
        $this->assertNull($newPlacement->tarikh_tamat);
    }

    public function test_attendance_recording_and_batch(): void
    {
        $anggota = Anggota::create([
            'nama_penuh' => 'Wan Nurul Binti Daud',
            'no_pekerja' => 'STAFF888',
            'aktif' => true,
        ]);

        AnggotaPenempatan::create([
            'anggota_id' => $anggota->id,
            'bahagian_id' => $this->bahagian->id,
            'tarikh_mula' => '2026-01-01',
            'tarikh_tamat' => null,
        ]);

        // Record attendance
        $response = $this->actingAs($this->superadmin)->post(route('attendance.store'), [
            'anggota_id' => $anggota->id,
            'status_id' => $this->status->id,
            'tarikh' => '2026-03-10',
            'sepanjang_hari' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('rekod_kehadiran', [
            'anggota_id' => $anggota->id,
            'status_id' => $this->status->id,
        ]);
        $rec = RekodKehadiran::where('anggota_id', $anggota->id)->first();
        $this->assertEquals('2026-03-10', $rec->tarikh->toDateString());
    }

    public function test_calendar_event_cru(): void
    {
        $anggota = Anggota::create([
            'nama_penuh' => 'Harun Bin Zakaria',
            'no_pekerja' => 'STAFF777',
            'aktif' => true,
        ]);

        AnggotaPenempatan::create([
            'anggota_id' => $anggota->id,
            'bahagian_id' => $this->bahagian->id,
            'tarikh_mula' => '2026-01-01',
            'tarikh_tamat' => null,
        ]);

        $response = $this->actingAs($this->superadmin)->post(route('calendar.events.store'), [
            'tajuk' => 'Bengkel Pengurusan Projek',
            'jenis' => 'BENGKEL',
            'mula' => '2026-04-15 09:00',
            'tamat' => '2026-04-15 17:00',
            'sepanjang_hari' => 0,
            'lokasi' => 'Bilik Latihan ICT',
            'attendees' => [$anggota->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('calendar_events', ['tajuk' => 'Bengkel Pengurusan Projek']);
    }

    public function test_audit_logs_accessible_only_by_superadmin(): void
    {
        // Superadmin can view
        $this->actingAs($this->superadmin)->get(route('audit.index'))->assertStatus(200);

        // Scoped admin is forbidden (403)
        $this->actingAs($this->admin)->get(route('audit.index'))->assertStatus(403);
    }
}
