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
        Schema::create('tugas_akhir', function(Blueprint $table){
            $table->id();
            $table->foreignId('id_mahasiswa')->constrained('mahasiswa')->ondelete('cascade');
            $table->foreignId('id_dosen')->constrained('dosen')->ondelete('cascade');
            $table->string('judul_tugas');
            $table->date('tenggat_waktu');
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['disetujui', 'ditolak', 'menunggu'])->default('menunggu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExist('tugas_akhir');
    }
};
