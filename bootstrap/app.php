<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ngrok (y cualquier proxy HTTPS) termina TLS en su borde y reenvía
        // en http interno. Sin esto Laravel ve esquema http + IP del proxy:
        // redirects http<->https en bucle, cookies inseguras y todos los
        // dispositivos compartiendo la misma IP en los throttles.
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'role'           => \App\Http\Middleware\RoleMiddleware::class,
            'single.session' => \App\Http\Middleware\SingleSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Estandarizar respuestas JSON para todas las peticiones API
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Recurso no encontrado.',
                ], 404);
            }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Datos de entrada inválidos.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'No autenticado. Inicia sesión para continuar.',
                ], 401);
            }
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                \Illuminate\Support\Facades\Log::error('[API Error] ' . $e->getMessage(), [
                    'url'   => $request->fullUrl(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json([
                    'success' => false,
                    'error'   => app()->isProduction()
                        ? 'Error interno del servidor.'
                        : $e->getMessage(),
                ], 500);
            }
        });
    })
    ->booted(function () {
        // Consultas del menú público: bucket por IP+ruta. Todos los clientes
        // comparten el WiFi/IP del local; un bucket único los bloquearía entre sí.
        RateLimiter::for('public-api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip() . '|' . $request->path());
        });

        // Pedidos: límite por mesa (no por IP) para no bloquear a mesas vecinas
        // que comparten IP ni a reintentos legítimos de otros clientes.
        RateLimiter::for('crear-pedido', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip() . '|mesa:' . $request->input('mesa_id'));
        });
    })
    ->create();
