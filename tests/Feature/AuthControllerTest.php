<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Role;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        
        // Crear roles
        Role::create(['id' => 1, 'name' => 'Administrador', 'description' => 'Admin']);
        Role::create(['id' => 2, 'name' => 'Cocinero', 'description' => 'Cocina']);
        Role::create(['id' => 3, 'name' => 'Cajero', 'description' => 'Caja']);
    }

    public function test_cajero_is_redirected_to_caja_after_login()
    {
        $cajero = Usuario::create([
            'nombres' => 'Cajero',
            'apellidos' => 'Prueba',
            'email' => 'cajero@mrgiova.com',
            'password' => Hash::make('password'),
            'rol_id' => 3, // Cajero
            'activo' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'cajero@mrgiova.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/caja');
        $this->assertAuthenticatedAs($cajero);
    }
    
    public function test_admin_is_redirected_to_admin_after_login()
    {
        $admin = Usuario::create([
            'nombres' => 'Admin',
            'apellidos' => 'Prueba',
            'email' => 'admin@mrgiova.com',
            'password' => Hash::make('password'),
            'rol_id' => 1, // Admin
            'activo' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@mrgiova.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
    }

    public function test_second_login_is_blocked_while_a_session_is_active()
    {
        $admin = Usuario::create([
            'nombres'   => 'Admin',
            'apellidos' => 'Block',
            'email'     => 'admin.block@mrgiova.com',
            'password'  => \Illuminate\Support\Facades\Hash::make('password'),
            'rol_id'    => 1,
            'activo'    => true,
        ]);

        // Primer login exitoso: establece session_token en BD
        $this->post('/login', [
            'email'    => 'admin.block@mrgiova.com',
            'password' => 'password',
        ])->assertRedirect('/admin');

        $tokenOriginal = $admin->fresh()->session_token;
        $this->assertNotNull($tokenOriginal);

        // Segundo login SIN forzar_sesion: debe ser bloqueado
        $response = $this->post('/login', [
            'email'    => 'admin.block@mrgiova.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertSessionHas('sesion_activa', true);
        $this->assertGuest();

        // El token en BD NO debe haber cambiado
        $this->assertEquals($tokenOriginal, $admin->fresh()->session_token);
    }

    public function test_second_login_succeeds_when_forzar_sesion_is_confirmed()
    {
        $admin = Usuario::create([
            'nombres'   => 'Admin',
            'apellidos' => 'Force',
            'email'     => 'admin.force@mrgiova.com',
            'password'  => \Illuminate\Support\Facades\Hash::make('password'),
            'rol_id'    => 1,
            'activo'    => true,
        ]);

        // Primer login exitoso
        $this->post('/login', [
            'email'    => 'admin.force@mrgiova.com',
            'password' => 'password',
        ])->assertRedirect('/admin');

        $tokenOriginal = $admin->fresh()->session_token;
        $this->assertNotNull($tokenOriginal);

        // Segundo login CON forzar_sesion=1: debe ser exitoso
        $response = $this->post('/login', [
            'email'         => 'admin.force@mrgiova.com',
            'password'      => 'password',
            'forzar_sesion' => '1',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticated();

        // El token en BD debe haber cambiado
        $this->assertNotEquals($tokenOriginal, $admin->fresh()->session_token);
    }
}
