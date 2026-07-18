<?php

namespace App\Providers;

use App\HealthChecks\ExtractionBinariesCheck;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Facades\Health;

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
        Health::checks([
            DatabaseCheck::new(),
            RedisCheck::new(),
            QueueCheck::new()->onQueue('documents'),
            ExtractionBinariesCheck::new(),
        ]);

        Gate::define('viewPulse', fn ($user = null): bool => $user?->hasRole('admin') === true);

        RateLimiter::for('api-client', function (Request $request): Limit {
            $client = $request->attributes->get('apiClient');
            $limit = $client?->rate_limit_per_minute ?? 30;

            return Limit::perMinute($limit)->by($client?->id ?? $request->ip());
        });
    }
}
