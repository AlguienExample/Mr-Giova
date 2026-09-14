<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Mesa;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Pedido;
use App\Models\Role;
use App\Models\Usuario;
use App\Models\Empleado;
use Illuminate\Support\Facades\Hash;

class PedidoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $producto;
    protected $mesa;
    protected $admin;

    public function setUp(): void
    {
        parent::setUp();

        // Create Roles (la migración ya siembra 'Cajero': firstOrCreate + IDs dinámicos)
        $rolAdmin = Role::firstOrCreate(['name' => 'Administrador'], ['description' => 'Admin']);
        Role::firstOrCreate(['name' => 'Cocinero'], ['description' => 'Cocina']);
        Role::firstOrCreate(['name' => 'Cajero'], ['description' => 'Caja']);
        Role::firstOrCreate(['name' => 'Cliente'], ['description' => 'Cliente']);

        // Create a Category
        $categoria = Categoria::create(['nombre' => 'Platos Fuertes']);

        // Create a Product with stock = 10
        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Lomo Saltado',
            'precio' => 15.50,
            'stock' => 10,
            'disponible' => true
        ]);

        // Create a Mesa
        $this->mesa = Mesa::create([
            'numero_mesa' => 1,
            'capacidad' => 4,
            'estado' => 'Disponible',
            'codigo_qr' => 'qr_test_1'
        ]);

        // Create an Admin user for protected routes (if any)
        $this->admin = Usuario::create([
            'nombres' => 'Admin',
            'apellidos' => 'Test',
            'email' => 'admin@mrgiova.com',
            'password' => Hash::make('password'),
            'rol_id' => $rolAdmin->id,
            'activo' => true
        ]);
        
        Empleado::create([
            'usuario_id' => $this->admin->id,
            'cargo' => 'Gerente',
            'fecha_contratacion' => now(),
            'sueldo' => 1000
        ]);
    }

    public function test_can_create_order_and_deduct_stock()
    {
        $payload = [
            'mesa_id' => $this->mesa->id,
            'items' => [
                [
                    'producto_id' => $this->producto->id,
                    'cantidad' => 2,
                    'notas_especiales' => 'Sin cebolla'
                ]
            ],
            'notas' => 'Pedido de prueba'
        ];

        // Ensure throttle doesn't block us in testing
        $response = $this->postJson('/api/pedidos', $payload);

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'pedido_id', 'total']);

        // Verify stock was deducted (10 - 2 = 8)
        $this->assertEquals(8, $this->producto->fresh()->stock);
        
        // Verify table status changed
        $this->assertEquals('Ocupada', $this->mesa->fresh()->estado);
    }

    public function test_cannot_create_order_with_insufficient_stock()
    {
        $payload = [
            'mesa_id' => $this->mesa->id,
            'items' => [
                [
                    'producto_id' => $this->producto->id,
                    'cantidad' => 15, // Only 10 available
                ]
            ]
        ];

        $response = $this->postJson('/api/pedidos', $payload);

        $response->assertStatus(422)
                 ->assertJsonFragment(['error' => "Stock insuficiente para el producto 'Lomo Saltado'. Disponible: 10"]);

        // Stock should remain unchanged
        $this->assertEquals(10, $this->producto->fresh()->stock);
        
        // Ensure table was not marked as occupied because it rolled back
        // The transaction rolls back the mesa state change too
        $this->assertEquals('Disponible', $this->mesa->fresh()->estado);
    }

    public function test_canceling_order_restores_stock()
    {
        // First create an order
        $createResponse = $this->postJson('/api/pedidos', [
            'mesa_id' => $this->mesa->id,
            'items' => [
                ['producto_id' => $this->producto->id, 'cantidad' => 3]
            ]
        ]);
        
        $pedidoId = $createResponse->json('pedido_id');
        $this->assertEquals(7, $this->producto->fresh()->stock);

        // Authenticate as Cocinero or Admin to change status
        $this->actingAs($this->admin);

        // Cancel order
        $response = $this->postJson("/api/pedidos/{$pedidoId}/estado", [
            'estado' => 'Cancelado'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        // Verify stock is restored (7 + 3 = 10)
        $this->assertEquals(10, $this->producto->fresh()->stock);
        
        // Verify table is released
        $this->assertEquals('Disponible', $this->mesa->fresh()->estado);
    }

    public function test_double_cancellation_is_prevented()
    {
        // Create order
        $createResponse = $this->postJson('/api/pedidos', [
            'mesa_id' => $this->mesa->id,
            'items' => [
                ['producto_id' => $this->producto->id, 'cantidad' => 2]
            ]
        ]);
        
        $pedidoId = $createResponse->json('pedido_id');
        
        // Authenticate as Admin
        $this->actingAs($this->admin);

        // First cancellation
        $this->postJson("/api/pedidos/{$pedidoId}/estado", ['estado' => 'Cancelado']);
        
        // Stock is now restored to 10
        $this->assertEquals(10, $this->producto->fresh()->stock);

        // Try canceling again
        $response = $this->postJson("/api/pedidos/{$pedidoId}/estado", ['estado' => 'Cancelado']);
        
        // Should return 200 with the idempotent guard message
        $response->assertStatus(200)
                 ->assertJson(['success' => true, 'message' => 'El pedido ya se encuentra en el estado solicitado.']);

        // Stock should STILL be 10, not 12
        $this->assertEquals(10, $this->producto->fresh()->stock);
    }
}
