<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\AnggotaPenempatan;
use App\Models\Bahagian;
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

class LookupEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected User $scopedAdmin;

    protected Bahagian $bahagianIT;

    protected Bahagian $bahagianKewangan;

    protected Unit $unitIntegriti;

    protected Anggota $anggotaIT;

    protected Anggota $anggotaKewangan;

    protected Anggota $anggotaUnit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(StatusKehadiranSeeder::class);

        $superAdminRole = Role::where('kod', 'SUPERADMIN')->first();
        $adminRole = Role::where('kod', 'ADMIN')->first();

        // 1. Superadmin User
        $this->superadmin = User::create([
            'nama' => 'Super Administrator',
            'email' => 'super@kejora.gov.my',
            'password_hash' => Hash::make('password123'),
            'aktif' => true,
        ]);
        $this->superadmin->roles()->attach($superAdminRole);

        // 2. Scoped Admin User (Only IT Department)
        $this->scopedAdmin = User::create([
            'nama' => 'IT Admin',
            'email' => 'itadmin@kejora.gov.my',
            'password_hash' => Hash::make('password123'),
            'aktif' => true,
        ]);
        $this->scopedAdmin->roles()->attach($adminRole);

        // 3. Departments
        $this->bahagianIT = Bahagian::create([
            'kod' => 'BTM',
            'nama' => 'Bahagian Teknologi Maklumat',
            'aktif' => true,
        ]);

        $this->bahagianKewangan = Bahagian::create([
            'kod' => 'KEW',
            'nama' => 'Bahagian Kewangan',
            'aktif' => true,
        ]);

        $this->unitIntegriti = Unit::create([
            'kod' => 'UI',
            'nama' => 'Unit Integriti',
            'aktif' => true,
        ]);

        // Assign IT scope to scopedAdmin
        UserScope::create([
            'user_id' => $this->scopedAdmin->id,
            'bahagian_id' => $this->bahagianIT->id,
            'unit_id' => null,
        ]);

        // 4. Staff members
        $this->anggotaIT = Anggota::create([
            'no_pekerja' => '1827 I',
            'nama_penuh' => 'Emirul Izzat Bin Edon',
            'jawatan' => 'Penolong Pegawai Teknologi Maklumat',
            'gred' => 'FA5',
            'emel' => 'izzat@kejora.gov.my',
            'aktif' => true,
        ]);
        AnggotaPenempatan::create([
            'anggota_id' => $this->anggotaIT->id,
            'bahagian_id' => $this->bahagianIT->id,
            'unit_id' => null,
            'tarikh_mula' => now()->subMonth()->toDateString(),
            'tarikh_tamat' => null,
        ]);

        $this->anggotaKewangan = Anggota::create([
            'no_pekerja' => '1020 K',
            'nama_penuh' => 'Siti Aminah Binti Ahmad',
            'jawatan' => 'Akauntan',
            'gred' => 'WA41',
            'emel' => 'aminah@kejora.gov.my',
            'aktif' => true,
        ]);
        AnggotaPenempatan::create([
            'anggota_id' => $this->anggotaKewangan->id,
            'bahagian_id' => $this->bahagianKewangan->id,
            'unit_id' => null,
            'tarikh_mula' => now()->subMonth()->toDateString(),
            'tarikh_tamat' => null,
        ]);

        $this->anggotaUnit = Anggota::create([
            'no_pekerja' => '2001 U',
            'nama_penuh' => 'Mohd Azhar Bin Salleh',
            'jawatan' => 'Pegawai Integriti',
            'gred' => 'N41',
            'emel' => 'azhar@kejora.gov.my',
            'aktif' => true,
        ]);
        AnggotaPenempatan::create([
            'anggota_id' => $this->anggotaUnit->id,
            'bahagian_id' => null,
            'unit_id' => $this->unitIntegriti->id,
            'tarikh_mula' => now()->subMonth()->toDateString(),
            'tarikh_tamat' => null,
        ]);
    }

    public function test_guest_cannot_access_lookup_endpoint(): void
    {
        $response = $this->getJson(route('lookup.anggota'));
        $response->assertUnauthorized();
    }

    public function test_superadmin_can_search_and_see_all_staff(): void
    {
        $response = $this->actingAs($this->superadmin)->getJson(route('lookup.anggota', ['q' => '']));
        $response->assertOk();

        $data = $response->json('results');
        $this->assertCount(3, $data);

        $titles = collect($data)->pluck('title')->all();
        $this->assertContains('Emirul Izzat Bin Edon', $titles);
        $this->assertContains('Siti Aminah Binti Ahmad', $titles);
        $this->assertContains('Mohd Azhar Bin Salleh', $titles);
    }

    public function test_scoped_admin_can_only_see_staff_within_authorized_scope(): void
    {
        // scopedAdmin has scope for Bahagian Teknologi Maklumat only
        $response = $this->actingAs($this->scopedAdmin)->getJson(route('lookup.anggota', ['q' => '']));
        $response->assertOk();

        $data = $response->json('results');
        $this->assertCount(1, $data);
        $this->assertEquals('Emirul Izzat Bin Edon', $data[0]['title']);
        $this->assertEquals('1827 I', $data[0]['badge']);
    }

    public function test_search_by_query_string_matches_name_and_staff_id(): void
    {
        // Search by keyword "izzat"
        $response = $this->actingAs($this->superadmin)->getJson(route('lookup.anggota', ['q' => 'izzat']));
        $response->assertOk();
        $data = $response->json('results');
        $this->assertCount(1, $data);
        $this->assertEquals('Emirul Izzat Bin Edon', $data[0]['title']);

        // Search by staff ID "1020 K"
        $idResponse = $this->actingAs($this->superadmin)->getJson(route('lookup.anggota', ['q' => '1020 K']));
        $idResponse->assertOk();
        $idData = $idResponse->json('results');
        $this->assertCount(1, $idData);
        $this->assertEquals('Siti Aminah Binti Ahmad', $idData[0]['title']);
    }

    public function test_selected_id_parameter_retrieves_exact_record_for_form_initialization(): void
    {
        $response = $this->actingAs($this->superadmin)->getJson(route('lookup.anggota', [
            'selected_id' => $this->anggotaKewangan->id,
        ]));

        $response->assertOk();
        $data = $response->json('results');
        $this->assertCount(1, $data);
        $this->assertEquals($this->anggotaKewangan->id, $data[0]['id']);
        $this->assertEquals('Siti Aminah Binti Ahmad', $data[0]['title']);
        $this->assertStringContainsString('Bahagian Kewangan', $data[0]['department']);
    }

    public function test_attendance_create_form_renders_searchable_select_and_persists_attendance(): void
    {
        $viewResponse = $this->actingAs($this->superadmin)->get(route('attendance.create'));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Pilih Anggota');
        $viewResponse->assertSee('lookup\/anggota', false);
        $viewResponse->assertSee('name="anggota_id"', false);

        $statusPejabat = StatusKehadiran::where('kod', 'PEJABAT')->first();

        $postResponse = $this->actingAs($this->superadmin)->post(route('attendance.store'), [
            'anggota_id' => $this->anggotaIT->id,
            'status_id' => $statusPejabat->id,
            'tarikh' => now()->toDateString(),
            'sepanjang_hari' => '1',
        ]);

        $postResponse->assertRedirect();
        $this->assertDatabaseHas('rekod_kehadiran', [
            'anggota_id' => $this->anggotaIT->id,
            'status_id' => $statusPejabat->id,
        ]);
    }
}
