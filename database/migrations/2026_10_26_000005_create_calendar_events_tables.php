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
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('tajuk', 255);
            $table->enum('jenis', ['MESYUARAT', 'BENGKEL', 'KURSUS', 'LAWATAN_TAPAK', 'LAIN_LAIN'])->default('LAIN_LAIN');
            $table->dateTime('mula');
            $table->dateTime('tamat');
            $table->boolean('sepanjang_hari')->default(false);
            $table->string('lokasi', 255)->nullable();
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['mula', 'tamat'], 'idx_event_dates');

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        // Add check constraint for event dates
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE calendar_events ADD CONSTRAINT chk_event_dates CHECK (tamat > mula)');
        }

        Schema::create('calendar_event_attendees', function (Blueprint $table) {
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('anggota_id');
            $table->primary(['event_id', 'anggota_id']);

            $table->index(['anggota_id', 'event_id'], 'idx_attendee_anggota');

            $table->foreign('event_id')->references('id')->on('calendar_events')->onDelete('cascade');
            $table->foreign('anggota_id')->references('id')->on('anggota')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_event_attendees');
        Schema::dropIfExists('calendar_events');
    }
};
