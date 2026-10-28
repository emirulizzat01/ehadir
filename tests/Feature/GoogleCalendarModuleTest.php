<?php

namespace Tests\Feature;

use App\Livewire\Calendar\CalendarView;
use App\Models\Anggota;
use App\Models\AnggotaPenempatan;
use App\Models\Bahagian;
use App\Models\CalendarEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class GoogleCalendarModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $regularUser;

    protected Bahagian $bahagian;

    protected Unit $unit;

    protected Anggota $anggota;

    protected function setUp(): void
    {
        parent::setUp();

        $roleSuper = Role::create(['nama' => 'Superadmin', 'kod' => 'SUPERADMIN', 'aktif' => true]);
        $roleAdmin = Role::create(['nama' => 'Pentadbir', 'kod' => 'ADMIN', 'aktif' => true]);

        $permCalView = Permission::create(['nama' => 'Lihat Kalendar', 'kod' => 'calendar.view']);
        $permCalManage = Permission::create(['nama' => 'Urus Kalendar', 'kod' => 'calendar.manage']);
        $roleAdmin->permissions()->attach([$permCalView->id, $permCalManage->id]);

        $this->superAdmin = User::create([
            'nama' => 'Superadmin Test',
            'email' => 'superadmin@test.gov.my',
            'password_hash' => Hash::make('Password@123'),
            'aktif' => true,
        ]);
        $this->superAdmin->roles()->attach($roleSuper);

        $this->regularUser = User::create([
            'nama' => 'Admin Test',
            'email' => 'admin@test.gov.my',
            'password_hash' => Hash::make('Password@123'),
            'aktif' => true,
        ]);
        $this->regularUser->roles()->attach($roleAdmin);

        $this->bahagian = Bahagian::create(['nama' => 'Bahagian Pengurusan Maklumat', 'kod' => 'BPM', 'aktif' => true]);
        $this->unit = Unit::create(['nama' => 'Unit Aplikasi', 'kod' => 'UA', 'aktif' => true]);

        $this->anggota = Anggota::create([
            'nama_penuh' => 'Ahmad Albab',
            'no_kad_pengenalan' => '900101015555',
            'email' => 'ahmad@test.gov.my',
            'aktif' => true,
        ]);

        AnggotaPenempatan::create([
            'anggota_id' => $this->anggota->id,
            'bahagian_id' => $this->bahagian->id,
            'unit_id' => null,
            'tarikh_mula' => '2026-01-01',
            'tarikh_tamat' => null,
            'catatan' => 'Penempatan Awal',
        ]);
    }

    public function test_user_with_permission_can_access_calendar_page(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('calendar.index'));

        $response->assertOk();
        $response->assertSeeLivewire(CalendarView::class);
        $response->assertSee('Kalendar & Aktiviti');
        $response->assertSee('Bulan');
        $response->assertSee('Minggu');
        $response->assertSee('Hari');
        $response->assertSee('Agenda');
    }

    public function test_user_without_permission_cannot_access_calendar(): void
    {
        $unauthorizedUser = User::create([
            'nama' => 'No Perm User',
            'email' => 'noperm@test.gov.my',
            'password_hash' => Hash::make('Password@123'),
            'aktif' => true,
        ]);

        $response = $this->actingAs($unauthorizedUser)->get(route('calendar.index'));

        $response->assertForbidden();
    }

    public function test_calendar_livewire_can_switch_views(): void
    {
        Livewire::actingAs($this->regularUser)
            ->test(CalendarView::class)
            ->assertSet('view', 'month')
            ->call('setView', 'week')
            ->assertSet('view', 'week')
            ->assertSee('Masa')
            ->call('setView', 'day')
            ->assertSet('view', 'day')
            ->assertSee('aktiviti dijadualkan')
            ->call('setView', 'agenda')
            ->assertSet('view', 'agenda')
            ->assertSee('Jadual Agenda Aktiviti')
            ->call('setView', 'month')
            ->assertSet('view', 'month');
    }

    public function test_calendar_livewire_date_navigation_and_jump(): void
    {
        $today = now()->toDateString();
        $nextMonth = now()->copy()->addMonth()->startOfMonth()->toDateString();

        Livewire::actingAs($this->regularUser)
            ->test(CalendarView::class, ['initialDate' => $today])
            ->assertSet('currentDate', $today)
            ->call('nextPeriod')
            ->assertSet('currentDate', now()->copy()->addMonth()->toDateString())
            ->call('previousPeriod')
            ->assertSet('currentDate', $today)
            ->call('selectDate', '2026-12-25', 'day')
            ->assertSet('currentDate', '2026-12-25')
            ->assertSet('view', 'day')
            ->call('goToToday')
            ->assertSet('currentDate', $today);
    }

    public function test_calendar_livewire_category_filtering_and_search(): void
    {
        $event1 = CalendarEvent::create([
            'tajuk' => 'Mesyuarat Bajet ICT 2026',
            'jenis' => 'MESYUARAT',
            'mula' => Carbon::parse('2026-10-15 09:00:00'),
            'tamat' => Carbon::parse('2026-10-15 11:00:00'),
            'sepanjang_hari' => false,
            'lokasi' => 'Bilik Gerakan',
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);
        $event1->attendees()->attach($this->anggota->id);

        $event2 = CalendarEvent::create([
            'tajuk' => 'Kursus Keselamatan Siber',
            'jenis' => 'KURSUS',
            'mula' => Carbon::parse('2026-10-20 09:00:00'),
            'tamat' => Carbon::parse('2026-10-20 17:00:00'),
            'sepanjang_hari' => false,
            'lokasi' => 'Makmal Komputer',
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);
        $event2->attendees()->attach($this->anggota->id);

        Livewire::actingAs($this->superAdmin)
            ->test(CalendarView::class, ['initialDate' => '2026-10-15'])
            ->assertSee('Mesyuarat Bajet ICT 2026')
            ->assertSee('Kursus Keselamatan Siber')
            // Deselect KURSUS
            ->call('toggleType', 'KURSUS')
            ->assertSee('Mesyuarat Bajet ICT 2026')
            ->assertDontSee('Kursus Keselamatan Siber')
            // Search filter
            ->call('toggleType', 'KURSUS')
            ->set('search', 'Bajet')
            ->assertSee('Mesyuarat Bajet ICT 2026')
            ->assertDontSee('Kursus Keselamatan Siber');
    }

    public function test_calendar_quick_view_popover_modal(): void
    {
        $event = CalendarEvent::create([
            'tajuk' => 'Bengkel AI Generatif Sektor Awam',
            'jenis' => 'BENGKEL',
            'mula' => Carbon::parse('2026-10-27 10:00:00'),
            'tamat' => Carbon::parse('2026-10-27 12:00:00'),
            'sepanjang_hari' => false,
            'lokasi' => 'Dewan Serbaguna',
            'catatan' => 'Sila bawa komputer riba masing-masing.',
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);
        $event->attendees()->attach($this->anggota->id);

        Livewire::actingAs($this->superAdmin)
            ->test(CalendarView::class, ['initialDate' => '2026-10-27'])
            ->assertSet('activeEventId', null)
            ->call('openQuickView', $event->id)
            ->assertSet('activeEventId', $event->id)
            ->assertSee('Bengkel AI Generatif Sektor Awam')
            ->assertSee('Dewan Serbaguna')
            ->assertSee('Sila bawa komputer riba masing-masing.')
            ->assertSee('Ahmad Albab')
            ->assertSee(route('calendar.show', $event))
            ->assertSee(route('calendar.events.edit', $event))
            ->call('closeQuickView')
            ->assertSet('activeEventId', null);
    }

    public function test_create_event_page_prefills_date_and_time(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('calendar.events.create', [
            'date' => '2026-11-10',
            'time' => '14:00',
        ]));

        $response->assertOk();
        $response->assertSee('2026-11-10T14:00');
        $response->assertSee('2026-11-10T16:00');
    }

    public function test_show_event_page_has_link_to_attendance_roster(): void
    {
        $event = CalendarEvent::create([
            'tajuk' => 'Lawatan Audit Integriti',
            'jenis' => 'LAWATAN_TAPAK',
            'mula' => Carbon::parse('2026-10-27 09:00:00'),
            'tamat' => Carbon::parse('2026-10-27 11:00:00'),
            'sepanjang_hari' => false,
            'lokasi' => 'Tapak Projek Presint 4',
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->regularUser)->get(route('calendar.show', $event));

        $response->assertOk();
        $response->assertSee('Lawatan Audit Integriti');
        $response->assertSee(route('attendance.index', ['date' => '2026-10-27']));
    }
}
