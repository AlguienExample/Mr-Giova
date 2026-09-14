<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Empleado;
use App\Models\Role;
use App\Models\MateriaPrima;
use App\Models\PedidoProveedor;
use App\Models\DetallePedidoProveedor;

class PedidoProveedorTest extends TestCase
{
    use RefreshDatabase;

    private int $rolAdminId;

    public function setUp(): void
    {
        parent::setUp();
        $this->rolAdminId = Role::firstOrCreate(['name' => 'Administrador'], ['description' => 'Admin'])->id;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function crearAdmin(): array
    {
        $admin = Usuario::create([
            'nombres'   => 'Admin',
            'apellidos' => 'Test',
            'email'     => 'admin_pp_test@mrgiova.com',
            'password'  => Hash::make('secret123'),
            'rol_id'    => $this->rolAdminId,
            'activo'    => true,
        ]);

        $empleado = Empleado::create([
            'usuario_id'         => $admin->id,
            'cargo'              => 'Gerente',
            'fecha_contratacion' => now(),
            'sueldo'             => 1000,
            'turno'              => 'Rotativo',
        ]);

        return [$admin, $empleado];
    }

    private function crearInsumoCritico(array $attrs = []): MateriaPrima
    {
        return MateriaPrima::create(array_merge([
            'nombre'         => 'Insumo Crítico Test',
            'categoria'      => 'Carnes',
            'cantidad_actual'=> 2.0,
            'unidad_medida'  => 'kg',
            'stock_minimo'   => 10.0,   // 2 <= 10 → CRÍTICO
            'costo_unitario' => 5000.0,
        ], $attrs));
    }

    private function crearInsumoOptimo(array $attrs = []): MateriaPrima
    {
        return MateriaPrima::create(array_merge([
            'nombre'         => 'Insumo Óptimo Test',
            'categoria'      => 'Verduras',
            'cantidad_actual'=> 50.0,
            'unidad_medida'  => 'kg',
            'stock_minimo'   => 5.0,    // 50 > 5 → ÓPTIMO
            'costo_unitario' => 1000.0,
        ], $attrs));
    }

    /** Crea un PedidoProveedor con detalle para un insumo dado */
    private function crearPedidoConDetalle(
        Empleado $empleado,
        MateriaPrima $insumo,
        string $estado = 'Pendiente',
        float $cantidadPedida = 8.0
    ): PedidoProveedor {
        $pedido = PedidoProveedor::create([
            'empleado_id' => $empleado->id,
            'estado'      => $estado,
            'notas'       => 'Pedido de test',
        ]);

        DetallePedidoProveedor::create([
            'pedido_proveedor_id'    => $pedido->id,
            'materia_prima_id'       => $insumo->id,
            'cantidad_pedida'        => $cantidadPedida,
            'costo_unitario_momento' => $insumo->costo_unitario,
        ]);

        return $pedido;
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    /**
     * 1. storeReposicion crea un DetallePedidoProveedor por cada insumo crítico.
     */
    public function test_reposicion_crea_detalle_por_cada_insumo_critico()
    {
        [$admin, $empleado] = $this->crearAdmin();

        // Crear 3 insumos críticos y 1 óptimo (el óptimo NO debe aparecer en el detalle)
        $c1 = $this->crearInsumoCritico(['nombre' => 'Carne A', 'cantidad_actual' => 1, 'stock_minimo' => 10]);
        $c2 = $this->crearInsumoCritico(['nombre' => 'Carne B', 'cantidad_actual' => 3, 'stock_minimo' => 15]);
        $c3 = $this->crearInsumoCritico(['nombre' => 'Carne C', 'cantidad_actual' => 0, 'stock_minimo' => 5]);
        $this->crearInsumoOptimo(['nombre' => 'Verdura OK']);

        $response = $this->actingAs($admin)->postJson('/api/admin/insumos/pedido', [
            'pin' => 'secret123',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        // Debe haber exactamente 3 líneas de detalle (una por insumo crítico)
        $this->assertDatabaseCount('detalle_pedido_proveedor', 3);

        // Verificar cantidad_pedida = max(0, stock_minimo * 2 - cantidad_actual)
        $this->assertDatabaseHas('detalle_pedido_proveedor', [
            'materia_prima_id' => $c1->id,
            'cantidad_pedida'  => max(0, ($c1->stock_minimo * 2) - $c1->cantidad_actual),  // 19
        ]);
        $this->assertDatabaseHas('detalle_pedido_proveedor', [
            'materia_prima_id' => $c2->id,
            'cantidad_pedida'  => max(0, ($c2->stock_minimo * 2) - $c2->cantidad_actual),  // 27
        ]);
        $this->assertDatabaseHas('detalle_pedido_proveedor', [
            'materia_prima_id' => $c3->id,
            'cantidad_pedida'  => max(0, ($c3->stock_minimo * 2) - $c3->cantidad_actual),  // 10
        ]);

        // El insumo óptimo NO debe tener detalle
        $this->assertDatabaseMissing('detalle_pedido_proveedor', [
            'materia_prima_id' => 4,  // el 4to creado es el óptimo
        ]);
    }

    /**
     * 2. marcarRecibido suma exactamente cantidad_pedida a cantidad_actual de cada insumo.
     */
    public function test_marcar_recibido_suma_stock_correctamente_a_cada_insumo()
    {
        [$admin, $empleado] = $this->crearAdmin();

        $insumo          = $this->crearInsumoCritico(['cantidad_actual' => 2.0]);
        $stockInicial    = 2.0;
        $cantidadPedida  = 8.0;

        $pedido = $this->crearPedidoConDetalle($empleado, $insumo, 'Pendiente', $cantidadPedida);

        $response = $this->actingAs($admin)->postJson("/api/admin/insumos/pedidos/{$pedido->id}/recibir");

        $response->assertStatus(200)->assertJson(['success' => true]);

        // El stock debe ser exactamente stockInicial + cantidadPedida
        $this->assertDatabaseHas('materia_primas', [
            'id'              => $insumo->id,
            'cantidad_actual' => $stockInicial + $cantidadPedida, // 10.0
        ]);

        // El pedido debe estar en estado Recibido y tener fecha_recibido
        $this->assertDatabaseHas('pedidos_proveedor', [
            'id'     => $pedido->id,
            'estado' => 'Recibido',
        ]);
        $this->assertNotNull($pedido->fresh()->fecha_recibido);
    }

    /**
     * 3. Llamar a marcarRecibido dos veces: el segundo intento falla con 422 y
     *    cantidad_actual NO cambia entre el primer y el segundo intento.
     *    Esto valida el comportamiento correcto del lock-first.
     */
    public function test_no_se_puede_marcar_recibido_dos_veces()
    {
        [$admin, $empleado] = $this->crearAdmin();

        $insumo         = $this->crearInsumoCritico(['cantidad_actual' => 2.0]);
        $cantidadPedida = 8.0;
        $pedido         = $this->crearPedidoConDetalle($empleado, $insumo, 'Pendiente', $cantidadPedida);

        // Primera llamada — debe tener éxito
        $this->actingAs($admin)->postJson("/api/admin/insumos/pedidos/{$pedido->id}/recibir")
             ->assertStatus(200)
             ->assertJson(['success' => true]);

        // Capturar cantidad_actual después del primer intento (2 + 8 = 10)
        $stockTrasRecibir = (float) $insumo->fresh()->cantidad_actual;
        $this->assertEquals(10.0, $stockTrasRecibir);

        // Segunda llamada — debe fallar con 422
        $this->actingAs($admin)->postJson("/api/admin/insumos/pedidos/{$pedido->id}/recibir")
             ->assertStatus(422)
             ->assertJson(['success' => false]);

        // El stock NO debe haber cambiado (sin duplicado de incremento)
        $this->assertDatabaseHas('materia_primas', [
            'id'              => $insumo->id,
            'cantidad_actual' => $stockTrasRecibir,  // sigue siendo 10.0, no 18.0
        ]);
    }

    /**
     * 4. cancelarReposicion no modifica el stock de ningún insumo.
     */
    public function test_cancelar_reposicion_no_modifica_stock()
    {
        [$admin, $empleado] = $this->crearAdmin();

        $insumo       = $this->crearInsumoCritico(['cantidad_actual' => 2.0]);
        $stockInicial = 2.0;
        $pedido       = $this->crearPedidoConDetalle($empleado, $insumo, 'Pendiente', 8.0);

        $response = $this->actingAs($admin)->postJson("/api/admin/insumos/pedidos/{$pedido->id}/cancelar");

        $response->assertStatus(200)->assertJson(['success' => true]);

        // El stock NO debe haber cambiado
        $this->assertDatabaseHas('materia_primas', [
            'id'              => $insumo->id,
            'cantidad_actual' => $stockInicial,
        ]);

        // El pedido debe estar en estado Cancelado
        $this->assertDatabaseHas('pedidos_proveedor', [
            'id'     => $pedido->id,
            'estado' => 'Cancelado',
        ]);
    }

    /**
     * 5. No se puede cancelar un pedido que ya está en estado 'Recibido'.
     */
    public function test_no_se_puede_cancelar_un_pedido_ya_recibido()
    {
        [$admin, $empleado] = $this->crearAdmin();

        $insumo = $this->crearInsumoCritico();
        $pedido = $this->crearPedidoConDetalle($empleado, $insumo, 'Recibido');

        // Actualizar fecha_recibido para que sea consistente
        $pedido->update(['fecha_recibido' => now()]);

        $response = $this->actingAs($admin)->postJson("/api/admin/insumos/pedidos/{$pedido->id}/cancelar");

        $response->assertStatus(422)->assertJson(['success' => false]);

        // El estado NO debe haber cambiado a Cancelado
        $this->assertDatabaseHas('pedidos_proveedor', [
            'id'     => $pedido->id,
            'estado' => 'Recibido',
        ]);
    }

    /**
     * 6. storeReposicion devuelve 422 cuando NO hay insumos críticos,
     *    y NO crea ningún PedidoProveedor en ese caso.
     */
    public function test_reposicion_falla_si_no_hay_insumos_criticos()
    {
        [$admin, $empleado] = $this->crearAdmin();

        // Solo insumos en estado ÓPTIMO — ninguno crítico
        $this->crearInsumoOptimo(['nombre' => 'Verdura OK 1']);
        $this->crearInsumoOptimo(['nombre' => 'Verdura OK 2']);

        $response = $this->actingAs($admin)->postJson('/api/admin/insumos/pedido', [
            'pin' => 'secret123',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);

        // NO debe haberse creado ningún PedidoProveedor
        $this->assertDatabaseCount('pedidos_proveedor', 0);

        // Tampoco filas de detalle
        $this->assertDatabaseCount('detalle_pedido_proveedor', 0);
    }

    /**
     * 7. No se puede eliminar un insumo que tiene historial de pedidos de reposición.
     *    El servidor debe responder 409 y el insumo debe seguir existiendo en BD.
     */
    public function test_no_se_puede_eliminar_insumo_con_historial_de_reposiciones()
    {
        [$admin, $empleado] = $this->crearAdmin();

        $insumo = $this->crearInsumoCritico(['nombre' => 'Insumo Con Historial']);
        $this->crearPedidoConDetalle($empleado, $insumo, 'Pendiente');

        $response = $this->actingAs($admin)->deleteJson("/api/admin/insumos/{$insumo->id}");

        $response->assertStatus(409)
                 ->assertJson(['success' => false]);

        // El insumo debe seguir existiendo en BD
        $this->assertDatabaseHas('materia_primas', [
            'id'     => $insumo->id,
            'nombre' => 'Insumo Con Historial',
        ]);
    }
}
