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
}
