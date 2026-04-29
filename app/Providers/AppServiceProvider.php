<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Ai\Contracts\LaravelAiSdkGateway as LaravelAiSdkGatewayContract;
use App\Services\Ai\LaravelAiSdkGateway;
use App\Support\Seo\SeoSettingsResolver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LaravelAiSdkGatewayContract::class, LaravelAiSdkGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability): bool|null {
            if ($user->hasRole('super-admin')) {
                return true;
            }

            return null;
        });

        Vite::prefetch(concurrency: 3);

        View::composer('app', function ($view): void {
            $settings = app(SeoSettingsResolver::class)->resolve();
            $view->with('seoSettings', $settings);
        });

    }
}
