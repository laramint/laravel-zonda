<?php

namespace App\Providers;

use App\Sandbox\SandboxManagerFactory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        $this->app->singleton(SandboxManagerFactory::class, fn () => new SandboxManagerFactory());
    }
}
