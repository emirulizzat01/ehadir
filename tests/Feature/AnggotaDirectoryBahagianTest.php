<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\AnggotaPenempatan;
use App\Models\Bahagian;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StatusKehadiranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AnggotaDirectoryBahagianTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected Bahagian $bahagian1;
    protected Bahagian $bahagian2;
    protected Unit $unit1;
    protected Anggota $anggota1;
    protected Anggota $anggota2;
    protected Anggota $anggota3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(StatusKehadiranSeeder::class);

        $superAdminRole = Role::where('kod', 'SUPERADMIN')->first();

        $this->superadmin = User::create([
            'nama' => 'Super Administrator',
            'email' => 'admin@kejora.gov.my',
            'password_hash' => Hash::make('password123'),
            'aktif' => true,
        ]);
        $this->superadmin->roles()->attach($superAdminRole);

        $this->bahagian1 = Bahagian::create([
            'kod' => 'BTM',
            'nama' => 'Bahagian Teknologi Maklumat',
            'aktif' => true,
        ]);

        $this->bahagian2 = Bahagian::create([
            'kod' => 'BKP',
            'nama' => 'Bahagian Khidmat Pengurusan',
            'aktif' => true,
        ]);

        $this->unit1 = Unit::create([
            'kod' => 'UKK',
            'nama' => 'Unit Komunikasi Korporat',
            'aktif' => true,
        ]);

        // Anggota 1 in Bahagian 1
        $this->anggota1 = Anggota::create([
            'no_pekerja' => 'STAFF-001',
            'nama_penuh' => 'Ahmad IT Specialist',
            'jawatan' => 'Pegawai Teknologi Maklumat',
            'aktif' => true,
        ]);
        AnggotaPenempatan::create([
            'anggota_id' => $this->anggota1->id,
            'bahagian_id' => $this->bahagian1->id,
            'unit_id' => null,
            'tarikh_mula' => now()->subMonths(6)->toDateString(),
            'tarikh_tamat' => null,
        ]);

        // Anggota 2 in Unit 1
        $this->anggota2 = Anggota::create([
            'no_pekerja' => 'STAFF-002',
            'nama_penuh' => 'Siti Corporate Affairs',
            'jawatan' => 'Pegawai Penerangan',
            'aktif' => true,
        ]);
        AnggotaPenempatan::create([
            'anggota_id' => $this->anggota2->id,
            'bahagian_id' => null,
            'unit_id' => $this->unit1->id,
            'tarikh_mula' => now()->subMonths(3)->toDateString(),
            'tarikh_tamat' => null,
        ]);

        // Anggota 3 with no placement
        $this->anggota3 = Anggota::create([
            'no_pekerja' => 'STAFF-003',
            'nama_penuh' => 'Ali Unassigned',
            'jawatan' => 'Pembantu Operasi',
            'aktif' => true,
        ]);
    }

    public function test_directory_renders_raw_bahagian_unit_and_employee_number(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('personnel.anggota.index'));

        $response->assertStatus(200);
        $response->assertSee('Bahagian / Unit');
        $response->assertSee('Bahagian Teknologi Maklumat');
        $response->assertSee('Unit Komunikasi Korporat');
        $response->assertSee('STAFF-001');
        $response->assertSee('STAFF-002');
        $response->assertSee('STAFF-003');
    }

    public function test_directory_filters_by_bahagian_id(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->get(route('personnel.anggota.index', ['bahagian_id' => $this->bahagian1->id]));

        $response->assertStatus(200);
        $response->assertSee('Ahmad IT Specialist');
        $response->assertDontSee('Siti Corporate Affairs');
        $response->assertDontSee('Ali Unassigned');
    }

    public function test_directory_search_by_bahagian_or_unit_code(): void
    {
        // Search by unit code
        $responseUnit = $this->actingAs($this->superadmin)
            ->get(route('personnel.anggota.index', ['search' => 'UKK']));

        $responseUnit->assertStatus(200);
        $responseUnit->assertSee('Siti Corporate Affairs');
        $responseUnit->assertDontSee('Ahmad IT Specialist');

        // Search by bahagian code
        $responseBahagian = $this->actingAs($this->superadmin)
            ->get(route('personnel.anggota.index', ['search' => 'BTM']));

        $responseBahagian->assertStatus(200);
        $responseBahagian->assertSee('Ahmad IT Specialist');
        $responseBahagian->assertDontSee('Siti Corporate Affairs');
    }

    public function test_directory_prioritizes_active_staff_before_inactive_staff(): void
    {
        // Create an inactive staff member whose name starts with asterisk (as in OneStack)
        $inactive = Anggota::create([
            'no_pekerja' => 'STAFF-999',
            'nama_penuh' => '*Zulkifli Retired',
            'jawatan' => 'Bekas Pegawai',
            'aktif' => false,
        ]);
        AnggotaPenempatan::create([
            'anggota_id' => $inactive->id,
            'bahagian_id' => $this->bahagian1->id,
            'tarikh_mula' => now()->subYears(5)->toDateString(),
            'tarikh_tamat' => now()->subMonth()->toDateString(),
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('personnel.anggota.index'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Active staff must appear before inactive staff in table order
        $posActive = strpos($content, 'Ahmad IT Specialist');
        $posInactive = strpos($content, '*Zulkifli Retired');

        $this->assertNotFalse($posActive);
        $this->assertNotFalse($posInactive);
        $this->assertTrue($posActive < $posInactive, 'Active staff must appear before inactive staff.');
        $response->assertSee('Bahagian Teknologi Maklumat');
    }
}
