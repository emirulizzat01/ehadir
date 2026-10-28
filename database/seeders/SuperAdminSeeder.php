<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadminEmail = env('SUPERADMIN_EMAIL', 'izzat@kejora.gov.my');
        $superadminPassword = env('SUPERADMIN_PASSWORD', 'Password@123');

        DB::table('users')->updateOrInsert(
            ['email' => $superadminEmail],
            [
                'nama' => 'Emirul Izzat Bin Edon',
                'password_hash' => Hash::make($superadminPassword),
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $user = DB::table('users')->where('email', $superadminEmail)->first();
        $superadminRole = DB::table('roles')->where('kod', 'SUPERADMIN')->first();

        if ($user && $superadminRole) {
            // Remove SUPERADMIN role from all other accounts
            DB::table('user_roles')
                ->where('role_id', $superadminRole->id)
                ->where('user_id', '!=', $user->id)
                ->delete();

            // Exclusively assign SUPERADMIN role to this user
            DB::table('user_roles')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'role_id' => $superadminRole->id,
                ]
            );
        }

        // Also create an initial Bahagian and Unit for initial readiness
        $bahagianData = [
            ['kod' => 'BTM', 'nama' => 'Bahagian Teknologi Maklumat', 'aktif' => true],
            ['kod' => 'BPSM', 'nama' => 'Bahagian Pengurusan Sumber Manusia', 'aktif' => true],
            ['kod' => 'BKEU', 'nama' => 'Bahagian Kewangan dan Akaun', 'aktif' => true],
        ];

        foreach ($bahagianData as $b) {
            DB::table('bahagian')->updateOrInsert(['kod' => $b['kod']], $b);
        }

        $unitData = [
            ['kod' => 'UINT', 'nama' => 'Unit Integriti', 'aktif' => true],
            ['kod' => 'UUU', 'nama' => 'Unit Penasihat Undang-Undang', 'aktif' => true],
            ['kod' => 'UKK', 'nama' => 'Unit Komunikasi Korporat', 'aktif' => true],
        ];

        foreach ($unitData as $u) {
            DB::table('unit')->updateOrInsert(['kod' => $u['kod']], $u);
        }
    }
}
