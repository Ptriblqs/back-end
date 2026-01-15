<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('login_attempts')->default(0)->after('password');
            $table->timestamp('last_failed_login')->nullable()->after('login_attempts');
            $table->boolean('is_blocked')->default(false)->after('last_failed_login');
            $table->timestamp('blocked_at')->nullable()->after('is_blocked');
            $table->string('blocked_reason')->nullable()->after('blocked_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'login_attempts', 
                'last_failed_login', 
                'is_blocked', 
                'blocked_at',
                'blocked_reason'
            ]);
        });
    }
};