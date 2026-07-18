<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('api-client', function (Request $request): Limit {
            $client = $request->attributes->get('apiClient');
            $limit = $client?->rate_limit_per_minute ?? 30;

            return Limit::perMinute($limit)->by($client?->id ?? $request->ip());
        });
    }
}
