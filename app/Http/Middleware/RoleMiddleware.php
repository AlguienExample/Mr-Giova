<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No autenticado'], 401);
            }
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        // Cargar relación de rol si no está cargada
        if (!$user->relationLoaded('rol')) {
            $user->load('rol');
        }

        if (!$user->rol || !in_array($user->rol->name, $roles)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No tienes permisos para esta acción.'], 403);
            }
            // Redirección suave: llevar al usuario a su panel correcto según su rol
            $roleName = $user->rol ? $user->rol->name : null;
            $dashboardCorrecto = match($roleName) {
                'Administrador' => '/admin',
                'Cocinero'      => '/cocina',
                'Cajero'        => '/caja',
                default         => null,
            };

            if ($dashboardCorrecto) {
                return redirect($dashboardCorrecto)->with('warning', 'La sesión activa ha cambiado. Has sido redirigido a tu panel actual.');
            }

            // Sin tablero asignado: pasar por el login (que explica el motivo con el
            // mensaje estándar) en vez de destruir la sesión por sorpresa aquí.
            return redirect()->route('login');
        }

        return $next($request);
    }
}
