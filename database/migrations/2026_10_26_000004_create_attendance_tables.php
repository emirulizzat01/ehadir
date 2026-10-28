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
        Schema::create('status_kehadiran', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('kod', 40)->unique();
            $table->string('nama', 100);
            $table->char('warna', 7)->default('#64748B');
            $table->boolean('aktif')->default(true);
            $table->unsignedSmallInteger('susunan')->default(0);
        });

        Schema::create('rekod_kehadiran', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anggota_id');
            $table->unsignedBigInteger('penempatan_id');
            $table->unsignedSmallInteger('status_id');
            $table->date('tarikh');
            $table->boolean('sepanjang_hari')->default(true);
            $table->time('masa_mula')->nullable();
            $table->time('masa_tamat')->nullable();
            $table->string('lokasi', 255)->nullable();
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['anggota_id', 'tarikh'], 'uq_harian');
            $table->index(['tarikh', 'status_id'], 'idx_calendar');
            $table->index(['penempatan_id', 'tarikh'], 'idx_kehadiran_penempatan');

            $table->foreign('anggota_id')->references('id')->on('anggota');
            $table->foreign(['penempatan_id', 'anggota_id'])
                ->references(['id', 'anggota_id'])
                ->on('anggota_penempatan');
            $table->foreign('status_id')->references('id')->on('status_kehadiran');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        // Add check constraint for time range
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE rekod_kehadiran ADD CONSTRAINT chk_kehadiran_time CHECK (masa_mula IS NULL OR masa_tamat IS NULL OR masa_tamat > masa_mula)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekod_kehadiran');
        Schema::dropIfExists('status_kehadiran');
    }
};
