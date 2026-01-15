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
        Schema::create('auth_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('event'); // login, logout, failed
            $table->string('ip')->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->string('session_id')->nullable();
            $table->string('location')->nullable(); // negara/kota (opsional)
            $table->string('reason')->nullable(); // e.g. invalid_password
            $table->boolean('suspicious')->default(false);
            $table->json('meta')->nullable(); // tambahan
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_logs');
    }
};
