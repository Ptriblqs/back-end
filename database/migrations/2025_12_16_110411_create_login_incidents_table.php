<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('login_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('username')->nullable();
            $table->string('role')->nullable();
            $table->string('type');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_incidents');
    }
};
