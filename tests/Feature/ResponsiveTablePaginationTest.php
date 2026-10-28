<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StatusKehadiranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResponsiveTablePaginationTest extends TestCase
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

        foreach (range(1, 30) as $index) {
            Anggota::create([
                'no_pekerja' => 'STF'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'nama_penuh' => 'Anggota Ujian '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'jawatan' => 'Pegawai Ujian',
                'susunan' => $index,
                'aktif' => true,
            ]);
        }
    }

    public function test_attendance_roster_paginates_twenty_five_records(): void
    {
        $firstPage = $this->actingAs($this->superadmin)->get(route('attendance.index'));

        $firstPage->assertOk();
        $firstPage->assertSee('1 / 2');

        $paginator = $firstPage->viewData('anggotaList');
        $this->assertSame(25, $paginator->perPage());
        $this->assertSame(30, $paginator->total());

        $secondPage = $this->actingAs($this->superadmin)->get(route('attendance.index', ['page' => 2]));

        $secondPage->assertOk();
        $secondPage->assertSee('Anggota Ujian 26');
        $this->assertSame(2, $secondPage->viewData('anggotaList')->currentPage());
    }

    public function test_daily_report_paginates_twenty_five_records(): void
    {
        $firstPage = $this->actingAs($this->superadmin)->get(route('reports.daily'));

        $firstPage->assertOk();
        $firstPage->assertSee('1 / 2');

        $paginator = $firstPage->viewData('anggotaList');
        $this->assertSame(25, $paginator->perPage());
        $this->assertSame(30, $paginator->total());

        $secondPage = $this->actingAs($this->superadmin)->get(route('reports.daily', ['page' => 2]));

        $secondPage->assertOk();
        $secondPage->assertSee('Anggota Ujian 26');
        $this->assertSame(2, $secondPage->viewData('anggotaList')->currentPage());
    }

    public function test_monthly_report_keeps_full_matrix_and_paginates_mobile_cards_separately(): void
    {
        $firstPage = $this->actingAs($this->superadmin)->get(route('reports.monthly'));

        $firstPage->assertOk();
        $this->assertCount(30, $firstPage->viewData('anggotaList'));

        $mobilePaginator = $firstPage->viewData('mobileAnggota');
        $this->assertSame(25, $mobilePaginator->perPage());
        $this->assertSame(30, $mobilePaginator->total());
        $this->assertSame('mobile_page', $mobilePaginator->getPageName());

        $secondPage = $this->actingAs($this->superadmin)->get(route('reports.monthly', ['mobile_page' => 2]));

        $secondPage->assertOk();
        $secondPage->assertSee('Anggota Ujian 26');
        $this->assertSame(2, $secondPage->viewData('mobileAnggota')->currentPage());
        $this->assertCount(30, $secondPage->viewData('anggotaList'));
    }
}
