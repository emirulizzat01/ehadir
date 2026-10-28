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
            $table->unsignedBigInteger('onestack_id')->nullable()->after('id')->index();
            $table->string('gred', 20)->nullable()->after('jawatan');
            $table->string('emel', 200)->nullable()->after('gred');
            $table->string('no_telefon', 30)->nullable()->after('emel');
            $table->date('tarikh_bersara')->nullable()->after('aktif');
            $table->timestamp('last_synced_at')->nullable()->after('susunan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->dropColumn([
                'onestack_id',
                'gred',
                'emel',
                'no_telefon',
                'tarikh_bersara',
                'last_synced_at',
            ]);
        });
    }
};

