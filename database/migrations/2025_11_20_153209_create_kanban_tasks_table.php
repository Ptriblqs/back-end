<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kanban_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Mahasiswa pemilik
            $table->string('title'); // Judul task
            $table->text('description')->nullable(); // Keterangan
            $table->string('status')->default('To Do'); // To Do, In Progress, Done
            $table->dateTime('due_date'); // Tanggal deadline
            $table->boolean('is_expired')->default(false); // Apakah sudah lewat deadline
            $table->timestamps();
            $table->softDeletes(); // Untuk soft delete
        });
    }

    public function down()
    {
        Schema::dropIfExists('kanban_tasks');
    }
};