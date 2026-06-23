<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Role;
use App\Models\Usuario;
use App\Models\Cliente;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\DetallePedido;
use App\Models\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductoControllerTest extends TestCase
{
    use RefreshDatabase;

    private $adminUser;
    private $nonAdminUser;
    private $cliente;
    private $categoria;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Roles
        $rolAdmin = Role::create([
            'name' => 'Administrador',
            'description' => 'Administrador de prueba'
        ]);

        $rolCliente = Role::create([
            'name' => 'Cliente',
            'description' => 'Cliente de prueba'
        ]);

        // Create Admin User
        $this->adminUser = Usuario::create([
            'nombres' => 'Admin',
            'apellidos' => 'Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'rol_id' => $rolAdmin->id,
            'activo' => true
        ]);

        // Create Regular User
        $this->nonAdminUser = Usuario::create([
            'nombres' => 'Cliente',
            'apellidos' => 'Test',
            'email' => 'cliente@test.com',
            'password' => bcrypt('password'),
            'rol_id' => $rolCliente->id,
            'activo' => true
        ]);

        // Create Cliente profile
        $this->cliente = Cliente::create([
            'usuario_id' => $this->nonAdminUser->id,
            'puntos_fidelidad' => 0
        ]);

        // Create test category
        $this->categoria = Categoria::create([
            'nombre' => 'Hamburguesas',
            'activo' => true
        ]);
    }

    /** @test */
    public function public_can_list_active_products_by_category()
    {
        $productoActivo = Producto::create([
            'categoria_id' => $this->categoria->id,
            'nombre' => 'Hamburguesa Especial',
            'precio' => 15000,
            'stock' => 10,
            'disponible' => true
        ]);

        $productoInactivo = Producto::create([
            'categoria_id' => $this->categoria->id,
            'nombre' => 'Hamburguesa Antigua',
            'precio' => 12000,
            'stock' => 0,
            'disponible' => false
        ]);

        $response = $this->getJson('/api/productos');

        $response->assertStatus(200);
        $response->assertJsonFragment(['nombre' => 'Hamburguesa Especial']);
        $response->assertJsonMissing(['nombre' => 'Hamburguesa Antigua']);
    }

    /** @test */
    public function guests_cannot_access_admin_endpoints()
    {
        $this->getJson('/api/admin/productos')->assertStatus(401);
        $this->postJson('/api/admin/productos', [])->assertStatus(401);
    }

    /** @test */
    public function non_admins_cannot_access_admin_endpoints()
    {
        $this->actingAs($this->nonAdminUser);

        $this->getJson('/api/admin/productos')->assertStatus(403);
        $this->postJson('/api/admin/productos', [])->assertStatus(403);
    }

    /** @test */
    public function admin_can_list_all_products_including_inactive_ones()
    {
        $this->actingAs($this->adminUser);

        $productoInactivo = Producto::create([
            'categoria_id' => $this->categoria->id,
            'nombre' => 'Hamburguesa Antigua',
            'precio' => 12000,
            'stock' => 0,
            'disponible' => false
        ]);

        $response = $this->getJson('/api/admin/productos');

        $response->assertStatus(200);
        $response->assertJsonFragment(['nombre' => 'Hamburguesa Antigua']);
    }

    /** @test */
    public function admin_can_create_a_product()
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'categoria_id' => $this->categoria->id,
            'nombre' => 'Perro Caliente',
            'precio' => 8000,
            'stock' => 30,
            'disponible' => true,
            'descripcion' => 'Delicioso perro caliente con queso',
            'tiempo_preparacion' => 10,
            'ingredientes' => 'Salchicha, Pan, Queso'
        ];

        $response = $this->postJson('/api/admin/productos', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('productos', ['nombre' => 'Perro Caliente', 'stock' => 30]);
    }

    /** @test */
    public function admin_can_update_a_product()
    {
        $this->actingAs($this->adminUser);

        $producto = Producto::create([
            'categoria_id' => $this->categoria->id,
            'nombre' => 'Original Name',
            'precio' => 10000,
            'stock' => 20,
            'disponible' => true
        ]);

        $payload = [
            'categoria_id' => $this->categoria->id,
            'nombre' => 'Updated Name',
            'precio' => 12000,
            'stock' => 15,
            'disponible' => false,
            'descripcion' => 'Updated Description',
            'tiempo_preparacion' => 12,
            'ingredientes' => 'Updated Ingredients'
        ];

        $response = $this->putJson("/api/admin/productos/{$producto->id}", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'nombre' => 'Updated Name',
            'stock' => 15,
            'disponible' => false
        ]);
    }

    /** @test */
    public function admin_can_delete_product_with_no_orders()
    {
        $this->actingAs($this->adminUser);

        $producto = Producto::create([
            'categoria_id' => $this->categoria->id,
            'nombre' => 'Short-lived Product',
            'precio' => 5000,
            'stock' => 5,
            'disponible' => true
        ]);

        $response = $this->deleteJson("/api/admin/productos/{$producto->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('productos', ['id' => $producto->id]);
    }

    /** @test */
    public function admin_cannot_delete_product_with_orders_and_instead_marks_unavailable()
    {
        $this->actingAs($this->adminUser);

        $producto = Producto::create([
            'categoria_id' => $this->categoria->id,
            'nombre' => 'Ordered Product',
            'precio' => 15000,
            'stock' => 5,
            'disponible' => true
        ]);

        // Create a dummy order detail referencing the product
        $pedido = Pedido::create([
            'cliente_id' => $this->cliente->id,
            'estado' => 'Nuevo',
            'tipo_pedido' => 'Presencial',
            'total' => 15000
        ]);

        DetallePedido::create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => 15000,
            'subtotal' => 15000,
            'estado_item' => 'Pendiente'
        ]);

        $response = $this->deleteJson("/api/admin/productos/{$producto->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'success' => true,
            'message' => 'El producto tiene pedidos asociados. Se marcó como no disponible y su stock fue puesto en 0.'
        ]);

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'disponible' => false,
            'stock' => 0
        ]);
    }
}
