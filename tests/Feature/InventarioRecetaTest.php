<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\Producto;
use App\Models\MateriaPrima;
use App\Models\Categoria;
use App\Models\Mesa;
use App\Models\Role;
use App\Models\Usuario;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\DetallePedido;

class InventarioRecetaTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function crearMesaLibre(): Mesa
    {
        return Mesa::create([
            'numero_mesa' => rand(100, 999),
            'capacidad'   => 4,
            'estado'      => 'Disponible',
            'codigo_qr'   => 'qr_test_' . uniqid(),
        ]);
    }

    private function crearProducto(array $extra = []): Producto
    {
        $cat = Categoria::first() ?? Categoria::create([
            'nombre'      => 'Test',
            'descripcion' => 'Cat test',
            'activo'      => true,
        ]);

        return Producto::create(array_merge([
            'categoria_id'       => $cat->id,
            'nombre'             => 'Producto Test ' . uniqid(),
            'descripcion'        => 'Desc',
            'precio'             => 15000,
            'disponible'         => true,
            'tiempo_preparacion' => 10,
            'stock'              => 50,
        ], $extra));
    }

    private function crearMateriaPrima(array $extra = []): MateriaPrima
    {
        return MateriaPrima::create(array_merge([
            'nombre'          => 'MP Test ' . uniqid(),
            'categoria'       => 'Carnes',
            'cantidad_actual' => 10.0,
            'unidad_medida'   => 'kg',
            'stock_minimo'    => 2.0,
            'costo_unitario'  => 50000,
        ], $extra));
    }

    /**
     * Registra el rol Cliente en BD si no existe y retorna el cliente_id
     * del cliente genérico de la mesa (el que PedidoController@store auto-crea).
     */
    private function prepararClienteMesa(Mesa $mesa): void
    {
        $rolCliente = Role::firstOrCreate(
            ['name' => 'Cliente'],
            ['description' => 'Comensal']
        );
        // Crear el usuario+cliente genérico de mesa para que store() lo encuentre
        $email = "cliente.mesa{$mesa->numero_mesa}@saborapueblo.com";
        if (!Usuario::where('email', $email)->exists()) {
            $u = Usuario::create([
                'nombres'  => 'Cliente',
                'apellidos'=> "Mesa {$mesa->numero_mesa}",
                'email'    => $email,
                'password' => Hash::make('test'),
                'rol_id'   => $rolCliente->id,
                'activo'   => true,
            ]);
            Cliente::create(['usuario_id' => $u->id]);
        }
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    /**
     * @test
     * Vender un producto con receta descuenta la cantidad correcta de MP.
     */
    public function test_vender_producto_con_receta_descuenta_materia_prima(): void
    {
        $mesa = $this->crearMesaLibre();
        $this->prepararClienteMesa($mesa);

        $producto = $this->crearProducto(['stock' => 20]);
        $mp       = $this->crearMateriaPrima(['cantidad_actual' => 10.0]);

        // Asociar receta: 1 producto consume 0.25 kg de MP
        $producto->materiasPrimas()->attach($mp->id, ['cantidad_requerida' => 0.250]);

        $cantidadPedida = 2;

        $response = $this->postJson('/api/pedidos', [
            'mesa_id' => $mesa->numero_mesa,
            'items'   => [
                ['producto_id' => $producto->id, 'cantidad' => $cantidadPedida, 'notas_especiales' => null],
            ],
        ]);

        $response->assertStatus(200)->assertJsonFragment(['success' => true]);

        // MP debería haber bajado: 10 - (0.25 * 2) = 9.5
        $this->assertEqualsWithDelta(9.5, (float) $mp->fresh()->cantidad_actual, 0.001);
        // Stock del producto también
        $this->assertEquals(18, $producto->fresh()->stock);
    }

    /**
     * @test
     * Un producto SIN receta debe crear el pedido sin tocar ninguna MateriaPrima.
     */
    public function test_vender_producto_sin_receta_no_falla_y_no_descuenta_nada(): void
    {
        $mesa = $this->crearMesaLibre();
        $this->prepararClienteMesa($mesa);

        $producto = $this->crearProducto(['stock' => 20]);
        $mp       = $this->crearMateriaPrima(['cantidad_actual' => 5.0]);
        // NO se adjunta receta

        $response = $this->postJson('/api/pedidos', [
            'mesa_id' => $mesa->numero_mesa,
            'items'   => [
                ['producto_id' => $producto->id, 'cantidad' => 1, 'notas_especiales' => null],
            ],
        ]);

        $response->assertStatus(200)->assertJsonFragment(['success' => true]);

        // MP no debe haber cambiado
        $this->assertEqualsWithDelta(5.0, (float) $mp->fresh()->cantidad_actual, 0.001);
        // Stock del producto sí baja
        $this->assertEquals(19, $producto->fresh()->stock);
    }

    /**
     * @test
     * Si no hay suficiente MP, el pedido falla completo (rollback de producto también).
     */
    public function test_pedido_falla_si_no_hay_suficiente_materia_prima(): void
    {
        $mesa = $this->crearMesaLibre();
        $this->prepararClienteMesa($mesa);

        $stockProductoOriginal = 20;
        $producto = $this->crearProducto(['stock' => $stockProductoOriginal]);

        // MP con stock MUY bajo
        $mp = $this->crearMateriaPrima(['cantidad_actual' => 0.1]);

        // Receta: necesita 0.5 kg por unidad → pedir 2 = necesitamos 1.0 kg, solo hay 0.1
        $producto->materiasPrimas()->attach($mp->id, ['cantidad_requerida' => 0.500]);

        $response = $this->postJson('/api/pedidos', [
            'mesa_id' => $mesa->numero_mesa,
            'items'   => [
                ['producto_id' => $producto->id, 'cantidad' => 2, 'notas_especiales' => null],
            ],
        ]);

        // Debe fallar con 422
        $response->assertStatus(422);
        $response->assertJsonStructure(['error']);
        $this->assertStringContainsStringIgnoringCase('materia prima', $response->json('error'));

        // ROLLBACK: el stock del producto NO debe haber cambiado
        $this->assertEquals($stockProductoOriginal, $producto->fresh()->stock);

        // Y la MP tampoco debe haber cambiado
        $this->assertEqualsWithDelta(0.1, (float) $mp->fresh()->cantidad_actual, 0.001);
    }

    /**
     * @test
     * Cancelar un pedido devuelve la materia prima al inventario.
     */
    public function test_cancelar_pedido_devuelve_materia_prima_al_inventario(): void
    {
        $mesa = $this->crearMesaLibre();
        $this->prepararClienteMesa($mesa);

        $producto = $this->crearProducto(['stock' => 20]);
        $mp       = $this->crearMateriaPrima(['cantidad_actual' => 10.0]);

        // Receta: 0.3 kg por unidad
        $producto->materiasPrimas()->attach($mp->id, ['cantidad_requerida' => 0.300]);

        // Crear el pedido
        $responseStore = $this->postJson('/api/pedidos', [
            'mesa_id' => $mesa->numero_mesa,
            'items'   => [
                ['producto_id' => $producto->id, 'cantidad' => 2, 'notas_especiales' => null],
            ],
        ]);

        $responseStore->assertStatus(200);
        $pedidoId = $responseStore->json('pedido_id');

        // Verificar que MP bajó: 10 - (0.3 * 2) = 9.4
        $this->assertEqualsWithDelta(9.4, (float) $mp->fresh()->cantidad_actual, 0.001);

        // Autenticar un usuario con rol Cocinero para poder usar updateStatus
        $rolCocina = Role::firstOrCreate(['name' => 'Cocinero'], ['description' => 'Chef']);
        $cocinero  = Usuario::create([
            'nombres'  => 'Chef',
            'apellidos'=> 'Test',
            'email'    => 'chef.test@test.com',
            'password' => Hash::make('pass'),
            'rol_id'   => $rolCocina->id,
            'activo'   => true,
        ]);

        // Cancelar el pedido
        $responseCancel = $this->actingAs($cocinero)
            ->postJson("/api/pedidos/{$pedidoId}/estado", ['estado' => 'Cancelado']);

        $responseCancel->assertStatus(200)->assertJsonFragment(['success' => true]);

        // MP debe haber vuelto a 10.0
        $this->assertEqualsWithDelta(10.0, (float) $mp->fresh()->cantidad_actual, 0.001);
        // Stock del producto también vuelve a 20
        $this->assertEquals(20, $producto->fresh()->stock);
    }
}
