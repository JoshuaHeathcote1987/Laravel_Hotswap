<?php

namespace Placeholder\App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // This is where you would bind things to the service container.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load package routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    
        // Load package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../databases/migrations');
    }
}
