<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Bahagian;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StatusKehadiranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DataTableUxTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(StatusKehadiranSeeder::class);

        $this->superadmin = User::create([
            'nama' => 'Superadmin User',
            'email' => 'super@ehadir.gov.my',
            'password_hash' => Hash::make('Password@123'),
            'aktif' => true,
        ]);
        $this->superadmin->roles()->attach(Role::where('kod', 'SUPERADMIN')->first());

        // Create sample Bahagian and Unit
        $b1 = Bahagian::create(['kod' => 'B01', 'nama' => 'Bahagian Korporat', 'aktif' => true]);
        $b2 = Bahagian::create(['kod' => 'B02', 'nama' => 'Bahagian Teknologi Maklumat', 'aktif' => true]);
        Unit::create(['bahagian_id' => $b1->id, 'kod' => 'U01', 'nama' => 'Unit Pentadbiran', 'aktif' => true]);

        // Seed 30 Anggota with varying names for sorting tests
        foreach (range(1, 30) as $index) {
            Anggota::create([
                'no_pekerja' => 'STF'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'nama_penuh' => 'Staff '.chr(65 + ($index % 26)).' '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'jawatan' => 'Pegawai Gred '.(41 + ($index % 5)),
                'susunan' => 100 - $index,
                'aktif' => true,
            ]);
        }
    }

    public function test_anggota_datatable_respects_per_page_whitelist(): void
    {
        // Allowed: 10
        $response = $this->actingAs($this->superadmin)->get(route('personnel.anggota.index', ['per_page' => 10]));
        $response->assertOk();
        $this->assertSame(10, $response->viewData('anggotaList')->perPage());

        // Allowed: 25
        $response = $this->actingAs($this->superadmin)->get(route('personnel.anggota.index', ['per_page' => 25]));
        $response->assertOk();
        $this->assertSame(25, $response->viewData('anggotaList')->perPage());

        // Disallowed / non-whitelisted per_page falls back to default 15
        $response = $this->actingAs($this->superadmin)->get(route('personnel.anggota.index', ['per_page' => 9999]));
        $response->assertOk();
        $this->assertSame(15, $response->viewData('anggotaList')->perPage());
    }

    public function test_anggota_datatable_sorts_by_whitelisted_columns(): void
    {
        // Ascending by nama
        $resAsc = $this->actingAs($this->superadmin)->get(route('personnel.anggota.index', [
            'sort' => 'nama',
            'direction' => 'asc',
            'per_page' => 10,
        ]));
        $resAsc->assertOk();
        $itemsAsc = $resAsc->viewData('anggotaList')->items();
        $this->assertTrue(strcmp($itemsAsc[0]->nama_penuh, $itemsAsc[1]->nama_penuh) <= 0);

        // Descending by nama
        $resDesc = $this->actingAs($this->superadmin)->get(route('personnel.anggota.index', [
            'sort' => 'nama',
            'direction' => 'desc',
            'per_page' => 10,
        ]));
        $resDesc->assertOk();
        $itemsDesc = $resDesc->viewData('anggotaList')->items();
        $this->assertTrue(strcmp($itemsDesc[0]->nama_penuh, $itemsDesc[1]->nama_penuh) >= 0);

        // Sorting by invalid column does not crash and falls back safely
        $resInvalid = $this->actingAs($this->superadmin)->get(route('personnel.anggota.index', [
            'sort' => 'non_existent_column',
            'direction' => 'desc',
        ]));
        $resInvalid->assertOk();
    }

    public function test_anggota_datatable_preserves_query_string_in_pagination(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('personnel.anggota.index', [
            'search' => 'Staff',
            'sort' => 'nama',
            'direction' => 'asc',
            'per_page' => 10,
        ]));

        $response->assertOk();
        $paginator = $response->viewData('anggotaList');
        $nextPageUrl = $paginator->nextPageUrl();

        $this->assertNotNull($nextPageUrl);
        $this->assertStringContainsString('search=Staff', $nextPageUrl);
        $this->assertStringContainsString('sort=nama', $nextPageUrl);
        $this->assertStringContainsString('direction=asc', $nextPageUrl);
        $this->assertStringContainsString('per_page=10', $nextPageUrl);
    }

    public function test_attendance_datatable_supports_sorting_and_per_page(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('attendance.index', [
            'per_page' => 10,
            'sort' => 'nama',
            'direction' => 'asc',
        ]));

        $response->assertOk();
        $paginator = $response->viewData('anggotaList');
        $this->assertSame(10, $paginator->perPage());

        $nextPageUrl = $paginator->nextPageUrl();
        $this->assertNotNull($nextPageUrl);
        $this->assertStringContainsString('sort=nama', $nextPageUrl);
        $this->assertStringContainsString('direction=asc', $nextPageUrl);
        $this->assertStringContainsString('per_page=10', $nextPageUrl);
    }

    public function test_organization_bahagian_datatable_sorting_and_per_page(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('organization.bahagian.index', [
            'sort' => 'kod',
            'direction' => 'desc',
            'per_page' => 25,
        ]));

        $response->assertOk();
        $paginator = $response->viewData('bahagianList');
        $this->assertSame(25, $paginator->perPage());
        $items = $paginator->items();
        $this->assertSame('B02', $items[0]->kod);
    }

    public function test_admin_audit_datatable_sorting_and_per_page(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('audit.index', [
            'sort' => 'masa',
            'direction' => 'desc',
            'per_page' => 10,
        ]));

        $response->assertOk();
        $paginator = $response->viewData('logs');
        $this->assertSame(10, $paginator->perPage());
    }

    public function test_daily_report_datatable_sorting_and_per_page(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('reports.daily', [
            'sort' => 'nama',
            'direction' => 'asc',
            'per_page' => 10,
        ]));

        $response->assertOk();
        $paginator = $response->viewData('anggotaList');
        $this->assertSame(10, $paginator->perPage());
    }
}

