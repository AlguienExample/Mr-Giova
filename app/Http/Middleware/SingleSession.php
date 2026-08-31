<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

            // Si el token en la sesión no coincide con el token en BD (cuando hay un token configurado),
            // significa que la cuenta fue usada en otro lugar.
            if ($user->session_token !== null && $user->session_token !== $sessionToken) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Tu cuenta fue iniciada en otro dispositivo. Por seguridad, esta sesión fue cerrada.',
                ]);
            }
        }

        return $next($request);
    }
}
