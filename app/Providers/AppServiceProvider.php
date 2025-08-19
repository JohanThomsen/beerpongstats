<?php

namespace App\Providers;

use App\Models\GameUpdate;
use App\Observers\GameUpdateObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        GameUpdate::observe(GameUpdateObserver::class);
    }
}
