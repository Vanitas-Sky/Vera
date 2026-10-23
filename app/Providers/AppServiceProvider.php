<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // Fuerza a Laravel a generar todos los enlaces y assets en HTTPS cuando no esté en localhost directo
        if (request()->server->has('HTTP_X_FORWARDED_PROTO') || str_contains(request()->getHost(), 'ngrok')) {
            URL::forceScheme('https');
        }
        if (str_contains(config('app.url'), 'ngrok') || app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
