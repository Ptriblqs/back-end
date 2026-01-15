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
        Schema::create('dokumen', function (Blueprint $table) {
            $table->id();

            // Relasi
            $table->unsignedBigInteger('mahasiswa_id');
            $table->unsignedBigInteger('dosen_id');

            // Data dokumen
            $table->string('judul');
            $table->string('bab')->nullable();
            $table->text('deskripsi')->nullable();

            // File
            $table->string('file_path');
            $table->string('file_revisi_path')->nullable();

            // Status
            $table->enum('status', ['Menunggu', 'Revisi', 'Disetujui'])->default('Menunggu');
            $table->integer('revisi')->default(0);
            $table->text('catatan_revisi')->nullable();

            // Tanggal
            $table->timestamp('tanggal_upload')->nullable();
            $table->timestamp('tanggal_revisi')->nullable();

            $table->timestamps();

            // Foreign key
            $table->foreign('mahasiswa_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('dosen_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumen');
    }
};
