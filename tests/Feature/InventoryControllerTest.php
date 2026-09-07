<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Empleado;
use App\Models\Role;
use App\Models\MateriaPrima;

class InventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        // Crear rol Administrador
        Role::create(['id' => 1, 'name' => 'Administrador', 'description' => 'Admin']);
    }

    // ─── Helper para crear admin con empleado ────────────────────────────────

    private function crearAdmin(string $email = 'admin_inv@mrgiova.com', string $password = 'secret123'): array
    {
        $admin = Usuario::create([
            'nombres'   => 'Admin',
            'apellidos' => 'Test',
            'email'     => $email,
            'password'  => Hash::make($password),
            'rol_id'    => 1,
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
            'nombre'         => 'Insumo Test',
            'categoria'      => 'Carnes',
            'cantidad_actual'=> 2.0,
            'unidad_medida'  => 'kg',
            'stock_minimo'   => 10.0,   // 2 <= 10 → CRÍTICO
            'costo_unitario' => 5000.0,
        ], $attrs));
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    /**
     * POST /api/admin/insumos/pedido con PIN válido y al menos un insumo crítico
     * debe crear un PedidoProveedor en estado 'Pendiente' (no 'Aprobado') y
     * generar una fila en detalle_pedido_proveedor por cada insumo crítico.
     */
    public function test_admin_can_store_reposicion_with_valid_pin()
    {
        [$admin, $empleado] = $this->crearAdmin();

        // Crear un insumo crítico para que storeReposicion no retorne 422
        $insumo = $this->crearInsumoCritico();

        $response = $this->actingAs($admin)->postJson('/api/admin/insumos/pedido', [
            'pin' => 'secret123',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        // El estado ahora es 'Pendiente', no 'Aprobado'
        $this->assertDatabaseHas('pedidos_proveedor', [
            'empleado_id' => $empleado->id,
            'estado'      => 'Pendiente',
        ]);

        // Debe existir al menos una fila de detalle
        $this->assertDatabaseHas('detalle_pedido_proveedor', [
            'materia_prima_id' => $insumo->id,
        ]);
    }

    public function test_admin_cannot_store_reposicion_with_invalid_pin()
    {
        [$admin] = $this->crearAdmin('admin_inv2@mrgiova.com');

        $response = $this->actingAs($admin)->postJson('/api/admin/insumos/pedido', [
            'pin' => 'wrongpin',
        ]);

        $response->assertStatus(403)
                 ->assertJson(['success' => false, 'error' => 'PIN de autorización inválido.']);
    }
}
