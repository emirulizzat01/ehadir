<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Roles
        $roles = [
            ['kod' => 'SUPERADMIN', 'nama' => 'Superadmin'],
            ['kod' => 'ADMIN', 'nama' => 'Admin'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(['kod' => $role['kod']], $role);
        }

        // 2. Seed Permissions
        $permissions = [
            ['kod' => 'org.view', 'nama' => 'Lihat bahagian dan unit'],
            ['kod' => 'org.manage', 'nama' => 'Urus bahagian dan unit'],
            ['kod' => 'users.manage', 'nama' => 'Urus pengguna dan akses'],
            ['kod' => 'anggota.view', 'nama' => 'Lihat anggota'],
            ['kod' => 'anggota.manage', 'nama' => 'Urus anggota'],
            ['kod' => 'attendance.view', 'nama' => 'Lihat kehadiran'],
            ['kod' => 'attendance.manage', 'nama' => 'Urus kehadiran'],
            ['kod' => 'calendar.view', 'nama' => 'Lihat kalendar'],
            ['kod' => 'calendar.manage', 'nama' => 'Urus aktiviti kalendar'],
            ['kod' => 'reports.export', 'nama' => 'Eksport laporan'],
            ['kod' => 'audit.view', 'nama' => 'Lihat audit log'],
        ];

        foreach ($permissions as $perm) {
            DB::table('permissions')->updateOrInsert(['kod' => $perm['kod']], $perm);
        }

        // 3. Seed Role-Permissions
        $superadmin = DB::table('roles')->where('kod', 'SUPERADMIN')->first();
        $admin = DB::table('roles')->where('kod', 'ADMIN')->first();
        $allPermissions = DB::table('permissions')->get();

        // SUPERADMIN gets all permissions
        foreach ($allPermissions as $perm) {
            DB::table('role_permissions')->updateOrInsert([
                'role_id' => $superadmin->id,
                'permission_id' => $perm->id,
            ]);
        }

        // ADMIN gets specified permissions
        $adminPermCodes = [
            'org.view',
            'anggota.view',
            'anggota.manage',
            'attendance.view',
            'attendance.manage',
            'calendar.view',
            'calendar.manage',
            'reports.export',
        ];

        foreach ($allPermissions->whereIn('kod', $adminPermCodes) as $perm) {
            DB::table('role_permissions')->updateOrInsert([
                'role_id' => $admin->id,
                'permission_id' => $perm->id,
            ]);
        }
    }
}
