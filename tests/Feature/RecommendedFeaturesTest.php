<?php

namespace Tests\Feature;

use App\Models\Bahagian;
use App\Models\Role;
use App\Models\StatusKehadiran;
use App\Models\User;
use App\Models\UserScope;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StatusKehadiranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RecommendedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(StatusKehadiranSeeder::class);

        $bahagian = Bahagian::create(['kod' => 'BTM', 'nama' => 'Bahagian Teknologi Maklumat', 'aktif' => true]);

        $this->superadmin = User::create([
            'nama' => 'Superadmin Utama',
            'email' => 'super@ehadir.gov.my',
            'password_hash' => Hash::make('Password@123'),
            'aktif' => true,
        ]);
        $this->superadmin->roles()->attach(Role::where('kod', 'SUPERADMIN')->first());

        $this->admin = User::create([
            'nama' => 'Admin Bertugas',
            'email' => 'admin@ehadir.gov.my',
            'password_hash' => Hash::make('Password@123'),
            'aktif' => true,
        ]);
        $this->admin->roles()->attach(Role::where('kod', 'ADMIN')->first());
        UserScope::create([
            'user_id' => $this->admin->id,
            'bahagian_id' => $bahagian->id,
        ]);
    }

    public function test_user_can_view_and_update_profile(): void
    {
        $this->actingAs($this->admin)->get(route('profile.edit'))->assertStatus(200);

        $response = $this->actingAs($this->admin)->put(route('profile.update'), [
            'nama' => 'Admin Nama Baru',
            'email' => 'admin_baru@ehadir.gov.my',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'nama' => 'Admin Nama Baru',
            'email' => 'admin_baru@ehadir.gov.my',
        ]);
    }

    public function test_user_can_update_password(): void
    {
        $response = $this->actingAs($this->admin)->put(route('profile.password'), [
            'current_password' => 'Password@123',
            'password' => 'NewPassword@456',
            'password_confirmation' => 'NewPassword@456',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->admin->refresh();
        $this->assertTrue(Hash::check('NewPassword@456', $this->admin->password_hash));
    }

    public function test_user_cannot_update_password_with_incorrect_current_password(): void
    {
        $response = $this->actingAs($this->admin)->put(route('profile.password'), [
            'current_password' => 'WrongPassword',
            'password' => 'NewPassword@456',
            'password_confirmation' => 'NewPassword@456',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->admin->refresh();
        $this->assertTrue(Hash::check('Password@123', $this->admin->password_hash));
    }

    public function test_superadmin_can_manage_attendance_statuses(): void
    {
        // View index
        $this->actingAs($this->superadmin)->get(route('organization.attendance-statuses.index'))->assertStatus(200);

        // Create new status
        $response = $this->actingAs($this->superadmin)->post(route('organization.attendance-statuses.store'), [
            'kod' => 'KURSUS_RASMI',
            'nama' => 'Kursus / Latihan Rasmi',
            'warna' => '#0D9488',
            'susunan' => 6,
            'aktif' => 1,
        ]);

        $response->assertRedirect(route('organization.attendance-statuses.index'));
        $this->assertDatabaseHas('status_kehadiran', [
            'kod' => 'KURSUS_RASMI',
            'warna' => '#0D9488',
        ]);

        $status = StatusKehadiran::where('kod', 'KURSUS_RASMI')->first();

        // Edit status
        $this->actingAs($this->superadmin)->get(route('organization.attendance-statuses.edit', $status))->assertStatus(200);

        $this->actingAs($this->superadmin)->put(route('organization.attendance-statuses.update', $status), [
            'kod' => 'KURSUS_RASMI',
            'nama' => 'Kursus Rasmi Jabatan',
            'warna' => '#14B8A6',
            'susunan' => 6,
            'aktif' => 1,
        ]);

        $this->assertDatabaseHas('status_kehadiran', [
            'kod' => 'KURSUS_RASMI',
            'nama' => 'Kursus Rasmi Jabatan',
            'warna' => '#14B8A6',
        ]);
    }

    public function test_scoped_admin_is_forbidden_from_configuring_statuses(): void
    {
        $this->actingAs($this->admin)->get(route('organization.attendance-statuses.index'))->assertStatus(403);
    }

    public function test_daily_and_monthly_print_views_are_accessible(): void
    {
        $this->actingAs($this->superadmin)->get(route('reports.daily.print'))->assertStatus(200);
        $this->actingAs($this->superadmin)->get(route('reports.monthly.print'))->assertStatus(200);
    }

    public function test_dashboard_renders_fluid_full_width_layout_and_evaluates_metrics(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('dashboard'));

        $response->assertOk();
        // Verify fluid full-width layout classes
        $response->assertSee('class="flex-1 p-4 sm:p-6 lg:p-8 w-full"', false);
        $response->assertDontSee('max-w-7xl');

        // Verify KPI card description is evaluated and does not contain raw curly braces
        $response->assertSee('Kadar Kehadiran:');
        $response->assertDontSee('{$presenceRate}');
    }
}
