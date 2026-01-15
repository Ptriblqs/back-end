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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('nama_lengkap');
            $table->string('email')->unique()->nullable();
            $table->string('foto_profil')->nullable();
            $table->string('password');
            $table->enum('role', ['mahasiswa', 'dosen', 'admin'])->default('mahasiswa');
            $table->timestamps();
            $table->text('integrity_hash')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
