<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\AnggotaPenempatan;
use App\Models\Bahagian;
use App\Models\CalendarEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserScope;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CalendarIcsFeedTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $scopedAdmin;

    protected Bahagian $bahagianA;

    protected Bahagian $bahagianB;

    protected Unit $unitA;

    protected Unit $unitB;

    protected Anggota $anggotaA;

    protected Anggota $anggotaB;

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

        $this->scopedAdmin = User::create([
            'nama' => 'Pegawai Bahagian A',
            'email' => 'pegawai.a@test.gov.my',
            'password_hash' => Hash::make('Password@123'),
            'aktif' => true,
        ]);
        $this->scopedAdmin->roles()->attach($roleAdmin);

        $this->bahagianA = Bahagian::create(['nama' => 'Bahagian Teknologi Maklumat', 'kod' => 'BTM', 'aktif' => true]);
        $this->bahagianB = Bahagian::create(['nama' => 'Bahagian Kewangan', 'kod' => 'BKW', 'aktif' => true]);

        $this->unitA = Unit::create(['nama' => 'Unit Web', 'kod' => 'UW', 'aktif' => true]);
        $this->unitB = Unit::create(['nama' => 'Unit Belanjawan', 'kod' => 'UB', 'aktif' => true]);

        // Scope regular admin strictly to Bahagian A
        UserScope::create([
            'user_id' => $this->scopedAdmin->id,
            'bahagian_id' => $this->bahagianA->id,
            'unit_id' => null,
        ]);

        $this->anggotaA = Anggota::create([
            'nama_penuh' => 'Siti Aishah',
            'no_kad_pengenalan' => '950101011111',
            'email' => 'aishah@test.gov.my',
            'aktif' => true,
        ]);
        AnggotaPenempatan::create([
            'anggota_id' => $this->anggotaA->id,
            'bahagian_id' => $this->bahagianA->id,
            'unit_id' => $this->unitA->id,
            'tarikh_mula' => '2026-01-01',
            'tarikh_tamat' => null,
            'catatan' => 'Penempatan A',
        ]);

        $this->anggotaB = Anggota::create([
            'nama_penuh' => 'Mohd Azman',
            'no_kad_pengenalan' => '920202022222',
            'email' => 'azman@test.gov.my',
            'aktif' => true,
        ]);
        AnggotaPenempatan::create([
            'anggota_id' => $this->anggotaB->id,
            'bahagian_id' => $this->bahagianB->id,
            'unit_id' => $this->unitB->id,
            'tarikh_mula' => '2026-01-01',
            'tarikh_tamat' => null,
            'catatan' => 'Penempatan B',
        ]);
    }

    public function test_user_can_retrieve_calendar_feed_url_and_token(): void
    {
        $token = $this->superAdmin->getOrCreateCalendarFeedToken();

        $this->assertNotNull($token);
        $this->assertEquals(64, strlen($token));
        $this->assertTrue(ctype_xdigit($token));

        $feedUrl = $this->superAdmin->getCalendarFeedUrl();
        $this->assertStringContainsString('/calendar/feed/'.$token.'.ics', $feedUrl);
    }

    public function test_feed_endpoint_with_invalid_token_returns_404(): void
    {
        // Bad format
        $response1 = $this->get('/calendar/feed/short-invalid.ics');
        $response1->assertNotFound();

        // 64 non-hex characters
        $response2 = $this->get('/calendar/feed/'.str_repeat('z', 64).'.ics');
        $response2->assertNotFound();

        // 64 hex characters but non-existent token
        $response3 = $this->get('/calendar/feed/'.str_repeat('0', 64).'.ics');
        $response3->assertNotFound();
    }

    public function test_feed_endpoint_with_valid_token_returns_rfc5545_calendar(): void
    {
        $token = $this->superAdmin->getOrCreateCalendarFeedToken();

        $event = CalendarEvent::create([
            'tajuk' => 'Mesyuarat Penyelarasan Sistem eHadir',
            'jenis' => 'MESYUARAT',
            'mula' => Carbon::parse('2026-10-30 09:30:00'),
            'tamat' => Carbon::parse('2026-10-30 11:30:00'),
            'sepanjang_hari' => false,
            'lokasi' => 'Bilik Mesyuarat Utama, Aras 4',
            'catatan' => 'Perbincangan pelancaran modul kalendar Google Calendar.',
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);
        $event->attendees()->attach([$this->anggotaA->id]);

        $response = $this->get('/calendar/feed/'.$token.'.ics');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');

        $content = $response->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $content);
        $this->assertStringContainsString('VERSION:2.0', $content);
        $this->assertStringContainsString('PRODID:-//eHadir//Sistem Kehadiran Organisasi v5.0//MS', $content);
        $this->assertStringContainsString('BEGIN:VTIMEZONE', $content);
        $this->assertStringContainsString('TZID:Asia/Kuala_Lumpur', $content);
        $this->assertStringContainsString('BEGIN:VEVENT', $content);
        $this->assertStringContainsString('UID:ehadir-event-'.$event->id.'@ehadir.gov.my', $content);
        $this->assertStringContainsString('SUMMARY:Mesyuarat Penyelarasan Sistem eHadir', $content);
        $this->assertStringContainsString('LOCATION:Bilik Mesyuarat Utama\, Aras 4', $content);
        $this->assertStringContainsString('CATEGORIES:Mesyuarat', $content);
        $this->assertStringContainsString('DTSTART;TZID=Asia/Kuala_Lumpur:20261030T093000', $content);
        $this->assertStringContainsString('DTEND;TZID=Asia/Kuala_Lumpur:20261030T113000', $content);
        $this->assertStringContainsString('END:VEVENT', $content);
        $this->assertStringContainsString('END:VCALENDAR', $content);
    }

    public function test_feed_strictly_respects_user_scope_invariants(): void
    {
        // Event in Bahagian A (attended by Anggota A)
        $eventA = CalendarEvent::create([
            'tajuk' => 'Bengkel Pembangunan Web Bahagian A',
            'jenis' => 'BENGKEL',
            'mula' => Carbon::parse('2026-11-05 09:00:00'),
            'tamat' => Carbon::parse('2026-11-05 17:00:00'),
            'sepanjang_hari' => false,
            'lokasi' => 'Makmal ICT Bahagian A',
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);
        $eventA->attendees()->attach($this->anggotaA->id);

        // Event in Bahagian B (attended by Anggota B, not accessible to Scoped Admin A)
        $eventB = CalendarEvent::create([
            'tajuk' => 'Taklimat Kewangan Bahagian B',
            'jenis' => 'KURSUS',
            'mula' => Carbon::parse('2026-11-06 10:00:00'),
            'tamat' => Carbon::parse('2026-11-06 12:00:00'),
            'sepanjang_hari' => false,
            'lokasi' => 'Dewan Kewangan',
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);
        $eventB->attendees()->attach($this->anggotaB->id);

        $tokenA = $this->scopedAdmin->getOrCreateCalendarFeedToken();

        $response = $this->get('/calendar/feed/'.$tokenA.'.ics');
        $response->assertOk();

        $content = $response->getContent();
        // Scoped admin MUST see Event A (within their department scope)
        $this->assertStringContainsString('Bengkel Pembangunan Web Bahagian A', $content);
        // Scoped admin MUST NOT see Event B (outside their department scope)
        $this->assertStringNotContainsString('Taklimat Kewangan Bahagian B', $content);
    }

    public function test_user_can_regenerate_token_to_revoke_old_feed_link(): void
    {
        $oldToken = $this->superAdmin->getOrCreateCalendarFeedToken();

        // Regenerate token via POST endpoint
        $response = $this->actingAs($this->superAdmin)->post(route('calendar.feed.regenerate'));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $newToken = $this->superAdmin->fresh()->calendar_feed_token;
        $this->assertNotEquals($oldToken, $newToken);

        // Old token is immediately revoked (returns 404)
        $this->get('/calendar/feed/'.$oldToken.'.ics')->assertNotFound();

        // New token works cleanly
        $this->get('/calendar/feed/'.$newToken.'.ics')->assertOk();
    }

    public function test_single_event_can_be_exported_as_ics(): void
    {
        $event = CalendarEvent::create([
            'tajuk' => 'Lawatan Tapak Fasiliti Server',
            'jenis' => 'LAWATAN_TAPAK',
            'mula' => Carbon::parse('2026-11-12 14:00:00'),
            'tamat' => Carbon::parse('2026-11-12 16:00:00'),
            'sepanjang_hari' => false,
            'lokasi' => 'Pusat Data Sektor Awam',
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('calendar.events.export-ics', $event));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Lawatan Tapak Fasiliti Server', $response->getContent());
    }

    public function test_google_calendar_url_attribute_generates_valid_intent_url(): void
    {
        $event = CalendarEvent::create([
            'tajuk' => 'Mesyuarat Jawatankuasa Pemandu',
            'jenis' => 'MESYUARAT',
            'mula' => Carbon::parse('2026-10-27 10:00:00'),
            'tamat' => Carbon::parse('2026-10-27 12:00:00'),
            'sepanjang_hari' => false,
            'lokasi' => 'Bilik Mesyuarat Utama',
            'catatan' => 'Agenda perbincangan fasa seterusnya.',
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $url = $event->google_calendar_url;

        $this->assertStringStartsWith('https://calendar.google.com/calendar/render?', $url);
        $this->assertStringContainsString('action=TEMPLATE', $url);
        $this->assertStringContainsString('text=Mesyuarat+Jawatankuasa+Pemandu', $url);
        $this->assertStringContainsString('location=Bilik+Mesyuarat+Utama', $url);
        $this->assertStringContainsString('dates=', $url);
    }
}
