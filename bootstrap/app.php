<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\RoleMiddleware;
use App\Providers\EventServiceProvider;
use App\Console\Commands\MigrateInOrder;
use App\Console\Commands\RestoreDatabase;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(HandleCors::class);
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
        // $middleware->web(append: [
        //     \Fruitcake\Cors\HandleCors::class,
        // ]);
        // $middleware->api(append: [
        //     \Fruitcake\Cors\HandleCors::class,
        // ]);
    })
    ->withCommands([
        MigrateInOrder::class,
        RestoreDatabase::class,
    ])
    ->withProviders([
        EventServiceProvider::class,
    ])
    ->withSchedule(function (Schedule $schedule) {
        // Jalankan setiap hari
        $schedule->command('app:hapus-tugas-akhir')->daily();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
