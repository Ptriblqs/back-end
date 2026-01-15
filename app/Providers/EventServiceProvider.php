<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Listeners\LogAuthentication;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class => [
            LogAuthentication::class,
        ],
        Failed::class => [
            LogAuthentication::class,
        ],
        Logout::class => [
            LogAuthentication::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot(); 
    }
}
