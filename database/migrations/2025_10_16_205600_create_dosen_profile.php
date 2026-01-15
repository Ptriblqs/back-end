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
        Schema::create('dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->ondelete('cascade');
            $table->foreignId('prodi_id')->constrained('program_studis')->ondelete('cascade');
            $table->text('nik');
            $table->text('bidang_keahlian')->nullable();
            $table->timestamps();
            $table->text('no_telepon')->nullable();
            $table->text('integrity_hash')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dosen');
    }
};
