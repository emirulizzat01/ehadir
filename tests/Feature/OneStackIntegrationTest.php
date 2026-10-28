<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\AnggotaPenempatan;
use App\Models\AuditLog;
use App\Models\Bahagian;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Services\OneStack\Exceptions\OneStackApiException;
use App\Services\OneStack\OneStackApiClient;
use App\Services\OneStack\OneStackSyncService;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OneStackIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected string $fakeUrl = 'https://onestack.kejora.gov.my/onestack/api-v2/data_user.php';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.onestack.url', $this->fakeUrl);
        Config::set('services.onestack.key', 'TestApiKey123!');
    }

    public function test_client_throws_exception_when_api_key_is_missing(): void
    {
        Config::set('services.onestack.key', null);

        $client = new OneStackApiClient();

        $this->expectException(OneStackApiException::class);
        $this->expectExceptionMessage('OneStack API key is not configured');

        $client->fetchAllUsers();
    }

    public function test_client_fetches_all_users_successfully(): void
    {
        Http::fake([
            $this->fakeUrl.'*' => Http::response([
                'status' => true,
                'message' => 'Rekod berjaya didapatkan.',
                'data' => [
                    [
                        'id' => 10,
                        'id_pengguna' => '1010 A',
                        'nama_pengguna' => 'Ahmad Kasim',
                        'jawatan' => 'Pegawai IT',
                        'bahagian' => 'BAHAGIAN TEKNOLOGI MAKLUMAT',
                        'kod_bahagian' => 'TM',
                        'status_masih_berkhidmat' => 1,
                    ],
                ],
            ], 200),
        ]);

        $client = new OneStackApiClient();
        $users = $client->fetchAllUsers();

        $this->assertCount(1, $users);
        $this->assertEquals('1010 A', $users[0]['id_pengguna']);
    }

    public function test_client_fetches_single_user_successfully(): void
    {
        Http::fake([
            $this->fakeUrl.'*id_pengguna=*' => Http::response([
                'status' => true,
                'message' => 'Rekod berjaya didapatkan.',
                'data' => [
                    'id' => 10,
                    'id_pengguna' => '1010 A',
                    'nama_pengguna' => 'Ahmad Kasim',
                    'jawatan' => 'Pegawai IT',
                    'bahagian' => 'BAHAGIAN TEKNOLOGI MAKLUMAT',
                    'kod_bahagian' => 'TM',
                    'status_masih_berkhidmat' => 1,
                ],
            ], 200),
        ]);

        $client = new OneStackApiClient();
        $user = $client->fetchUserById('1010 A');

        $this->assertNotNull($user);
        $this->assertEquals('1010 A', $user['id_pengguna']);
        $this->assertEquals('Ahmad Kasim', $user['nama_pengguna']);
    }

    public function test_client_handles_unauthorized_error(): void
    {
        Http::fake([
            $this->fakeUrl.'*' => Http::response(['message' => 'Unauthorized key'], 401),
        ]);

        $client = new OneStackApiClient();

        $this->expectException(OneStackApiException::class);
        $this->expectExceptionMessage('HTTP 401');

        $client->fetchAllUsers();
    }

    public function test_sync_service_upserts_anggota_and_maps_bahagian_correctly(): void
    {
        $rawRecords = [
            [
                'id' => 20,
                'id_pengguna' => '2001 B',
                'nama_pengguna' => 'HAJI ABDUL RAZAK BIN HARUN',
                'jawatan' => 'PENOLONG PEGAWAI PERANCANG       ',
                'bahagian' => 'BAHAGIAN PERANCANGAN STRATEGIK',
                'kod_bahagian' => 'PS',
                'status_masih_berkhidmat' => 1,
            ],
        ];

        $syncService = app(OneStackSyncService::class);
        $result = $syncService->syncUserCollection($rawRecords);

        $this->assertEquals(1, $result['synced']);
        $this->assertEquals(1, $result['created']);
        $this->assertEquals(0, $result['updated']);

        $anggota = Anggota::where('no_pekerja', '2001 B')->first();
        $this->assertNotNull($anggota);
        $this->assertEquals('HAJI ABDUL RAZAK BIN HARUN', $anggota->nama_penuh);
        $this->assertEquals('HAJI', $anggota->gelaran);
        $this->assertEquals('PENOLONG PEGAWAI PERANCANG', $anggota->jawatan);
        $this->assertTrue($anggota->aktif);

        // Check Bahagian
        $bahagian = Bahagian::where('kod', 'PS')->first();
        $this->assertNotNull($bahagian);
        $this->assertEquals('BAHAGIAN PERANCANGAN STRATEGIK', $bahagian->nama);

        // Check Penempatan: Must have bahagian_id, unit_id must be NULL
        $penempatan = AnggotaPenempatan::where('anggota_id', $anggota->id)->first();
        $this->assertNotNull($penempatan);
        $this->assertEquals($bahagian->id, $penempatan->bahagian_id);
        $this->assertNull($penempatan->unit_id);
        $this->assertNull($penempatan->tarikh_tamat);
    }

    public function test_sync_service_upserts_anggota_and_maps_unit_correctly(): void
    {
        $rawRecords = [
            [
                'id' => 30,
                'id_pengguna' => '3001 U',
                'nama_pengguna' => 'SITI NURHALIZA',
                'jawatan' => 'PEGAWAI INTEGRITI',
                'bahagian' => 'UNIT INTEGRITI',
                'kod_bahagian' => 'UI',
                'status_masih_berkhidmat' => 1,
            ],
        ];

        $syncService = app(OneStackSyncService::class);
        $result = $syncService->syncUserCollection($rawRecords);

        $this->assertEquals(1, $result['synced']);

        $anggota = Anggota::where('no_pekerja', '3001 U')->first();
        $this->assertNotNull($anggota);

        // Check Unit
        $unit = Unit::where('kod', 'UI')->first();
        $this->assertNotNull($unit);
        $this->assertEquals('UNIT INTEGRITI', $unit->nama);

        // Check Penempatan: Must have unit_id, bahagian_id must be NULL
        $penempatan = AnggotaPenempatan::where('anggota_id', $anggota->id)->first();
        $this->assertNotNull($penempatan);
        $this->assertEquals($unit->id, $penempatan->unit_id);
        $this->assertNull($penempatan->bahagian_id);
    }

    public function test_sync_service_handles_staff_transfer_correctly(): void
    {
        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        // 1. Initial sync to Bahagian Kewangan
        $initialRecord = [
            [
                'id' => 40,
                'id_pengguna' => '4001 K',
                'nama_pengguna' => 'FAIZAL BIN AHMAD',
                'jawatan' => 'AKAUNTAN',
                'bahagian' => 'BAHAGIAN KEWANGAN',
                'kod_bahagian' => 'KEW',
                'status_masih_berkhidmat' => 1,
            ],
        ];

        $syncService = app(OneStackSyncService::class);
        $syncService->syncUserCollection($initialRecord);

        $anggota = Anggota::where('no_pekerja', '4001 K')->first();
        $this->assertCount(1, $anggota->penempatan);
        $initialPenempatan = $anggota->penempatan->first();
        $initialPenempatan->update(['tarikh_mula' => '2026-01-01']);
        $this->assertNull($initialPenempatan->tarikh_tamat);

        // 2. Transfer staff to Bahagian Hartanah
        $transferRecord = [
            [
                'id' => 40,
                'id_pengguna' => '4001 K',
                'nama_pengguna' => 'FAIZAL BIN AHMAD',
                'jawatan' => 'PEGAWAI TADBIR',
                'bahagian' => 'BAHAGIAN HARTANAH',
                'kod_bahagian' => 'HT',
                'status_masih_berkhidmat' => 1,
            ],
        ];

        $result = $syncService->syncUserCollection($transferRecord);
        $this->assertEquals(1, $result['transferred']);

        $placements = AnggotaPenempatan::where('anggota_id', $anggota->id)->orderBy('tarikh_mula')->get();
        $this->assertCount(2, $placements);

        // Old placement closed yesterday (or today if start was today)
        $oldP = $placements[0];
        $this->assertNotNull($oldP->tarikh_tamat);

        // New placement active
        $newP = $placements[1];
        $this->assertEquals(Bahagian::where('kod', 'HT')->first()->id, $newP->bahagian_id);
        $this->assertNull($newP->tarikh_tamat);
    }

    public function test_sync_service_handles_deactivation_and_closes_placement(): void
    {
        $records = [
            [
                'id' => 50,
                'id_pengguna' => '5001 P',
                'nama_pengguna' => 'PESARA ABDULLAH',
                'jawatan' => 'PEGAWAI KANAN',
                'bahagian' => 'BAHAGIAN KEWANGAN',
                'kod_bahagian' => 'KEW',
                'status_masih_berkhidmat' => 1,
            ],
        ];

        $syncService = app(OneStackSyncService::class);
        $syncService->syncUserCollection($records);

        $anggota = Anggota::where('no_pekerja', '5001 P')->first();
        $this->assertTrue($anggota->aktif);

        // Now deactive (status_masih_berkhidmat = 0)
        $records[0]['status_masih_berkhidmat'] = 0;
        $syncService->syncUserCollection($records);

        $anggota->refresh();
        $this->assertFalse($anggota->aktif);

        $placement = AnggotaPenempatan::where('anggota_id', $anggota->id)->first();
        $this->assertNotNull($placement->tarikh_tamat);
    }

    public function test_sync_service_skips_dummy_records(): void
    {
        $records = [
            [
                'id_pengguna' => '888888',
                'nama_pengguna' => 'DEMO',
                'status_masih_berkhidmat' => 0,
            ],
            [
                'id_pengguna' => '777777',
                'nama_pengguna' => 'dummy test user',
                'status_masih_berkhidmat' => 0,
            ],
            [
                'id_pengguna' => 'VALID01',
                'nama_pengguna' => 'ZULKIFLI BIN ISMAIL',
                'bahagian' => 'BAHAGIAN TEKNOLOGI MAKLUMAT',
                'kod_bahagian' => 'TM',
                'status_masih_berkhidmat' => 1,
            ],
        ];

        $syncService = app(OneStackSyncService::class);
        $result = $syncService->syncUserCollection($records);

        $this->assertEquals(2, $result['skipped_dummies']);
        $this->assertEquals(1, $result['synced']);
        $this->assertDatabaseHas('anggota', ['no_pekerja' => 'VALID01']);
        $this->assertDatabaseMissing('anggota', ['no_pekerja' => '888888']);
    }

    public function test_artisan_command_sync_all_and_dry_run(): void
    {
        Http::fake([
            $this->fakeUrl.'*' => Http::response([
                'status' => true,
                'message' => 'Rekod berjaya didapatkan.',
                'data' => [
                    [
                        'id_pengguna' => 'CMD01',
                        'nama_pengguna' => 'CMD TEST STAFF',
                        'jawatan' => 'OPERATOR',
                        'bahagian' => 'BAHAGIAN KEWANGAN',
                        'kod_bahagian' => 'KEW',
                        'status_masih_berkhidmat' => 1,
                    ],
                ],
            ], 200),
        ]);

        // 1. Dry run
        $this->artisan('onestack:sync-users --dry-run')
            ->expectsOutputToContain('Mod Simulasi (DRY-RUN) Aktif')
            ->expectsOutputToContain('Berjaya Diselaraskan')
            ->assertSuccessful();

        $this->assertDatabaseMissing('anggota', ['no_pekerja' => 'CMD01']);

        // 2. Real sync
        $this->artisan('onestack:sync-users')
            ->expectsOutputToContain('Anggota Baharu Ditambah')
            ->assertSuccessful();

        $this->assertDatabaseHas('anggota', ['no_pekerja' => 'CMD01']);
        $this->assertDatabaseHas('audit_logs', ['tindakan' => 'SYNC_ONESTACK']);
    }

    public function test_artisan_command_sync_single_user(): void
    {
        Http::fake([
            $this->fakeUrl.'*id_pengguna=CMD02*' => Http::response([
                'status' => true,
                'message' => 'Rekod berjaya didapatkan.',
                'data' => [
                    'id_pengguna' => 'CMD02',
                    'nama_pengguna' => 'SINGLE USER SYNC',
                    'jawatan' => 'JURUTERA',
                    'bahagian' => 'BAHAGIAN PEMBANGUNAN PROJEK',
                    'kod_bahagian' => 'BPP',
                    'status_masih_berkhidmat' => 1,
                ],
            ], 200),
        ]);

        $this->artisan('onestack:sync-users --id=CMD02')
            ->expectsOutputToContain('Memulakan penyegerakan untuk ID: CMD02')
            ->expectsOutputToContain('Anggota Baharu Ditambah')
            ->assertSuccessful();

        $this->assertDatabaseHas('anggota', ['no_pekerja' => 'CMD02']);
    }

    public function test_sync_service_maps_new_onestack_fields_properly(): void
    {
        $rawRecords = [
            [
                'id' => 250,
                'id_pengguna' => '1827 I',
                'nama_pengguna' => 'EMIRUL IZZAT BIN EDON',
                'jawatan' => 'PEN. PEG. TEKNOLOGI MAKLUMAT',
                'gred' => 'FA5',
                'emel_pengguna' => 'izzat@kejora.gov.my',
                'no_telefon' => '0193066524',
                'bahagian' => 'BAHAGIAN TEKNOLOGI MAKLUMAT',
                'kod_bahagian' => 'TM',
                'status_masih_berkhidmat' => 1,
                'tarikh_tamat_berkhidmat' => '2050-01-01',
            ],
        ];

        $syncService = app(OneStackSyncService::class);
        $result = $syncService->syncUserCollection($rawRecords);

        $this->assertEquals(1, $result['synced']);

        $anggota = Anggota::where('no_pekerja', '1827 I')->first();
        $this->assertNotNull($anggota);
        $this->assertEquals(250, $anggota->onestack_id);
        $this->assertEquals('FA5', $anggota->gred);
        $this->assertEquals('izzat@kejora.gov.my', $anggota->emel);
        $this->assertEquals('0193066524', $anggota->no_telefon);
        $this->assertEquals('2050-01-01', $anggota->tarikh_bersara->toDateString());
        $this->assertNotNull($anggota->last_synced_at);
    }

    public function test_web_routes_for_onestack_sync_and_single_refresh(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $superAdminRole = Role::where('kod', 'SUPERADMIN')->first();

        $superadmin = User::create([
            'nama' => 'Admin Utama',
            'email' => 'super@kejora.gov.my',
            'password_hash' => Hash::make('secret123'),
            'aktif' => true,
        ]);
        $superadmin->roles()->attach($superAdminRole);

        Http::fake([
            $this->fakeUrl.'*' => Http::response([
                'status' => true,
                'message' => 'Rekod berjaya didapatkan.',
                'data' => [
                    [
                        'id' => 300,
                        'id_pengguna' => 'WEB01',
                        'nama_pengguna' => 'WEB TEST STAFF',
                        'jawatan' => 'PEGAWAI SISTEM',
                        'gred' => 'F41',
                        'emel_pengguna' => 'web@kejora.gov.my',
                        'bahagian' => 'BAHAGIAN TEKNOLOGI MAKLUMAT',
                        'kod_bahagian' => 'TM',
                        'status_masih_berkhidmat' => 1,
                    ],
                ],
            ], 200),
        ]);

        // 1. Full sync via web POST
        $response = $this->actingAs($superadmin)->post(route('personnel.anggota.sync-onestack'));
        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('anggota', [
            'no_pekerja' => 'WEB01',
            'gred' => 'F41',
            'emel' => 'web@kejora.gov.my',
        ]);

        $anggota = Anggota::where('no_pekerja', 'WEB01')->first();

        // 2. Single refresh via web POST
        $refreshResponse = $this->actingAs($superadmin)->post(route('personnel.anggota.refresh-onestack', $anggota));
        $refreshResponse->assertRedirect();
        $refreshResponse->assertSessionHas('success');

        // 3. Verify directory index and show view display OneStack data
        $indexResponse = $this->actingAs($superadmin)->get(route('personnel.anggota.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('OneStack');
        $indexResponse->assertSee('Gred F41');
        $indexResponse->assertSee('web@kejora.gov.my');

        $showResponse = $this->actingAs($superadmin)->get(route('personnel.anggota.show', $anggota));
        $showResponse->assertOk();
        $showResponse->assertSee('Data Induk OneStack');
        $showResponse->assertSee('Gred F41');
        $showResponse->assertSee('web@kejora.gov.my');
    }
}
