<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Muestra la vista de login.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }
        return view('login');
    }

    /**
     * Procesa la solicitud de inicio de sesión.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'Por favor introduce un correo válido.',
            'password.required' => 'La contraseña es requerida.',
        ]);

        if (Auth::attempt($credentials, $request->has('remember'))) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            // Validar que el usuario esté activo
            if (!$user->activo) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Tu cuenta está inactiva. Contacta al administrador.',
                ]);
            }

            // Generar un token único para esta sesión (sesión única por usuario)
            $sessionToken = Str::random(60);
            $user->update(['session_token' => $sessionToken]);
            $request->session()->put('session_token', $sessionToken);

            return $this->redirectBasedOnRole($user);
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    /**
     * Cierra la sesión activa.
     */
    public function logout(Request $request)
    {
        // Limpiar el token de sesión en la BD para invalidar cualquier otra sesión activa
        if ($user = Auth::user()) {
            $user->update(['session_token' => null]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Sesión cerrada correctamente.');
    }

    /**
     * Redirige al panel correspondiente de acuerdo con el rol.
     */
    protected function redirectBasedOnRole($user)
    {
        $user->load('rol');
        
        if ($user->rol && $user->rol->name === 'Administrador') {
            return redirect()->intended('/admin');
        } elseif ($user->rol && $user->rol->name === 'Cocinero') {
            return redirect()->intended('/cocina');
        } elseif ($user->rol && $user->rol->name === 'Cajero') {
            return redirect()->intended('/caja');
        }

        // Si es otro rol (como Mesero o Cliente) que no tiene tablero específico asignado
        Auth::logout();
        return redirect('/login')->withErrors([
            'email' => 'Tu rol (' . ($user->rol ? $user->rol->name : 'Ninguno') . ') no cuenta con un tablero asignado.',
        ]);
    }
}
