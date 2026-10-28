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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 200);
            $table->string('email', 200)->unique();
            $table->string('password_hash', 255);
            $table->boolean('aktif')->default(true);
            $table->dateTime('last_login')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('kod', 40)->unique();
            $table->string('nama', 100);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('kod', 80)->unique();
            $table->string('nama', 150);
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->unsignedSmallInteger('role_id');
            $table->unsignedSmallInteger('permission_id');
            $table->primary(['role_id', 'permission_id']);

            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedSmallInteger('role_id');
            $table->primary(['user_id', 'role_id']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });

        Schema::create('user_scopes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('bahagian_id')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();

            $table->unique(['user_id', 'bahagian_id'], 'uq_scope_bahagian');
            $table->unique(['user_id', 'unit_id'], 'uq_scope_unit');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('bahagian_id')->references('id')->on('bahagian')->onDelete('restrict');
            $table->foreign('unit_id')->references('id')->on('unit')->onDelete('restrict');
        });

        // Add check constraint for exactly one scope (bahagian XOR unit)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE user_scopes ADD CONSTRAINT chk_scope_exactly_one CHECK ((bahagian_id IS NOT NULL AND unit_id IS NULL) OR (bahagian_id IS NULL AND unit_id IS NOT NULL))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_scopes');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
