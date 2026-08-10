<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Empleado;
use App\Models\Role;

class InventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        
        // Crear rol Administrador
        Role::create(['id' => 1, 'name' => 'Administrador', 'description' => 'Admin']);
    }

    public function test_admin_can_store_reposicion_with_valid_pin()
    {
        $admin = Usuario::create([
            'nombres' => 'Admin',
            'apellidos' => 'Test',
            'email' => 'admin_inv@mrgiova.com',
            'password' => Hash::make('secret123'),
            'rol_id' => 1,
            'activo' => true,
        ]);
        
        $empleado = Empleado::create([
            'usuario_id' => $admin->id,
            'cargo' => 'Gerente',
            'fecha_contratacion' => now(),
            'sueldo' => 1000,
            'turno' => 'Rotativo'
        ]);

        $response = $this->actingAs($admin)->postJson('/api/admin/insumos/pedido', [
            'pin' => 'secret123'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
                 
        $this->assertDatabaseHas('pedidos_proveedor', [
            'empleado_id' => $empleado->id,
            'estado' => 'Aprobado'
        ]);
    }

    public function test_admin_cannot_store_reposicion_with_invalid_pin()
    {
        $admin = Usuario::create([
            'nombres' => 'Admin',
            'apellidos' => 'Test',
            'email' => 'admin_inv2@mrgiova.com',
            'password' => Hash::make('secret123'),
            'rol_id' => 1,
            'activo' => true,
        ]);
        
        Empleado::create([
            'usuario_id' => $admin->id,
            'cargo' => 'Gerente',
            'fecha_contratacion' => now(),
            'sueldo' => 1000,
            'turno' => 'Rotativo'
        ]);

        $response = $this->actingAs($admin)->postJson('/api/admin/insumos/pedido', [
            'pin' => 'wrongpin'
        ]);

        $response->assertStatus(403)
                 ->assertJson(['success' => false, 'error' => 'PIN de autorización inválido.']);
    }
}
