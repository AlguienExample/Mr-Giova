<?php

namespace App\Providers;

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
        // ngrok termina TLS en su borde y reenvía en http interno. Con
        // trustProxies (bootstrap/app.php) request()->isSecure() ya detecta
        // el esquema real: https en ngrok, http en localhost. Forzar https a
        // ciegas rompía http://localhost; por eso solo se fuerza cuando la
        // petición entrante ya es segura. En consola (correos de reset) se
        // usa el esquema de APP_URL.
        try {
            if (!$this->app->runningInConsole() && request()->isSecure()) {
                \URL::forceScheme('https');
            } elseif ($this->app->runningInConsole() && str_starts_with((string) config('app.url'), 'https://')) {
                \URL::forceScheme('https');
            }
        } catch (\Throwable) {
            // Sin request disponible (tests): no forzar nada.
        }
    }
}
