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
        Schema::table('anggota', function (Blueprint $table) {
            $table->unsignedSmallInteger('tahap_hierarki')->default(100)->after('gred');
            $table->index(['aktif', 'tahap_hierarki', 'susunan', 'nama_penuh'], 'idx_anggota_hierarki');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->dropIndex('idx_anggota_hierarki');
            $table->dropColumn('tahap_hierarki');
        });
    }
};
