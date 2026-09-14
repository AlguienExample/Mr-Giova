<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Mesa;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\DetallePedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function stats(Request $request)
    {
        // ── Fecha dinámica desde el dispositivo del administrador ─────────────
        // Si el cliente envía su fecha local, la usamos; si no, fallback al servidor.
        // El parseo va protegido: un valor corrupto no debe tumbar el endpoint (el
        // dashboard lo consulta cada 7 segundos).
        $fechaReferencia = Carbon::now();
        if ($request->filled('current_date')) {
            try {
                $fechaReferencia = Carbon::parse($request->current_date);
            } catch (\Throwable $e) {
                $fechaReferencia = Carbon::now();
            }
        }

        $hoy  = $fechaReferencia->copy()->startOfDay();
        $ayer = $hoy->copy()->subDay();

        // 1. KPIs
        $ventasHoy = Pedido::whereDate('created_at', $hoy)
            ->where('estado', '!=', 'Cancelado')
            ->sum('total');

        $ventasAyer = Pedido::whereDate('created_at', $ayer)
            ->where('estado', '!=', 'Cancelado')
            ->sum('total');

        $variacionVentas = 0;
        if ($ventasAyer > 0) {
            $variacionVentas = (($ventasHoy - $ventasAyer) / $ventasAyer) * 100;
        } elseif ($ventasHoy > 0) {
            $variacionVentas = 100.0;
        }

        $pedidosHoy = Pedido::whereDate('created_at', $hoy)
            ->where('estado', '!=', 'Cancelado')
            ->count();

        $mesasActivas = Mesa::where('estado', 'Ocupada')->count();
        $totalMesas = Mesa::count();

        $ticketPromedio = $pedidosHoy > 0 ? ($ventasHoy / $pedidosHoy) : 0;

        // 2. ── Ventas Semanales: Lunes a Domingo de la SEMANA ACTUAL ────────
        // startOfWeek en Carbon usa lunes por defecto (ISO 8601)
        $inicioSemana = $hoy->copy()->startOfWeek(Carbon::MONDAY);
        $finSemana    = $hoy->copy()->endOfWeek(Carbon::SUNDAY);

        // Una sola query agrupada en vez de 14 queries individuales
        $ventasAgrupadas = Pedido::select(
                DB::raw('DATE(created_at) as fecha'),
                DB::raw('SUM(total) as ventas'),
                DB::raw('COUNT(*) as pedidos')
            )
            ->whereBetween('created_at', [$inicioSemana, $finSemana->copy()->endOfDay()])
            ->where('estado', '!=', 'Cancelado')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('ventas', 'fecha')
            ->toArray();

        $pedidosAgrupados = Pedido::select(
                DB::raw('DATE(created_at) as fecha'),
                DB::raw('COUNT(*) as pedidos')
            )
            ->whereBetween('created_at', [$inicioSemana, $finSemana->copy()->endOfDay()])
            ->where('estado', '!=', 'Cancelado')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('pedidos', 'fecha')
            ->toArray();

        $ventasPorDia = [];
        $diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

        for ($i = 0; $i < 7; $i++) {
            $fecha = $inicioSemana->copy()->addDays($i);
            $nombreDia = $diasSemana[$i];
            $fechaStr = $fecha->format('Y-m-d');
            $esFuturo = $fecha->copy()->startOfDay()->isAfter($hoy);

            $ventasPorDia[] = [
                'dia'          => $nombreDia,
                'fecha'        => $fechaStr,
                'ventas'       => $esFuturo ? 0 : (float) ($ventasAgrupadas[$fechaStr] ?? 0),
                'pedidos'      => $esFuturo ? 0 : (int) ($pedidosAgrupados[$fechaStr] ?? 0),
                'es_hoy'       => $fecha->isSameDay($hoy),
                'es_futuro'    => $esFuturo,
            ];
        }

        $totalSemanal  = array_sum(array_column($ventasPorDia, 'ventas'));
        $totalPedidos  = array_sum(array_column($ventasPorDia, 'pedidos'));
        $ticketSemanal = $totalPedidos > 0 ? round($totalSemanal / $totalPedidos) : 0;

        // 3. Productos Premium (join en vez de N+1)
        $catPremium = Categoria::where('nombre', 'Premium & Delicatessen')->first();
        $premiumQuery = Producto::query()
            ->leftJoin('detalle_pedido', 'productos.id', '=', 'detalle_pedido.producto_id')
            ->select('productos.nombre', DB::raw('COALESCE(SUM(detalle_pedido.cantidad), 0) as cantidad'))
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('cantidad');

        if ($catPremium) {
            $premiumQuery->where('productos.categoria_id', $catPremium->id);
        }

        $premiumProducts = $premiumQuery->take(5)
            ->get()
            ->map(fn($p) => ['nombre' => $p->nombre, 'cantidad' => (int) $p->cantidad])
            ->toArray();

        // 4. Inventario crítico
        $alertasStock = Producto::where('stock', '<=', 10)->count();

        return response()->json([
            'kpis' => [
                'ventas_hoy'       => (float) $ventasHoy,
                'ventas_ayer'      => (float) $ventasAyer,
                'variacion_ventas' => (float) $variacionVentas,
                'pedidos_hoy'      => $pedidosHoy,
                'mesas_activas'    => $mesasActivas,
                'total_mesas'      => $totalMesas,
                'ticket_promedio'  => (float) $ticketPromedio,
                'alertas_stock'    => $alertasStock,
            ],
            'ventas_por_dia'    => $ventasPorDia,
            'productos_premium' => $premiumProducts,
            // Metadatos para exportadores PDF/Excel
            'meta_semana' => [
                'inicio'          => $inicioSemana->format('Y-m-d'),
                'fin'             => $finSemana->format('Y-m-d'),
                'total_semanal'   => (float) $totalSemanal,
                'ticket_semanal'  => (float) $ticketSemanal,
                'total_pedidos'   => $totalPedidos,
                'generado_en'     => now()->format('Y-m-d H:i:s'),
                'fecha_referencia'=> $hoy->format('Y-m-d'),
            ],
        ]);
    }

    public function getMesasEstado()
    {
        // Pre-cargar IDs de mesas con pedidos activos en UNA sola query (evita N+1)
        // Incluye Listo: igual que cocina y caja, un pedido listo sigue ocupando la mesa.
        $mesasConPedidoActivo = Pedido::whereIn('estado', ['Nuevo', 'En_Preparacion', 'Listo'])
            ->distinct()
            ->pluck('mesa_id')
            ->toArray();

        $mesas = Mesa::with('empleado.usuario')->orderBy('numero_mesa', 'asc')->get()->map(function($m) use ($mesasConPedidoActivo) {
            return [
                'id' => $m->id,
                'numero_mesa' => $m->numero_mesa,
                'capacidad' => $m->capacidad,
                'estado' => $m->estado,
                'codigo_qr' => $m->codigo_qr,
                'ubicacion' => $m->ubicacion,
                'pos_x' => $m->pos_x !== null ? (float) $m->pos_x : null,
                'pos_y' => $m->pos_y !== null ? (float) $m->pos_y : null,
                'zona' => $m->zona ?? 'Principal',
                'timer_inicio' => $m->timer_inicio,
                'pedido_en_preparacion' => in_array($m->id, $mesasConPedidoActivo),
                'empleado_id' => $m->empleado_id,
                'empleado_nombre' => $m->empleado && $m->empleado->usuario 
                    ? $m->empleado->usuario->nombres . ' ' . $m->empleado->usuario->apellidos 
                    : null
            ];
        });
        return response()->json($mesas);
    }

    public function assignEmpleadoMesa(Request $request, $id)
    {
        $request->validate([
            'empleado_id' => 'nullable|exists:empleados,id'
        ]);

        $mesa = Mesa::findOrFail($id);
        $mesa->empleado_id = $request->empleado_id;
        $mesa->save();

        return response()->json([
            'success' => true,
            'message' => 'Personal asignado correctamente a la mesa.'
        ]);
    }

    public function updateMesaCoordenadas(Request $request, $id)
    {
        $request->validate([
            'x' => 'required|numeric|min:0|max:100',
            'y' => 'required|numeric|min:0|max:100'
        ]);
        
        $mesa = Mesa::findOrFail($id);
        // Las coordenadas van en columnas dedicadas: `ubicacion` (texto humano)
        // queda intacta.
        $mesa->pos_x = round($request->x, 2);
        $mesa->pos_y = round($request->y, 2);
        $mesa->save();
        
        return response()->json(['success' => true]);
    }

    public function storeComanda(Request $request)
    {
        $validated = $request->validate([
            'mesa_numero' => 'required|integer|min:1|max:999',
            'accion'      => 'required|in:abrir,cerrar',
        ]);

        $mesa = Mesa::where('numero_mesa', $validated['mesa_numero'])->first();
        if (!$mesa) {
            return response()->json(['success' => false, 'error' => 'Mesa no encontrada.'], 404);
        }

        if ($validated['accion'] === 'abrir') {
            if (!in_array($mesa->estado, ['Disponible', 'Mantenimiento'])) {
                return response()->json([
                    'success' => false,
                    'error'   => "La mesa {$mesa->numero_mesa} ya está {$mesa->estado}.",
                ], 422);
            }
            $mesa->update(['estado' => 'Ocupada', 'timer_inicio' => now()]);
            $mensaje = "Mesa {$mesa->numero_mesa} abierta correctamente.";
        } else {
            // Cerrar: solo mesas ocupadas y sin pedidos pendientes de pago.
            if ($mesa->estado !== 'Ocupada') {
                return response()->json([
                    'success' => false,
                    'error'   => "La mesa {$mesa->numero_mesa} no está ocupada.",
                ], 422);
            }
            if (Pedido::mesaTienePendientes($mesa->id)) {
                return response()->json([
                    'success' => false,
                    'error'   => "La mesa {$mesa->numero_mesa} tiene pedidos pendientes. Cóbralos en caja primero.",
                ], 422);
            }
            $mesa->update(['estado' => 'Disponible', 'timer_inicio' => null]);
            $mensaje = "Mesa {$mesa->numero_mesa} cerrada correctamente.";
        }

        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'mesa'    => ['id' => $mesa->id, 'numero_mesa' => $mesa->numero_mesa, 'estado' => $mesa->estado],
        ]);
    }

    public function getMesaPedidoActivo($num)
    {
        $mesa = Mesa::where('numero_mesa', $num)->first();
        if (!$mesa) return response()->json(['error' => 'Mesa no encontrada'], 404);

        $pedido = Pedido::where('mesa_id', $mesa->id)
            ->whereIn('estado', ['Nuevo', 'En_Preparacion', 'Listo'])
            ->whereDoesntHave('factura')
            ->orderBy('created_at', 'desc')
            ->first();
            
        if ($pedido) {
            return response()->json(['success' => true, 'pedido_id' => $pedido->id]);
        }
        
        return response()->json(['success' => false, 'error' => 'No active order']);
    }
}
