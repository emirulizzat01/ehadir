<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusKehadiranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['kod' => 'PEJABAT', 'nama' => 'Pejabat', 'warna' => '#16A34A', 'aktif' => true, 'susunan' => 1],
            ['kod' => 'CUTI_REHAT', 'nama' => 'Cuti Rehat', 'warna' => '#F59E0B', 'aktif' => true, 'susunan' => 2],
            ['kod' => 'MC', 'nama' => 'Cuti Sakit (MC)', 'warna' => '#EF4444', 'aktif' => true, 'susunan' => 3],
            ['kod' => 'EMPAT_JAM', 'nama' => '4 Jam', 'warna' => '#8B5CF6', 'aktif' => true, 'susunan' => 4],
            ['kod' => 'KERJA_LUAR', 'nama' => 'Kerja Luar', 'warna' => '#3B82F6', 'aktif' => true, 'susunan' => 5],
        ];

        foreach ($statuses as $status) {
            DB::table('status_kehadiran')->updateOrInsert(['kod' => $status['kod']], $status);
        }
    }
}
