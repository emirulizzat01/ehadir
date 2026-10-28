<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\AnggotaPenempatan;
use App\Models\Bahagian;
use App\Models\CalendarEvent;
use App\Models\Role;
use App\Models\StatusKehadiran;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StatusKehadiranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UiContainerRulesTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected Bahagian $bahagian;

    protected Unit $unit;

    protected StatusKehadiran $status;

    protected Anggota $anggota;

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

        $this->anggota = Anggota::create([
            'nama_penuh' => 'Ahmad Khairi Bin Zainal',
            'no_pekerja' => 'STF101',
            'gelaran' => 'En.',
            'jawatan' => 'Penolong Pegawai',
            'susunan' => 1,
            'aktif' => true,
        ]);

        AnggotaPenempatan::create([
            'anggota_id' => $this->anggota->id,
            'bahagian_id' => $this->bahagian->id,
            'tarikh_mula' => '2026-01-01',
            'tarikh_tamat' => null,
        ]);
    }

    /**
     * Tier 1: Forms with <5 fields use in-context Modals.
     */
    public function test_tier1_forms_under_5_fields_render_modals(): void
    {
        // 1. Bahagian Index (3 fields: kod, nama, aktif) -> Modal
        $response = $this->actingAs($this->superadmin)->get(route('organization.bahagian.index'));
        $response->assertStatus(200);
        $response->assertSee('modal-create-bahagian');
        $response->assertSee('modal-edit-bahagian');

        // 2. Unit Index (3 fields: kod, nama, aktif) -> Modal
        $response = $this->actingAs($this->superadmin)->get(route('organization.unit.index'));
        $response->assertStatus(200);
        $response->assertSee('modal-create-unit');
        $response->assertSee('modal-edit-unit');

        // 3. Penempatan (<5 fields: penempatan_type, bahagian_id/unit_id, tarikh_mula, tarikh_tamat) -> Modal on Anggota Show
        $response = $this->actingAs($this->superadmin)->get(route('personnel.anggota.show', $this->anggota));
        $response->assertStatus(200);
        $response->assertSee('modal-create-penempatan');
    }

    /**
     * Tier 2: Forms with 5-10 fields use in-context Slide-Over drawers.
     */
    public function test_tier2_forms_5_to_10_fields_render_slide_overs(): void
    {
        // 1. Status Kehadiran Index (5 fields: kod, nama, warna, susunan, aktif) -> Slide-Over
        $response = $this->actingAs($this->superadmin)->get(route('organization.attendance-statuses.index'));
        $response->assertStatus(200);
        $response->assertSee('slideover-create-status');
        $response->assertSee('slideover-edit-status');

        // 2. Attendance Index (8 fields) -> Slide-Over
        $response = $this->actingAs($this->superadmin)->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('slideover-create-attendance');

        // 3. Calendar View (7 fields) -> Slide-Over
        $response = $this->actingAs($this->superadmin)->get(route('calendar.index'));
        $response->assertStatus(200);
        $response->assertSee('slideover-create-event');

        // 4. Anggota Directory Index (6-9 fields) -> Slide-Over
        $response = $this->actingAs($this->superadmin)->get(route('personnel.anggota.index'));
        $response->assertStatus(200);
        $response->assertSee('slideover-create-anggota');
        $response->assertSee('slideover-edit-anggota');

        // Anggota Show -> Slide-Over for profile editing
        $response = $this->actingAs($this->superadmin)->get(route('personnel.anggota.show', $this->anggota));
        $response->assertStatus(200);
        $response->assertSee('slideover-edit-anggota');
    }

    /**
     * Backward Compatibility & Invariant: Dedicated CRU routes must stay intact and functional.
     */
    public function test_dedicated_cru_routes_remain_functional_and_render_partials(): void
    {
        // Bahagian dedicated routes
        $this->actingAs($this->superadmin)->get(route('organization.bahagian.create'))->assertStatus(200);
        $this->actingAs($this->superadmin)->get(route('organization.bahagian.edit', $this->bahagian))->assertStatus(200);

        // Unit dedicated routes
        $this->actingAs($this->superadmin)->get(route('organization.unit.create'))->assertStatus(200);
        $this->actingAs($this->superadmin)->get(route('organization.unit.edit', $this->unit))->assertStatus(200);

        // Status Kehadiran dedicated routes
        $this->actingAs($this->superadmin)->get(route('organization.attendance-statuses.create'))->assertStatus(200);
        $this->actingAs($this->superadmin)->get(route('organization.attendance-statuses.edit', $this->status))->assertStatus(200);

        // Anggota dedicated routes
        $this->actingAs($this->superadmin)->get(route('personnel.anggota.create'))->assertStatus(200);
        $this->actingAs($this->superadmin)->get(route('personnel.anggota.edit', $this->anggota))->assertStatus(200);

        // Attendance dedicated create route
        $this->actingAs($this->superadmin)->get(route('attendance.create'))->assertStatus(200);

        // Calendar Event dedicated create & edit routes
        $this->actingAs($this->superadmin)->get(route('calendar.events.create'))->assertStatus(200);
        $event = CalendarEvent::create([
            'tajuk' => 'Mesyuarat Bahagian',
            'jenis' => 'MESYUARAT',
            'mula' => '2026-10-28 09:00:00',
            'tamat' => '2026-10-28 11:00:00',
            'sepanjang_hari' => false,
            'created_by' => $this->superadmin->id,
            'updated_by' => $this->superadmin->id,
        ]);
        $this->actingAs($this->superadmin)->get(route('calendar.events.edit', $event))->assertStatus(200);

        // Tier 3 Dedicated Pages (10+ fields / complex administration)
        $this->actingAs($this->superadmin)->get(route('users.create'))->assertStatus(200);
        $this->actingAs($this->superadmin)->get(route('users.edit', $this->superadmin))->assertStatus(200);
        $this->actingAs($this->superadmin)->get(route('profile.edit'))->assertStatus(200);
    }
}
