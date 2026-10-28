<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\AnggotaPenempatan;
use App\Models\Bahagian;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserScope;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\StatusKehadiranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserAnggotaLinkageTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected Bahagian $bahagian;

    protected Unit $unit;

    protected Anggota $anggota;

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

        $this->bahagian = Bahagian::create([
            'kod' => 'BTM',
            'nama' => 'Bahagian Teknologi Maklumat',
            'aktif' => true,
        ]);

        $this->unit = Unit::create([
            'kod' => 'UKK',
            'nama' => 'Unit Komunikasi Korporat',
            'aktif' => true,
        ]);

        $this->anggota = Anggota::create([
            'no_pekerja' => '1827 I',
            'nama_penuh' => 'Emirul Izzat Bin Edon',
            'gelaran' => 'Encik',
            'jawatan' => 'Penolong Pegawai Teknologi Maklumat',
            'aktif' => true,
        ]);

        AnggotaPenempatan::create([
            'anggota_id' => $this->anggota->id,
            'bahagian_id' => $this->bahagian->id,
            'unit_id' => null,
            'tarikh_mula' => now()->subMonths(6)->toDateString(),
            'tarikh_tamat' => null,
        ]);
    }

    public function test_user_and_anggota_relationships(): void
    {
        $adminRole = Role::where('kod', 'ADMIN')->first();

        $user = User::create([
            'anggota_id' => $this->anggota->id,
            'nama' => 'Emirul Izzat',
            'email' => 'izzat@kejora.gov.my',
            'password_hash' => Hash::make('secret123'),
            'aktif' => true,
        ]);
        $user->roles()->attach($adminRole);

        // Test User -> Anggota
        $this->assertNotNull($user->anggota);
        $this->assertEquals($this->anggota->id, $user->anggota->id);
        $this->assertEquals('1827 I', $user->anggota->no_pekerja);

        // Test Anggota -> User
        $this->assertNotNull($this->anggota->fresh()->user);
        $this->assertEquals($user->id, $this->anggota->fresh()->user->id);
    }

    public function test_null_on_delete_cascades_safely_when_anggota_is_deleted(): void
    {
        $user = User::create([
            'anggota_id' => $this->anggota->id,
            'nama' => 'Temporary Operator',
            'email' => 'operator@kejora.gov.my',
            'password_hash' => Hash::make('secret123'),
            'aktif' => true,
        ]);

        $this->assertEquals($this->anggota->id, $user->fresh()->anggota_id);

        // Delete penempatan first to satisfy DB foreign keys if any
        $this->anggota->penempatan()->delete();
        $this->anggota->delete();

        // User should still exist, with anggota_id set to null
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertNull($user->fresh()->anggota_id);
    }

    public function test_auto_inherits_bahagian_scope_from_linked_anggota(): void
    {
        $user = User::create([
            'anggota_id' => $this->anggota->id,
            'nama' => 'Emirul Izzat',
            'email' => 'izzat@kejora.gov.my',
            'password_hash' => Hash::make('secret123'),
            'aktif' => true,
        ]);

        // Has no explicit UserScope records
        $this->assertCount(0, $user->scopes);

        // Automatically inherits Bahagian Teknologi Maklumat ID
        $this->assertEquals([$this->bahagian->id], $user->getScopedBahagianIds());
        $this->assertEmpty($user->getScopedUnitIds());
    }

    public function test_auto_inherits_unit_scope_when_anggota_is_placed_in_unit(): void
    {
        $anggotaUnit = Anggota::create([
            'no_pekerja' => '2001 U',
            'nama_penuh' => 'Siti Nurhaliza',
            'jawatan' => 'Pegawai Perhubungan Awam',
            'aktif' => true,
        ]);

        AnggotaPenempatan::create([
            'anggota_id' => $anggotaUnit->id,
            'bahagian_id' => null,
            'unit_id' => $this->unit->id,
            'tarikh_mula' => now()->subMonth()->toDateString(),
            'tarikh_tamat' => null,
        ]);

        $user = User::create([
            'anggota_id' => $anggotaUnit->id,
            'nama' => 'Siti User',
            'email' => 'siti@kejora.gov.my',
            'password_hash' => Hash::make('secret123'),
            'aktif' => true,
        ]);

        $this->assertEmpty($user->getScopedBahagianIds());
        $this->assertEquals([$this->unit->id], $user->getScopedUnitIds());
    }

    public function test_explicit_user_scopes_override_inherited_anggota_placement(): void
    {
        $otherBahagian = Bahagian::create([
            'kod' => 'BPK',
            'nama' => 'Bahagian Pembangunan Korporat',
            'aktif' => true,
        ]);

        $user = User::create([
            'anggota_id' => $this->anggota->id, // linked to BTM
            'nama' => 'Emirul Izzat',
            'email' => 'izzat@kejora.gov.my',
            'password_hash' => Hash::make('secret123'),
            'aktif' => true,
        ]);

        // Explicitly assigned to BPK
        UserScope::create([
            'user_id' => $user->id,
            'bahagian_id' => $otherBahagian->id,
            'unit_id' => null,
        ]);

        $this->assertEquals([$otherBahagian->id], $user->fresh()->getScopedBahagianIds());
    }

    public function test_superadmin_can_create_user_with_linked_anggota(): void
    {
        $adminRole = Role::where('kod', 'ADMIN')->first();

        $response = $this->actingAs($this->superadmin)->post(route('users.store'), [
            'anggota_id' => $this->anggota->id,
            'nama' => 'Ahmad Khairi',
            'email' => 'khairi@kejora.gov.my',
            'password' => 'SecurePass123!',
            'aktif' => '1',
            'role_ids' => [$adminRole->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'khairi@kejora.gov.my',
            'anggota_id' => $this->anggota->id,
        ]);
    }

    public function test_validation_prevents_linking_same_anggota_to_multiple_users(): void
    {
        $adminRole = Role::where('kod', 'ADMIN')->first();

        User::create([
            'anggota_id' => $this->anggota->id,
            'nama' => 'User One',
            'email' => 'user1@kejora.gov.my',
            'password_hash' => Hash::make('password123'),
            'aktif' => true,
        ]);

        $response = $this->actingAs($this->superadmin)->post(route('users.store'), [
            'anggota_id' => $this->anggota->id,
            'nama' => 'User Two',
            'email' => 'user2@kejora.gov.my',
            'password' => 'SecurePass123!',
            'aktif' => '1',
            'role_ids' => [$adminRole->id],
        ]);

        $response->assertSessionHasErrors(['anggota_id']);
        $this->assertDatabaseMissing('users', ['email' => 'user2@kejora.gov.my']);
    }

    public function test_superadmin_can_update_user_and_change_or_unlink_anggota(): void
    {
        $adminRole = Role::where('kod', 'ADMIN')->first();

        $user = User::create([
            'anggota_id' => $this->anggota->id,
            'nama' => 'Linked User',
            'email' => 'linked@kejora.gov.my',
            'password_hash' => Hash::make('password123'),
            'aktif' => true,
        ]);
        $user->roles()->attach($adminRole);

        // Update keeping the same anggota_id should pass unique check
        $response = $this->actingAs($this->superadmin)->put(route('users.update', $user), [
            'anggota_id' => $this->anggota->id,
            'nama' => 'Linked User Updated',
            'email' => 'linked@kejora.gov.my',
            'role_ids' => [$adminRole->id],
        ]);

        $response->assertRedirect(route('users.show', $user));
        $this->assertEquals('Linked User Updated', $user->fresh()->nama);
        $this->assertEquals($this->anggota->id, $user->fresh()->anggota_id);

        // Unlink anggota
        $response = $this->actingAs($this->superadmin)->put(route('users.update', $user), [
            'anggota_id' => null,
            'nama' => 'Unlinked User',
            'email' => 'linked@kejora.gov.my',
            'role_ids' => [$adminRole->id],
        ]);

        $response->assertRedirect(route('users.show', $user));
        $this->assertNull($user->fresh()->anggota_id);
    }

    public function test_views_render_linked_anggota_information(): void
    {
        $adminRole = Role::where('kod', 'ADMIN')->first();

        $user = User::create([
            'anggota_id' => $this->anggota->id,
            'nama' => 'Emirul Izzat',
            'email' => 'izzat@kejora.gov.my',
            'password_hash' => Hash::make('secret123'),
            'aktif' => true,
        ]);
        $user->roles()->attach($adminRole);

        // Index page should show staff ID and badge
        $indexResponse = $this->actingAs($this->superadmin)->get(route('users.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('1827 I');
        $indexResponse->assertSee('Diwarisi');

        // Show page should display the linked staff card
        $showResponse = $this->actingAs($this->superadmin)->get(route('users.show', $user));
        $showResponse->assertOk();
        $showResponse->assertSee('Emirul Izzat Bin Edon');
        $showResponse->assertSee('1827 I');
        $showResponse->assertSee('Skop Diwarisi Automatik');

        // Edit page should have the select option with selected state
        $editResponse = $this->actingAs($this->superadmin)->get(route('users.edit', $user));
        $editResponse->assertOk();
        $editResponse->assertSee('Pautkan Rekod Anggota');
        $editResponse->assertSee('1827 I');
    }

    public function test_user_creation_auto_derives_nama_from_linked_anggota_when_omitted(): void
    {
        $adminRole = Role::where('kod', 'ADMIN')->first();

        $response = $this->actingAs($this->superadmin)->post(route('users.store'), [
            'anggota_id' => $this->anggota->id,
            'nama' => '', // Left blank to test auto-derivation
            'email' => 'autoname@kejora.gov.my',
            'password' => 'SecurePassword123!',
            'aktif' => '1',
            'role_ids' => [$adminRole->id],
        ]);

        $response->assertRedirect();
        $user = User::where('email', 'autoname@kejora.gov.my')->first();
        $this->assertNotNull($user);
        $this->assertEquals($this->anggota->nama_penuh, $user->nama);
        $this->assertEquals($this->anggota->id, $user->anggota_id);
    }

    public function test_user_creation_requires_nama_when_anggota_id_is_null(): void
    {
        $adminRole = Role::where('kod', 'ADMIN')->first();

        $response = $this->actingAs($this->superadmin)->post(route('users.store'), [
            'anggota_id' => null,
            'nama' => '', // Should fail because anggota_id is not provided
            'email' => 'unlinked@kejora.gov.my',
            'password' => 'SecurePassword123!',
            'aktif' => '1',
            'role_ids' => [$adminRole->id],
        ]);

        $response->assertSessionHasErrors(['nama']);
        $this->assertDatabaseMissing('users', ['email' => 'unlinked@kejora.gov.my']);
    }
}

