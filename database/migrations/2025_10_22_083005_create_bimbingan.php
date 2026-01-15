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
        Schema::create('bimbingan', function (Blueprint $table) {
            $table->id();
            
            // Relasi
            $table->unsignedBigInteger('mahasiswa_id');
            $table->unsignedBigInteger('dosen_id');
            
            // Informasi Bimbingan
            $table->string('judul')->comment('Judul/topik bimbingan');
            $table->string('lokasi')->comment('Lokasi bimbingan');
            
            // Waktu
            $table->date('tanggal')->comment('Tanggal bimbingan');
            $table->time('waktu')->comment('Jam bimbingan');
            $table->dateTime('waktu_mulai')->nullable()->comment('Waktu mulai (opsional)');
            $table->dateTime('waktu_selesai')->nullable()->comment('Waktu selesai (opsional)');
            
            // Detail
            $table->text('catatan')->nullable()->comment('Catatan tambahan');
            $table->enum('jenis_bimbingan', ['online', 'offline'])->default('offline');
            
            // Status & Approval
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak', 'ajuan_dosen'])
                  ->default('menunggu')
                  ->comment('Status persetujuan');
            $table->text('alasan_penolakan')->nullable()->comment('Alasan jika ditolak');
            $table->enum('pengaju', ['mahasiswa', 'dosen'])->default('mahasiswa');
            
            // Timestamps
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('mahasiswa_id')->references('id')->on('mahasiswa')->onDelete('cascade');
            $table->foreign('dosen_id')->references('id')->on('dosen')->onDelete('cascade');
            
            // Indexes
            $table->index('mahasiswa_id');
            $table->index('dosen_id');
            $table->index('status');
            $table->index('tanggal');
            $table->index(['mahasiswa_id', 'status']);
            $table->index(['dosen_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bimbingan');
    }
};
