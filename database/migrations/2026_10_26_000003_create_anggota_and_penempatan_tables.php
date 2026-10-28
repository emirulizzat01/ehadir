<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('anggota', function (Blueprint $table) {
            $table->id();
            $table->string('no_pekerja', 60)->nullable()->unique();
            $table->string('nama_penuh', 200);
            $table->string('gelaran', 40)->nullable();
            $table->string('jawatan', 150)->nullable();
            $table->boolean('aktif')->default(true);
            $table->unsignedInteger('susunan')->default(0);
            $table->timestamps();
        });

        Schema::create('anggota_penempatan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anggota_id');
            $table->unsignedBigInteger('bahagian_id')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->date('tarikh_mula');
            $table->date('tarikh_tamat')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['anggota_id', 'tarikh_mula'], 'uq_anggota_mula');
            $table->unique(['id', 'anggota_id'], 'uq_penempatan_anggota');

            $table->index(['bahagian_id', 'tarikh_mula', 'tarikh_tamat'], 'idx_penempatan_bahagian');
            $table->index(['unit_id', 'tarikh_mula', 'tarikh_tamat'], 'idx_penempatan_unit');

            $table->foreign('anggota_id')->references('id')->on('anggota')->onDelete('restrict');
            $table->foreign('bahagian_id')->references('id')->on('bahagian')->onDelete('restrict');
            $table->foreign('unit_id')->references('id')->on('unit')->onDelete('restrict');
        });

        // Add check constraints for anggota_penempatan
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE anggota_penempatan ADD CONSTRAINT chk_penempatan_exactly_one CHECK ((bahagian_id IS NOT NULL AND unit_id IS NULL) OR (bahagian_id IS NULL AND unit_id IS NOT NULL))');
            DB::statement('ALTER TABLE anggota_penempatan ADD CONSTRAINT chk_penempatan_dates CHECK (tarikh_tamat IS NULL OR tarikh_tamat >= tarikh_mula)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anggota_penempatan');
        Schema::dropIfExists('anggota');
    }
};
