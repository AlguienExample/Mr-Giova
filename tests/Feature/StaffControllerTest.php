<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Role;

class StaffControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Role::create(['id' => 1, 'name' => 'Administrador', 'description' => 'Admin']);
        Role::create(['id' => 2, 'name' => 'Cocinero',      'description' => 'Cocina']);
        Role::create(['id' => 3, 'name' => 'Cajero',        'description' => 'Caja']);
    }

    private function adminUser(): Usuario
    {
        return Usuario::create([
            'nombres'   => 'Admin',
            'apellidos' => 'Test',
            'email'     => 'admin@mrgiova.com',
            'password'  => Hash::make('secret'),
            'rol_id'    => 1,
            'activo'    => true,
        ]);
    }

    public function test_can_create_staff_with_correct_role_and_generates_password()
    {
        $response = $this->actingAs($this->adminUser())
            ->postJson('/api/admin/staff', [
                'nombres' => 'Maria Lopez',
                'cargo'   => 'Chef Ejecutivo',
                'rol'     => 'Cocinero',
            ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure([
                     'success',
                     'credenciales' => ['email', 'password'],
                 ]);

        // Verificar que el usuario creado tiene el rol Cocinero (id 2)
        $this->assertDatabaseHas('usuarios', [
            'nombres' => 'Maria',
            'rol_id'  => 2,
        ]);
        
        // Verificar que la contraseña devuelta no es 'haute123'
        $this->assertNotEquals('haute123', $response->json('credenciales.password'));
    }
    
    public function test_cannot_create_staff_with_invalid_role()
    {
        $response = $this->actingAs($this->adminUser())
            ->postJson('/api/admin/staff', [
                'nombres' => 'Pedro Garcia',
                'cargo'   => 'Mesero',
                'rol'     => 'RolInexistente',
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('usuarios', ['nombres' => 'Pedro']);
    }
    
    public function test_cannot_create_staff_without_role_field()
    {
        $response = $this->actingAs($this->adminUser())
            ->postJson('/api/admin/staff', [
                'nombres' => 'Ana Torres',
                'cargo'   => 'Mesero',
                // Falta 'rol'
            ]);

        $response->assertStatus(422);
    }
}
