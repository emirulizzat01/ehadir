<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Optimize rekod_kehadiran foreign keys
        Schema::table('rekod_kehadiran', function (Blueprint $table) {
            // Drop composite foreign key that referenced (id, anggota_id) on anggota_penempatan
            $table->dropForeign(['penempatan_id', 'anggota_id']);

            // Replace with standard single-column foreign key referencing anggota_penempatan(id)
            $table->foreign('penempatan_id')
                ->references('id')
                ->on('anggota_penempatan')
                ->onDelete('restrict');
        });

        // 2. Drop redundant composite unique index on anggota_penempatan
        // id is already PRIMARY KEY, so (id, anggota_id) uniqueness was redundant
        Schema::table('anggota_penempatan', function (Blueprint $table) {
            $table->dropUnique('uq_penempatan_anggota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anggota_penempatan', function (Blueprint $table) {
            $table->unique(['id', 'anggota_id'], 'uq_penempatan_anggota');
        });

        Schema::table('rekod_kehadiran', function (Blueprint $table) {
            $table->dropForeign(['penempatan_id']);

            $table->foreign(['penempatan_id', 'anggota_id'])
                ->references(['id', 'anggota_id'])
                ->on('anggota_penempatan');
        });
    }
};

