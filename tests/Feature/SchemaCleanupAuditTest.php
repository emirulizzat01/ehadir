<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\AnggotaPenempatan;
use App\Models\Bahagian;
use App\Models\Role;
use App\Models\User;
use App\Services\OneStack\OneStackSyncService;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StatusKehadiranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaCleanupAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

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
    }

    public function test_obsolete_scaffolding_tables_are_dropped(): void
    {
        $this->assertFalse(Schema::hasTable('password_reset_tokens'));
        $this->assertFalse(Schema::hasTable('jobs'));
        $this->assertFalse(Schema::hasTable('job_batches'));
        $this->assertFalse(Schema::hasTable('failed_jobs'));
    }

    public function test_core_domain_tables_exist_and_remain_healthy(): void
    {
        $coreTables = [
            'users',
            'roles',
            'permissions',
            'user_roles',
            'role_permissions',
            'user_scopes',
            'bahagian',
            'unit',
            'anggota',
            'anggota_penempatan',
            'status_kehadiran',
            'rekod_kehadiran',
            'calendar_events',
            'calendar_event_attendees',
            'audit_logs',
            'sessions',
        ];

        foreach ($coreTables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected core table '{$table}' to exist.");
        }
    }

    public function test_onestack_sync_synchronizes_linked_user_account(): void
    {
        $bahagian = Bahagian::create([
            'kod' => 'BTM',
            'nama' => 'Bahagian Teknologi Maklumat',
            'aktif' => true,
        ]);

        $anggota = Anggota::create([
            'no_pekerja' => 'SYNC-999',
            'nama_penuh' => 'Old Staff Name',
            'jawatan' => 'Pegawai IT',
            'emel' => 'old_email@kejora.gov.my',
            'aktif' => true,
        ]);

        AnggotaPenempatan::create([
            'anggota_id' => $anggota->id,
            'bahagian_id' => $bahagian->id,
            'unit_id' => null,
            'tarikh_mula' => now()->subYear()->toDateString(),
            'tarikh_tamat' => null,
        ]);

        $linkedUser = User::create([
            'anggota_id' => $anggota->id,
            'nama' => 'Old Staff Name',
            'email' => 'old_email@kejora.gov.my',
            'password_hash' => Hash::make('secret123'),
            'aktif' => true,
        ]);

        // Mock OneStack payload with updated name and email
        $syncService = app(OneStackSyncService::class);
        $rawPayload = [
            'id_pengguna' => 'SYNC-999',
            'nama_pengguna' => 'Updated Staff Name',
            'jawatan' => 'Pegawai IT Kanan',
            'kod_bahagian' => 'BTM',
            'bahagian' => 'Bahagian Teknologi Maklumat',
            'gred' => 'F44',
            'emel_pengguna' => 'updated_email@kejora.gov.my',
            'status_masih_berkhidmat' => 1,
        ];

        $syncService->syncUserCollection([$rawPayload], ['actor_id' => $this->superadmin->id]);

        $linkedUser->refresh();
        $this->assertEquals('Updated Staff Name', $linkedUser->nama);
        $this->assertEquals('updated_email@kejora.gov.my', $linkedUser->email);
        $this->assertTrue($linkedUser->aktif);
    }

    public function test_onestack_sync_deactivates_linked_user_when_staff_is_retired(): void
    {
        $bahagian = Bahagian::create([
            'kod' => 'BTM',
            'nama' => 'Bahagian Teknologi Maklumat',
            'aktif' => true,
        ]);

        $anggota = Anggota::create([
            'no_pekerja' => 'RETIRE-111',
            'nama_penuh' => 'Retiring Staff',
            'jawatan' => 'Pegawai Kanan',
            'emel' => 'retire@kejora.gov.my',
            'aktif' => true,
        ]);

        AnggotaPenempatan::create([
            'anggota_id' => $anggota->id,
            'bahagian_id' => $bahagian->id,
            'unit_id' => null,
            'tarikh_mula' => now()->subYears(10)->toDateString(),
            'tarikh_tamat' => null,
        ]);

        $linkedUser = User::create([
            'anggota_id' => $anggota->id,
            'nama' => 'Retiring Staff',
            'email' => 'retire@kejora.gov.my',
            'password_hash' => Hash::make('secret123'),
            'aktif' => true,
        ]);

        $syncService = app(OneStackSyncService::class);
        $retiredPayload = [
            'id_pengguna' => 'RETIRE-111',
            'nama_pengguna' => 'Retiring Staff',
            'jawatan' => 'Pegawai Kanan',
            'kod_bahagian' => 'BTM',
            'bahagian' => 'Bahagian Teknologi Maklumat',
            'gred' => 'F44',
            'emel_pengguna' => 'retire@kejora.gov.my',
            'status_masih_berkhidmat' => 0,
        ];

        $syncService->syncUserCollection([$retiredPayload], ['actor_id' => $this->superadmin->id]);

        $linkedUser->refresh();
        $this->assertFalse($linkedUser->aktif);
    }
}
