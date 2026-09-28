<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
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
        Paginator::useBootstrapFive();

        // Keep generated route/asset URLs free of "/public"
        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '') {
            URL::forceRootUrl(preg_replace('#/public$#i', '', $appUrl));
        }
    }
}
