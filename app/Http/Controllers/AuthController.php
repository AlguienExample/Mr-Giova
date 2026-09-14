<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;

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

            // Bloquear segundo login si ya hay una sesión activa en otro dispositivo.
            // Chequeo y asignación atómicos bajo lock: dos logins concurrentes
            // (doble clic, reintento) no pueden pasar el chequeo a la vez.
            $sessionToken = Str::random(60);
            $tomado = DB::transaction(function () use ($user, $request, $sessionToken) {
                $u = Usuario::lockForUpdate()->find($user->id);
                if (!$u) {
                    return false;
                }
                if ($u->session_token !== null && !$request->boolean('forzar_sesion')) {
                    return false;
                }
                $u->update(['session_token' => $sessionToken]);
                return true;
            });

            if (!$tomado) {
                Auth::logout();
                return back()
                    ->withErrors(['email' => 'Ya existe una sesión activa con esta cuenta en otro dispositivo.'])
                    ->onlyInput('email')
                    ->with('sesion_activa', true);
            }

            // Sesión única por usuario
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

    /**
     * Muestra el formulario para solicitar enlace de recuperación de contraseña.
     */
    public function showForgotPasswordForm()
    {
        return view('forgot-password');
    }

    /**
     * Envía el enlace de recuperación de contraseña.
     * Respuesta idéntica para email existente e inexistente (anti user-enumeration).
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email'], [
            'email.required' => 'El correo electrónico es requerido.',
            'email.email'    => 'Por favor introduce un correo válido.',
        ]);

        $status = Password::broker()->sendResetLink(
            $request->only('email')
        );

        // Solo RESET_THROTTLED recibe respuesta distinta (no revela existencia del email,
        // solo que hay throttling activo para esa dirección).
        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors([
                'email' => 'Has solicitado un enlace recientemente. Por favor espera unos minutos antes de intentarlo de nuevo.',
            ]);
        }

        // Para RESET_LINK_SENT *y* para INVALID_USER (email no existe):
        // exactamente el mismo mensaje, exactamente el mismo redirect.
        // No se puede distinguir desde afuera si el email está o no registrado.
        return back()->with(
            'status',
            'Si el correo existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña.'
        );
    }

    /**
     * Muestra el formulario para restablecer la contraseña.
     */
    public function showResetPasswordForm($token, Request $request)
    {
        return view('reset-password', ['token' => $token]);
    }

    /**
     * Restablece la contraseña usando el token del enlace.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'                 => 'required',
            'email'                 => 'required|email',
            'password'              => 'required|min:8|confirmed',
        ], [
            'password.required'     => 'La nueva contraseña es requerida.',
            'password.min'          => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'    => 'Las contraseñas no coinciden.',
            'email.required'        => 'El correo electrónico es requerido.',
            'email.email'           => 'Por favor introduce un correo válido.',
            'token.required'        => 'El token de recuperación es requerido.',
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Usuario $user, string $password) {
                // 1. Actualizar contraseña e invalidar remember_token
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // 2. Invalidar cualquier sesión única activa de este usuario
                //    (reutilizando la misma columna session_token del sistema de sesión única)
                $user->update(['session_token' => null]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('success', 'Tu contraseña ha sido restablecida correctamente. Inicia sesión con tus nuevas credenciales.');
        }

        // Mensaje genérico para cualquier otro error (token inválido, expirado, email incorrecto)
        // Sin distinguir el motivo exacto para no revelar información.
        return back()->withErrors([
            'email' => 'El enlace es inválido o expiró. Solicita uno nuevo.',
        ]);
    }
}
