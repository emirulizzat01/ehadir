<?php

namespace Tests\Feature;

use App\Livewire\Tv\TvDashboardView;
use App\Models\Anggota;
use App\Models\AnggotaPenempatan;
use App\Models\Bahagian;
use App\Models\CalendarEvent;
use App\Models\Permission;
use App\Models\RekodKehadiran;
use App\Models\Role;
use App\Models\StatusKehadiran;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserScope;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class TvDisplayModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $unauthorizedUser;

    protected Bahagian $bahagian;

    protected Unit $unit;

    protected Anggota $anggota1;

    protected Anggota $anggota2;

    protected StatusKehadiran $statusPejabat;

    protected StatusKehadiran $statusCuti;

    protected AnggotaPenempatan $penempatan1;

    protected AnggotaPenempatan $penempatan2;

    protected function setUp(): void
    {
        parent::setUp();

        $roleAdmin = Role::create(['nama' => 'Pentadbir', 'kod' => 'ADMIN', 'aktif' => true]);
        $permAttendanceView = Permission::create(['nama' => 'Lihat Kehadiran', 'kod' => 'attendance.view']);
        $roleAdmin->permissions()->attach([$permAttendanceView->id]);

        $this->adminUser = User::create([
            'nama' => 'Pegawai Pemantau',
            'email' => 'pemantau@test.gov.my',
            'password_hash' => Hash::make('Password@123'),
            'aktif' => true,
        ]);
        $this->adminUser->roles()->attach($roleAdmin);

        $this->unauthorizedUser = User::create([
            'nama' => 'User Biasa',
            'email' => 'biasa@test.gov.my',
            'password_hash' => Hash::make('Password@123'),
            'aktif' => true,
        ]);

        $this->statusPejabat = StatusKehadiran::create([
            'nama' => 'Hadir Pejabat',
            'kod' => 'PEJABAT',
            'warna' => '#10B981',
            'aktif' => true,
            'susunan' => 1,
        ]);

        $this->statusCuti = StatusKehadiran::create([
            'nama' => 'Cuti Rehat',
            'kod' => 'CUTI_REHAT',
            'warna' => '#F59E0B',
            'aktif' => true,
            'susunan' => 2,
        ]);

        $this->bahagian = Bahagian::create(['nama' => 'Bahagian Korporat', 'kod' => 'BKORP', 'aktif' => true]);
        $this->unit = Unit::create(['nama' => 'Unit Media', 'kod' => 'UM', 'aktif' => true]);

        $this->anggota1 = Anggota::create([
            'nama_penuh' => 'Ahmad Razak',
            'no_kad_pengenalan' => '880101011111',
            'email' => 'razak@test.gov.my',
            'aktif' => true,
        ]);
        $this->penempatan1 = AnggotaPenempatan::create([
            'anggota_id' => $this->anggota1->id,
            'bahagian_id' => $this->bahagian->id,
            'unit_id' => null,
            'tarikh_mula' => '2026-01-01',
            'tarikh_tamat' => null,
            'catatan' => 'Penempatan 1',
        ]);

        $this->anggota2 = Anggota::create([
            'nama_penuh' => 'Nurul Ain',
            'no_kad_pengenalan' => '910202022222',
            'email' => 'ain@test.gov.my',
            'aktif' => true,
        ]);
        $this->penempatan2 = AnggotaPenempatan::create([
            'anggota_id' => $this->anggota2->id,
            'bahagian_id' => $this->bahagian->id,
            'unit_id' => null,
            'tarikh_mula' => '2026-01-01',
            'tarikh_tamat' => null,
            'catatan' => 'Penempatan 2',
        ]);
    }

    public function test_user_with_permission_can_access_tv_display(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('tv.index'));

        $response->assertOk();
        $response->assertSeeLivewire(TvDashboardView::class);
        $response->assertSee('Pusat Pemantauan Operasi & Kehadiran');
        $response->assertSee('Kadar Kehadiran');
        $response->assertSee('Hadir Di Pejabat');
        $response->assertSee('Prestasi Kehadiran Mengikut Bahagian');
        $response->assertSee('Aktiviti & Mesyuarat Hari Ini', false);
    }

    public function test_unauthorized_user_cannot_access_tv_display(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)->get(route('tv.index'));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_redirects_to_login(): void
    {
        $response = $this->get(route('tv.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_tv_dashboard_livewire_computes_correct_metrics(): void
    {
        $testDate = '2026-10-27';

        // Anggota 1 Hadir, Anggota 2 Cuti
        RekodKehadiran::create([
            'anggota_id' => $this->anggota1->id,
            'penempatan_id' => $this->penempatan1->id,
            'status_id' => $this->statusPejabat->id,
            'tarikh' => $testDate,
            'masa_masuk' => '08:15:00',
            'sumber' => 'MANUAL',
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);

        RekodKehadiran::create([
            'anggota_id' => $this->anggota2->id,
            'penempatan_id' => $this->penempatan2->id,
            'status_id' => $this->statusCuti->id,
            'tarikh' => $testDate,
            'masa_masuk' => null,
            'sumber' => 'MANUAL',
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);

        // Create today's event
        $event = CalendarEvent::create([
            'tajuk' => 'Mesyuarat Pengurusan Pagi',
            'jenis' => 'MESYUARAT',
            'mula' => Carbon::parse($testDate.' 09:00:00'),
            'tamat' => Carbon::parse($testDate.' 11:00:00'),
            'sepanjang_hari' => false,
            'lokasi' => 'Bilik Mesyuarat Utama',
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);
        $event->attendees()->attach([$this->anggota1->id]);

        Livewire::test(TvDashboardView::class, ['date' => $testDate])
            ->assertSet('currentDate', $testDate)
            ->assertSee('50.0%') // 1 out of 2 present = 50.0%
            ->assertSee('Mesyuarat Pengurusan Pagi')
            ->assertSee('Bilik Mesyuarat Utama')
            ->assertSee('BKORP')
            ->assertSee('Bahagian Korporat');
    }

    public function test_unmanned_kiosk_route_with_valid_token_returns_200(): void
    {
        $token = $this->adminUser->getOrCreateCalendarFeedToken();

        $response = $this->get(route('tv.kiosk', ['token' => $token]));

        $response->assertOk();
        $response->assertSeeLivewire(TvDashboardView::class);
        $response->assertSee('Pusat Pemantauan Operasi & Kehadiran');
    }

    public function test_kiosk_route_with_invalid_token_returns_403(): void
    {
        $response = $this->get(route('tv.kiosk', ['token' => 'invalid-token-123']));

        $response->assertForbidden();
    }

    public function test_tv_display_includes_theme_persistence_script_and_toggle_button(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('tv.index'));

        $response->assertOk();
        // Verify anti-flash theme script
        $response->assertSee("localStorage.getItem('theme') === 'light'", false);
        $response->assertSee("document.documentElement.classList.remove('dark')", false);
        $response->assertSee("document.documentElement.classList.add('dark')", false);
        $response->assertSee("localStorage.setItem('theme', 'light')", false);
        $response->assertSee("localStorage.setItem('theme', 'dark')", false);

        // Verify theme toggler button and shortcut indication
        $response->assertSee('Tukar Mod Tema Terang/Gelap (Kekunci T)');
        $response->assertSee('handleKeyDown', false);

        // Verify dual-mode styling classes for kiosk legibility
        $response->assertSee('bg-slate-100 dark:bg-slate-950', false);
        $response->assertSee('text-slate-800 dark:text-slate-100', false);
        $response->assertSee('bg-white dark:bg-slate-900', false);
    }

    public function test_kiosk_display_includes_theme_persistence_and_controls(): void
    {
        $token = $this->adminUser->getOrCreateCalendarFeedToken();

        $response = $this->get(route('tv.kiosk', ['token' => $token]));

        $response->assertOk();
        $response->assertSee("localStorage.getItem('theme') === 'light'", false);
        $response->assertSee('Tukar Mod Tema Terang/Gelap (Kekunci T)');
        $response->assertSee('toggleFullscreen()', false);
        // Verify kiosk lockdown hides the portal return link
        $response->assertDontSee('Kembali ke Portal eHadir');
    }

    public function test_scoped_admin_automatically_defaults_to_department_mode(): void
    {
        // Create scoped user attached to BKORP
        $scopedUser = User::create([
            'nama' => 'Admin BKORP',
            'email' => 'admin.bkorp@test.gov.my',
            'password_hash' => Hash::make('Password@123'),
            'aktif' => true,
        ]);
        $scopedUser->roles()->attach(Role::where('kod', 'ADMIN')->first());

        UserScope::create([
            'user_id' => $scopedUser->id,
            'bahagian_id' => $this->bahagian->id,
        ]);

        $response = $this->actingAs($scopedUser)->get(route('tv.index'));

        $response->assertOk();
        $response->assertSeeLivewire(TvDashboardView::class);
        $response->assertSee('Paparan Operasi: Bahagian Korporat');
        $response->assertSee('Senarai Pergerakan & Status Anggota (BKORP)', false);
        $response->assertSee('Prestasi Mengikut Unit');
        $response->assertSee('UM - Unit Media');
        $response->assertSee('Ahmad Razak');
        $response->assertSee('Nurul Ain');
    }

    public function test_department_mode_displays_independent_units_breakdown(): void
    {
        $unitStaff = Anggota::create([
            'nama_penuh' => 'Kamal Unit',
            'no_kad_pengenalan' => '990303033333',
            'email' => 'kamal@test.gov.my',
            'aktif' => true,
        ]);
        $unitPlacement = AnggotaPenempatan::create([
            'anggota_id' => $unitStaff->id,
            'bahagian_id' => null,
            'unit_id' => $this->unit->id,
            'tarikh_mula' => '2026-01-01',
            'tarikh_tamat' => null,
            'catatan' => 'Unit placement',
        ]);
        RekodKehadiran::create([
            'anggota_id' => $unitStaff->id,
            'penempatan_id' => $unitPlacement->id,
            'status_id' => $this->statusPejabat->id,
            'tarikh' => '2026-10-27',
            'masa_masuk' => '08:30:00',
            'sumber' => 'MANUAL',
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(TvDashboardView::class, ['date' => '2026-10-27', 'mode' => 'department', 'bahagianId' => $this->bahagian->id])
            ->assertSee('UM - Unit Media')
            ->assertSee('100.0%')
            ->assertSee('1 Hadir')
            ->assertSee('1 Anggota');
    }

    public function test_lobby_mode_displays_service_readiness_and_masks_individual_staff(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('tv.index', ['mode' => 'lobby']));

        $response->assertOk();
        $response->assertSee('Selamat Datang — Kompleks Pentadbiran eHadir');
        $response->assertSee('BEROPERASI PENUH');
        $response->assertSee('08:00 PG - 05:00 PTG');
        $response->assertSee('Panduan Pelawat & Piagam Pelanggan', false);
        $response->assertSee('Pendaftaran Keselamatan');
        // Verify individual staff roster is strictly hidden in lobby mode
        $response->assertDontSee('Senarai Pergerakan & Status Anggota');
        $response->assertDontSee('880101011111');
    }

    public function test_livewire_component_set_mode_and_set_bahagian_for_superadmin(): void
    {
        // Add SUPERADMIN role to user
        $roleSuper = Role::create(['nama' => 'Superadmin', 'kod' => 'SUPERADMIN', 'aktif' => true]);
        $this->adminUser->roles()->attach($roleSuper);

        $testDate = '2026-10-27';

        $component = Livewire::actingAs($this->adminUser)
            ->test(TvDashboardView::class, ['date' => $testDate, 'mode' => 'executive'])
            ->assertSet('mode', 'executive')
            ->assertSee('Prestasi Kehadiran Mengikut Bahagian');

        // Switch to department mode
        $component->call('setMode', 'department')
            ->assertSet('mode', 'department')
            ->assertSee('Senarai Pergerakan', false);

        // Switch to lobby mode
        $component->call('setMode', 'lobby')
            ->assertSet('mode', 'lobby')
            ->assertSee('BEROPERASI PENUH');
    }
}
