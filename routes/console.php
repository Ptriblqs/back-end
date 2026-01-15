<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\HapusTugasAkhir;

Schedule::command('backup:run')->hourly();

Artisan::command('restore:latest', function () {
    Artisan::call('backup:restore');
    $this->info(Artisan::output());
})->purpose('Menjalankan restore database lewat class DatabaseRestore');

Schedule::command(HapusTugasAkhir::class)->daily();


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
