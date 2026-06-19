<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('password_hash')->nullable();
            $table->enum('role', ['ADMIN', 'MANAGER', 'EMPLOYEE', 'GIT USER'])->default('EMPLOYEE');
            $table->boolean('is_active')->default(1);
            $table->string('github_id')->nullable()->unique();
            $table->string('email')->nullable()->unique();
            $table->string('avatar')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('role');
            $table->index('is_active');
        });

        Schema::create('password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->index();
            $table->string('otp', 10);
            $table->timestamp('expires_at');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('users');
    }
};
