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
        Schema::create('ajuan_dospem', function (Blueprint $table) {
            $table->id();
            
            // Foreign key ke tabel users (mahasiswa)
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            
            // ✅ BUAT KOLOM DULU, BARU FOREIGN KEY
            $table->string('nim')->nullable();
            $table->unsignedBigInteger('program_studis_id')->nullable();  // ✅ Kolom dulu
            
            // Data dosen yang dipilih
            $table->unsignedBigInteger('dosen_id');
            $table->string('dosen_nik')->nullable();
            $table->string('dosen_nama');
            
            // Data pengajuan
            $table->text('alasan');
            $table->string('judul_ta');
            $table->text('deskripsi_ta');
            $table->text('portofolio');
            
            // Status pengajuan
            $table->enum('status', ['menunggu', 'diterima', 'ditolak'])
                  ->default('menunggu');
            
            // Catatan dari dosen (opsional)
            $table->text('catatan_dosen')->nullable();
            
            // Tanggal disetujui/ditolak
            $table->timestamp('reviewed_at')->nullable();
            
            $table->timestamps();
            
            // ✅ FOREIGN KEY SETELAH SEMUA KOLOM DIBUAT
            $table->foreign('program_studis_id')
                  ->references('id')
                  ->on('program_studis')
                  ->onDelete('set null');
                  
            $table->foreign('dosen_id')
                  ->references('id')
                  ->on('dosen')
                  ->onDelete('cascade');
            
            // Index untuk performa query
            $table->index('user_id');
            $table->index('dosen_id');
            $table->index('nim');
            $table->index('program_studis_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ajuan_dospem');
    }
};