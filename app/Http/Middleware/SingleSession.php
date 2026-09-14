<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SingleSession
{
    /**
     * Verifica que la sesión del usuario siga siendo válida.
     * Si otro dispositivo inició sesión con la misma cuenta,
     * el token en BD cambiará y esta sesión quedará invalidada.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $sessionToken = $request->session()->get('session_token');

            // Si el token en la sesión no coincide con el token en BD,
            // significa que la cuenta fue usada en otro lugar o se cerró sesión.
            if ($user->session_token !== $sessionToken) {
                // Re-autenticación vía "Recordarme": adopta la sesión (el último en
                // entrar gana) en vez de expulsar de inmediato a quien marcó la casilla.
                // Sin esto, "Recordarme" nunca servía: el reingreso moría aquí siempre.
                if (Auth::viaRemember()) {
                    $nuevo = Str::random(60);
                    $user->update(['session_token' => $nuevo]);
                    $request->session()->put('session_token', $nuevo);
                    return $next($request);
                }

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Los paneles (cocina/admin/caja) consumen JSON: no devolver HTML de login.
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Sesión invalidada. Inicia sesión de nuevo.',
                    ], 401);
                }

                return redirect()->route('login')->withErrors([
                    'email' => 'Tu cuenta fue iniciada en otro dispositivo. Por seguridad, esta sesión fue cerrada.',
                ]);
            }
        }

        return $next($request);
    }
}
