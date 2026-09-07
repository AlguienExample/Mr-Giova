<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Empleado;
use App\Models\MateriaPrima;
use App\Models\PedidoProveedor;
use App\Models\DetallePedidoProveedor;
use Carbon\Carbon;

class PedidoProveedorSeeder extends Seeder
{
    /**
     * Crea 4 pedidos de reposición de ejemplo en distintos estados
     * para poblar el historial en desarrollo/demo.
     *
     * Requiere que existan empleados y materias primas en la BD
     * (correr DatabaseSeeder o MateriaPrimaSeeder antes).
     */
    public function run(): void
    {
        // Buscar el empleado administrador (cargo Gerente General)
        $empleado = Empleado::first();

        if (!$empleado) {
            $this->command->warn('[PedidoProveedorSeeder] No hay empleados en la BD. Saltando seeder.');
            return;
        }

        // Obtener algunos insumos para el detalle
        $insumos = MateriaPrima::take(4)->get();

        if ($insumos->isEmpty()) {
            $this->command->warn('[PedidoProveedorSeeder] No hay materias primas en la BD. Saltando seeder.');
            return;
        }

        // ── Pedido 1: Pendiente ───────────────────────────────────────────────
        $p1 = PedidoProveedor::create([
            'empleado_id'    => $empleado->id,
            'estado'         => 'Pendiente',
            'notas'          => 'Reposición automática de ítems críticos — ' . now()->subHours(2)->toDateTimeString(),
            'fecha_recibido' => null,
            'created_at'     => now()->subHours(2),
            'updated_at'     => now()->subHours(2),
        ]);
        $this->crearDetalle($p1, $insumos->take(2));

        // ── Pedido 2: Enviado ─────────────────────────────────────────────────
        $p2 = PedidoProveedor::create([
            'empleado_id'    => $empleado->id,
            'estado'         => 'Enviado',
            'notas'          => 'Reposición automática de ítems críticos — ' . now()->subDays(1)->toDateTimeString(),
            'fecha_recibido' => null,
            'created_at'     => now()->subDays(1),
            'updated_at'     => now()->subDays(1),
        ]);
        $this->crearDetalle($p2, $insumos->take(3));

        // ── Pedido 3: Recibido ────────────────────────────────────────────────
        $fechaRecibido = now()->subDays(2)->addHours(6);
        $p3 = PedidoProveedor::create([
            'empleado_id'    => $empleado->id,
            'estado'         => 'Recibido',
            'notas'          => 'Reposición automática de ítems críticos — ' . now()->subDays(3)->toDateTimeString(),
            'fecha_recibido' => $fechaRecibido,
            'created_at'     => now()->subDays(3),
            'updated_at'     => $fechaRecibido,
        ]);
        $this->crearDetalle($p3, $insumos->take(4));

        // ── Pedido 4: Cancelado ───────────────────────────────────────────────
        $p4 = PedidoProveedor::create([
            'empleado_id'    => $empleado->id,
            'estado'         => 'Cancelado',
            'notas'          => 'Reposición automática de ítems críticos — ' . now()->subDays(5)->toDateTimeString(),
            'fecha_recibido' => null,
            'created_at'     => now()->subDays(5),
            'updated_at'     => now()->subDays(4),
        ]);
        $this->crearDetalle($p4, $insumos->take(2));

        $this->command->info('[PedidoProveedorSeeder] ✅ 4 pedidos de reposición creados (Pendiente, Enviado, Recibido, Cancelado).');
    }

    /**
     * Crea líneas de detalle para un pedido dado una colección de insumos.
     */
    private function crearDetalle(PedidoProveedor $pedido, \Illuminate\Support\Collection $insumos): void
    {
        foreach ($insumos as $insumo) {
            $cantidadSugerida = max(1, ($insumo->stock_minimo * 2) - $insumo->cantidad_actual);

            DetallePedidoProveedor::create([
                'pedido_proveedor_id'    => $pedido->id,
                'materia_prima_id'       => $insumo->id,
                'cantidad_pedida'        => $cantidadSugerida,
                'costo_unitario_momento' => $insumo->costo_unitario,
            ]);
        }
    }
}
