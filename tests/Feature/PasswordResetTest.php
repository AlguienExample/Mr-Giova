<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ResetPasswordNotification;
use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Role;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private Role $rol;

    public function setUp(): void
    {
        parent::setUp();
        $this->rol = Role::create([
            'name'        => 'Administrador',
            'description' => 'Admin',
        ]);
    }

    // ─── Helper ────────────────────────────────────────────────────────────
    private function crearUsuario(array $attrs = []): Usuario
    {
        return Usuario::create(array_merge([
            'nombres'   => 'Test',
            'apellidos' => 'Usuario',
            'email'     => 'camilojimenez24712@gmail.com',
            'password'  => Hash::make('password123'),
            'rol_id'    => $this->rol->id,
            'activo'    => true,
        ], $attrs));
    }

    // ─── Test 1 ────────────────────────────────────────────────────────────
    public function test_se_puede_solicitar_enlace_de_reset_con_email_existente(): void
    {
        Notification::fake();

        $usuario = $this->crearUsuario();

        $response = $this->post('/forgot-password', [
            'email' => $usuario->email,
        ]);

        // Redirige de vuelta al formulario (redirect 302)
        $response->assertStatus(302);
        $response->assertSessionHas(
            'status',
            'Si el correo existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña.'
        );
        $response->assertSessionHasNoErrors();

        // La notificación personalizada sí fue enviada al usuario existente
        Notification::assertSentTo($usuario, ResetPasswordNotification::class);
    }

    // ─── Test 2 ────────────────────────────────────────────────────────────
    public function test_solicitar_reset_con_email_inexistente_no_revela_que_no_existe(): void
    {
        Notification::fake();

        // Crear un usuario para tener una respuesta "existente" de referencia
        $usuario = $this->crearUsuario();

        // Respuesta con email EXISTENTE
        $responseExistente = $this->post('/forgot-password', [
            'email' => $usuario->email,
        ]);

        // Respuesta con email INEXISTENTE
        $responseInexistente = $this->post('/forgot-password', [
            'email' => 'nadie@nowhere.invalid',
        ]);

        // ── Aserciones de igualdad estricta ──────────────────────────────

        // 1. Mismo código de respuesta HTTP
        $this->assertSame(
            $responseExistente->getStatusCode(),
            $responseInexistente->getStatusCode(),
            'Los status codes deben ser idénticos para email existente e inexistente.'
        );

        // 2. Mismo mensaje en session('status')
        $responseExistente->assertSessionHas('status', 'Si el correo existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña.');
        $responseInexistente->assertSessionHas('status', 'Si el correo existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña.');

        // 3. Ninguna de las dos respuestas tiene errores de sesión
        $responseExistente->assertSessionHasNoErrors();
        $responseInexistente->assertSessionHasNoErrors();

        // 4. Se envió exactamente 1 notificación en total: la del email existente.
        //    Ninguna se generó para el email inexistente, ya que no hay ningún Usuario
        //    con esa dirección al que notificar.
        Notification::assertSentTimes(ResetPasswordNotification::class, 1);
    }

    // ─── Test 3 ────────────────────────────────────────────────────────────
    public function test_se_puede_restablecer_contrasena_con_token_valido(): void
    {
        $usuario = $this->crearUsuario();

        // Generar token real via el broker
        $token = Password::broker()->createToken($usuario);

        $nuevaPassword = 'NuevaPassword123';

        $response = $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $usuario->email,
            'password'              => $nuevaPassword,
            'password_confirmation' => $nuevaPassword,
        ]);

        // Redirige al login con mensaje de éxito
        $response->assertRedirect('/login');
        $response->assertSessionHas(
            'success',
            'Tu contraseña ha sido restablecida correctamente. Inicia sesión con tus nuevas credenciales.'
        );

        // El hash de la contraseña cambió en la base de datos
        $usuario->refresh();
        $this->assertTrue(
            Hash::check($nuevaPassword, $usuario->password),
            'El hash de la nueva contraseña debe ser válido.'
        );
        $this->assertFalse(
            Hash::check('password123', $usuario->password),
            'La contraseña antigua ya no debe ser válida.'
        );

        // El usuario puede loguearse con la nueva contraseña
        $loginResponse = $this->post('/login', [
            'email'    => $usuario->email,
            'password' => $nuevaPassword,
        ]);
        $loginResponse->assertRedirect('/admin'); // rol Administrador
        $this->assertAuthenticated();
    }

    // ─── Test 4 ────────────────────────────────────────────────────────────
    public function test_no_se_puede_restablecer_con_token_invalido_o_expirado(): void
    {
        $usuario = $this->crearUsuario();

        $response = $this->post('/reset-password', [
            'token'                 => 'token-completamente-invalido-xyz',
            'email'                 => $usuario->email,
            'password'              => 'NuevaPassword123',
            'password_confirmation' => 'NuevaPassword123',
        ]);

        // Vuelve al formulario con error genérico (no distingue motivo exacto)
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email']);

        $response->assertSessionHasErrors([
            'email' => 'El enlace es inválido o expiró. Solicita uno nuevo.',
        ]);

        // La contraseña NO cambió
        $usuario->refresh();
        $this->assertTrue(
            Hash::check('password123', $usuario->password),
            'La contraseña original debe permanecer intacta.'
        );
    }

    // ─── Test 5 ────────────────────────────────────────────────────────────
    public function test_restablecer_contrasena_invalida_la_sesion_activa(): void
    {
        $usuario = $this->crearUsuario();

        // Simular sesión activa: session_token tiene un valor no nulo
        $usuario->update(['session_token' => 'token-de-sesion-activa-simulada']);
        $this->assertNotNull($usuario->fresh()->session_token);

        // Generar token de reset real
        $token = Password::broker()->createToken($usuario);

        $response = $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $usuario->email,
            'password'              => 'NuevaPassword123',
            'password_confirmation' => 'NuevaPassword123',
        ]);

        $response->assertRedirect('/login');

        // session_token debe haber quedado en null tras el reset
        $usuario->refresh();
        $this->assertNull(
            $usuario->session_token,
            'El session_token debe ser null después de un reset exitoso.'
        );

        // La contraseña también cambió correctamente
        $this->assertTrue(
            Hash::check('NuevaPassword123', $usuario->password)
        );
    }
}
