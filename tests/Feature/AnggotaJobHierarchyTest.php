<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Role;
use App\Models\User;
use App\Services\Personnel\GradeHierarchyService;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StatusKehadiranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AnggotaJobHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected User $regularUser;

    protected GradeHierarchyService $hierarchyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(StatusKehadiranSeeder::class);

        $this->hierarchyService = new GradeHierarchyService;

        $this->superadmin = User::create([
            'nama' => 'Superadmin User',
            'email' => 'super@ehadir.gov.my',
            'password_hash' => Hash::make('Password@123'),
            'aktif' => true,
        ]);
        $this->superadmin->roles()->attach(Role::where('kod', 'SUPERADMIN')->first());

        $this->regularUser = User::create([
            'nama' => 'User Biasa',
            'email' => 'biasa@ehadir.gov.my',
            'password_hash' => Hash::make('Password@123'),
            'aktif' => true,
        ]);
        $this->regularUser->roles()->attach(Role::where('kod', 'USER')->first());
    }

    public function test_hierarchy_service_scores_malaysian_public_service_grades_accurately(): void
    {
        // 1. Premier Grade / JUSA
        $this->assertSame(GradeHierarchyService::SCORE_PREMIER, $this->hierarchyService->calculateScore('JUSA C', 'PENGURUS BESAR'));
        $this->assertSame(GradeHierarchyService::SCORE_PREMIER, $this->hierarchyService->calculateScore(null, 'PENGURUS BESAR'));

        // 2. Gred 54 / N14 (Timbalan Pengurus Besar)
        $this->assertSame(GradeHierarchyService::SCORE_GRED_54, $this->hierarchyService->calculateScore('N14', 'TIMBALAN PENGURUS BESAR(PEMBANGUNAN)'));

        // 3. Gred 52 / N13 / N52 (Pengurus Bahagian)
        $this->assertSame(GradeHierarchyService::SCORE_GRED_52, $this->hierarchyService->calculateScore('N13', 'PENGURUS BAHAGIAN'));
        $this->assertSame(GradeHierarchyService::SCORE_GRED_52, $this->hierarchyService->calculateScore('N52', 'PENGURUS KANAN'));

        // 4. Gred 48 / N12 / W12 / J12 (Pengurus)
        $this->assertSame(GradeHierarchyService::SCORE_GRED_48, $this->hierarchyService->calculateScore('N12', 'PENGURUS'));
        $this->assertSame(GradeHierarchyService::SCORE_GRED_48, $this->hierarchyService->calculateScore('W12', 'KETUA BAHAGIAN'));

        // 5. Gred 44 / N10 / W10 (Ketua Unit)
        $this->assertSame(GradeHierarchyService::SCORE_GRED_44, $this->hierarchyService->calculateScore('N10', 'KETUA UNIT'));
        $this->assertSame(GradeHierarchyService::SCORE_GRED_44, $this->hierarchyService->calculateScore('W10', 'AKAUNTAN'));

        // 6. Gred 41 / N9 / G9 / G41 (Pegawai)
        $this->assertSame(GradeHierarchyService::SCORE_GRED_41, $this->hierarchyService->calculateScore('N9', 'PENOLONG PENGURUS'));
        $this->assertSame(GradeHierarchyService::SCORE_GRED_41, $this->hierarchyService->calculateScore('G41', 'PEGAWAI PERTANIAN'));

        // 7. Gred 32-38 / N6-N7
        $this->assertSame(GradeHierarchyService::SCORE_GRED_36_38, $this->hierarchyService->calculateScore('N7', 'PENOLONG PEGAWAI TADBIR KANAN'));
        $this->assertSame(GradeHierarchyService::SCORE_GRED_32, $this->hierarchyService->calculateScore('N6', 'PENOLONG PEGAWAI TADBIR'));

        // 8. Gred 29 / N5 / N29
        $this->assertSame(GradeHierarchyService::SCORE_GRED_29, $this->hierarchyService->calculateScore('N5', 'PENOLONG PEGAWAI TADBIR'));
        $this->assertSame(GradeHierarchyService::SCORE_GRED_29, $this->hierarchyService->calculateScore('N29', 'PENOLONG PEGAWAI TADBIR'));

        // 9. Gred 19-26 / N2 / N3 / N19
        $this->assertSame(GradeHierarchyService::SCORE_GRED_22_26, $this->hierarchyService->calculateScore('N3', 'PEMBANTU TADBIR KANAN'));
        $this->assertSame(GradeHierarchyService::SCORE_GRED_19, $this->hierarchyService->calculateScore('N2', 'PEMBANTU TADBIR (P/O)'));
        $this->assertSame(GradeHierarchyService::SCORE_GRED_19, $this->hierarchyService->calculateScore('N19', 'PEMBANTU TADBIR'));

        // 10. Gred 1-14 / H1 / H11
        $this->assertSame(GradeHierarchyService::SCORE_GRED_11_14, $this->hierarchyService->calculateScore('H11', 'PENGHANTAR NOTIS'));
        $this->assertSame(GradeHierarchyService::SCORE_GRED_1_10, $this->hierarchyService->calculateScore('H1', 'PEMANDU KENDERAAN'));

        // 11. MySTEP
        $this->assertSame(GradeHierarchyService::SCORE_MYSTEP, $this->hierarchyService->calculateScore('00', 'MYSTEP'));
        $this->assertSame(GradeHierarchyService::SCORE_MYSTEP, $this->hierarchyService->calculateScore(null, 'MYSTEP'));
    }

    public function test_anggota_model_saving_hook_auto_computes_hierarchy_score(): void
    {
        $gm = Anggota::create([
            'no_pekerja' => 'STF-GM-01',
            'nama_penuh' => 'Dato GM',
            'jawatan' => 'PENGURUS BESAR',
            'gred' => 'JUSA C',
            'aktif' => true,
        ]);
        $this->assertSame(GradeHierarchyService::SCORE_PREMIER, $gm->tahap_hierarki);
        $this->assertSame('Pengurusan Tertinggi', $gm->kategori_hierarki);

        $driver = Anggota::create([
            'no_pekerja' => 'STF-DRV-01',
            'nama_penuh' => 'Pemandu Ali',
            'jawatan' => 'PEMANDU KENDERAAN',
            'gred' => 'H1',
            'aktif' => true,
        ]);
        $this->assertSame(GradeHierarchyService::SCORE_GRED_1_10, $driver->tahap_hierarki);
        $this->assertSame('Pelaksana (Operasi)', $driver->kategori_hierarki);

        // Update gred changes tahap_hierarki automatically
        $driver->update(['gred' => 'N19', 'jawatan' => 'PEMBANTU TADBIR']);
        $this->assertSame(GradeHierarchyService::SCORE_GRED_19, $driver->fresh()->tahap_hierarki);
    }

    public function test_personnel_directory_defaults_to_job_position_hierarchy_order(): void
    {
        $driver = Anggota::create([
            'no_pekerja' => 'STF-001',
            'nama_penuh' => 'Ahmad Driver',
            'jawatan' => 'PEMANDU',
            'gred' => 'H1',
            'aktif' => true,
        ]);

        $gm = Anggota::create([
            'no_pekerja' => 'STF-002',
            'nama_penuh' => 'Zainal GM',
            'jawatan' => 'PENGURUS BESAR',
            'gred' => 'JUSA C',
            'aktif' => true,
        ]);

        $officer = Anggota::create([
            'no_pekerja' => 'STF-003',
            'nama_penuh' => 'Kamal Pegawai',
            'jawatan' => 'PEGAWAI TADBIR',
            'gred' => 'N9',
            'aktif' => true,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('personnel.anggota.index'));
        $response->assertOk();

        $items = $response->viewData('anggotaList')->items();

        // 1st should be GM (JUSA C), 2nd Officer (N9), 3rd Driver (H1)
        $this->assertSame($gm->id, $items[0]->id);
        $this->assertSame($officer->id, $items[1]->id);
        $this->assertSame($driver->id, $items[2]->id);
    }

    public function test_attendance_roster_defaults_to_job_position_hierarchy_order(): void
    {
        $clerk = Anggota::create([
            'no_pekerja' => 'STF-101',
            'nama_penuh' => 'Abu Clerk',
            'jawatan' => 'PEMBANTU TADBIR',
            'gred' => 'N2',
            'aktif' => true,
        ]);

        $tpb = Anggota::create([
            'no_pekerja' => 'STF-102',
            'nama_penuh' => 'Zain TPB',
            'jawatan' => 'TIMBALAN PENGURUS BESAR',
            'gred' => 'N14',
            'aktif' => true,
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('attendance.index'));
        $response->assertOk();

        $items = $response->viewData('anggotaList')->items();

        // TPB (N14 / score 900) should appear before Clerk (N2 / score 200)
        $this->assertSame($tpb->id, $items[0]->id);
        $this->assertSame($clerk->id, $items[1]->id);
    }

    public function test_artisan_sync_hierarchy_command_processes_records(): void
    {
        $staff = Anggota::create([
            'no_pekerja' => 'STF-CMD-01',
            'nama_penuh' => 'Staff Test',
            'jawatan' => 'KETUA UNIT',
            'gred' => 'N10',
            'aktif' => true,
        ]);

        // Force reset score directly in DB
        Anggota::where('id', $staff->id)->update(['tahap_hierarki' => 0]);
        $this->assertSame(0, $staff->fresh()->tahap_hierarki);

        $this->artisan('anggota:sync-hierarchy --force')
            ->assertSuccessful();

        $this->assertSame(GradeHierarchyService::SCORE_GRED_44, $staff->fresh()->tahap_hierarki);
    }

    public function test_admin_can_trigger_hierarchy_sync_via_http(): void
    {
        $staff = Anggota::create([
            'no_pekerja' => 'STF-WEB-01',
            'nama_penuh' => 'Staff Web',
            'jawatan' => 'PENGURUS BAHAGIAN',
            'gred' => 'N13',
            'aktif' => true,
        ]);

        Anggota::where('id', $staff->id)->update(['tahap_hierarki' => 0]);

        $response = $this->actingAs($this->superadmin)->post(route('personnel.anggota.sync-hierarchy'));
        $response->assertRedirect(route('personnel.anggota.index'));
        $response->assertSessionHas('success');

        $this->assertSame(GradeHierarchyService::SCORE_GRED_52, $staff->fresh()->tahap_hierarki);

        // Non-authorized user cannot sync
        $forbiddenResponse = $this->actingAs($this->regularUser)->post(route('personnel.anggota.sync-hierarchy'));
        $forbiddenResponse->assertForbidden();
    }
}
