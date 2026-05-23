<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Producto;
use App\Models\Mesa;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    /**
     * Display a listing of the resource (history for admin).
     */
    public function index(Request $request)
    {
        $query = Pedido::with(['cliente.usuario', 'mesa', 'detalles.producto'])
            ->orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('mesa', function($mq) use ($search) {
                      $mq->where('numero_mesa', 'like', "%{$search}%");
                  })
                  ->orWhereHas('cliente.usuario', function($uq) use ($search) {
                      $uq->where('nombres', 'like', "%{$search}%")
                        ->orWhere('apellidos', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('estado') && $request->input('estado') !== 'Todos') {
            $query->where('estado', $request->input('estado'));
        }

        return response()->json($query->paginate(15));
    }

    /**
     * Store a newly created resource in storage (order from client menu).
     */
    public function store(Request $request)
    {
        $request->validate([
            'mesa_id' => 'required',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:productos,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.notas_especiales' => 'nullable|string|max:255',
            'notas' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Buscar la mesa y cambiar estado a Ocupada
            $mesa = Mesa::find($request->mesa_id);
            if (!$mesa) {
                // Si mandaron el número de mesa en lugar del ID, buscamos por número
                $mesa = Mesa::where('numero_mesa', $request->mesa_id)->first();
            }

            if (!$mesa) {
                return response()->json(['error' => 'Mesa no encontrada'], 404);
            }

            $mesa->update(['estado' => 'Ocupada']);

            // Encontrar el cliente asociado a esta mesa o usar un cliente genérico de mesa
            $emailMesa = "cliente.mesa{$mesa->numero_mesa}@mrgiova.com";
            $usuarioMesa = Usuario::where('email', $emailMesa)->first();
            if ($usuarioMesa && $usuarioMesa->cliente) {
                $clienteId = $usuarioMesa->cliente->id;
            } else {
                // Fallback al primer cliente si no existe el de mesa
                $firstClient = Cliente::first();
                $clienteId = $firstClient ? $firstClient->id : 1;
            }

            // Crear el pedido
            $pedido = Pedido::create([
                'cliente_id' => $clienteId,
                'empleado_id' => null, // Se asigna cuando el mesero o cocinero lo atiende
                'mesa_id' => $mesa->id,
                'estado' => 'Nuevo',
                'tipo_pedido' => 'Presencial',
                'total' => 0,
                'notas' => $request->notas,
                'prioridad' => 'Normal'
            ]);

            $total = 0;

            // Crear detalles de pedido
            foreach ($request->items as $item) {
                $producto = Producto::find($item['producto_id']);
                $subtotal = $producto->precio * $item['cantidad'];
                $total += $subtotal;

                DetallePedido::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $producto->precio,
                    'subtotal' => $subtotal,
                    'notas_especiales' => $item['notas_especiales'] ?? null,
                    'estado_item' => 'Pendiente'
                ]);
            }

            // Actualizar total del pedido
            $pedido->update(['total' => $total]);

            DB::commit();

            return response()->json([
                'success' => true,
                'pedido_id' => $pedido->id,
                'total' => $pedido->total,
                'tiempo_estimado' => '15 - 20 min'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al crear el pedido: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $pedido = Pedido::with(['cliente.usuario', 'mesa', 'detalles.producto'])->find($id);
        if (!$pedido) {
            return response()->json(['error' => 'Pedido no encontrado'], 404);
        }
        return response()->json($pedido);
    }

    /**
     * Update the status of a order (moves Kanban columns).
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|in:Nuevo,En_Preparacion,Listo,Entregado,Cancelado'
        ]);

        $pedido = Pedido::find($id);
        if (!$pedido) {
            return response()->json(['error' => 'Pedido no encontrado'], 404);
        }

        $nuevoEstado = $request->input('estado');
        $dataUpdate = ['estado' => $nuevoEstado];

        if ($nuevoEstado === 'En_Preparacion') {
            $dataUpdate['hora_inicio_preparacion'] = Carbon::now();
            // Asignar el empleado de cocina (primer cocinero de prueba) si aplica
            $cocina = Usuario::where('email', 'cocina@mrgiova.com')->first();
            if ($cocina && $cocina->empleado) {
                $dataUpdate['empleado_id'] = $cocina->empleado->id;
            }
        } elseif ($nuevoEstado === 'Listo') {
            $dataUpdate['hora_listo'] = Carbon::now();
        } elseif ($nuevoEstado === 'Entregado') {
            $dataUpdate['hora_entregado'] = Carbon::now();
            // Liberar la mesa
            if ($pedido->mesa_id) {
                Mesa::where('id', $pedido->mesa_id)->update(['estado' => 'Disponible']);
            }
        } elseif ($nuevoEstado === 'Cancelado') {
            // Liberar la mesa si se cancela
            if ($pedido->mesa_id) {
                Mesa::where('id', $pedido->mesa_id)->update(['estado' => 'Disponible']);
            }
        }

        $pedido->update($dataUpdate);

        // Actualizar también los estados de los items
        $estadoItem = 'Pendiente';
        if ($nuevoEstado === 'En_Preparacion') $estadoItem = 'En_Preparacion';
        if ($nuevoEstado === 'Listo' || $nuevoEstado === 'Entregado') $estadoItem = 'Listo';

        DetallePedido::where('pedido_id', $pedido->id)->update(['estado_item' => $estadoItem]);

        return response()->json([
            'success' => true,
            'pedido_id' => $pedido->id,
            'estado' => $pedido->estado
        ]);
    }

    /**
     * Fetch active orders for Kitchen Kanban board.
     */
    public function activeOrders()
    {
        $pedidos = Pedido::with(['mesa', 'detalles.producto'])
            ->whereIn('estado', ['Nuevo', 'En_Preparacion', 'Listo'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($pedidos);
    }

    /**
     * Fetch statistics for Admin Dashboard.
     */
    public function dashboardStats()
    {
        $hoy = Carbon::today();

        // 1. KPIs
        $ventasHoy = Pedido::whereDate('created_at', $hoy)
            ->where('estado', '!=', 'Cancelado')
            ->sum('total');

        $pedidosHoy = Pedido::whereDate('created_at', $hoy)->count();

        $mesasActivas = Mesa::where('estado', 'Ocupada')->count();

        $ticketPromedio = $pedidosHoy > 0 ? ($ventasHoy / $pedidosHoy) : 0;

        // 2. Ventas por día (Últimos 7 días)
        $ventasPorDia = [];
        $diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = Carbon::today()->subDays($i);
            $diaSemanaIndex = $fecha->dayOfWeek;
            $nombreDia = $diasSemana[$diaSemanaIndex];
            
            $ventas = Pedido::whereDate('created_at', $fecha)
                ->where('estado', '!=', 'Cancelado')
                ->sum('total');

            $ventasPorDia[] = [
                'dia' => $nombreDia,
                'fecha' => $fecha->format('Y-m-d'),
                'ventas' => (float)$ventas
            ];
        }

        // 3. Productos más vendidos
        $topProductos = DetallePedido::select('producto_id', DB::raw('SUM(cantidad) as total_vendido'))
            ->groupBy('producto_id')
            ->orderBy('total_vendido', 'desc')
            ->limit(5)
            ->get();

        $productosMasVendidos = [];
        foreach ($topProductos as $tp) {
            $prod = Producto::find($tp->producto_id);
            if ($prod) {
                $productosMasVendidos[] = [
                    'nombre' => $prod->nombre,
                    'cantidad' => (int)$tp->total_vendido
                ];
            }
        }

        // 4. Inventario bajo (Simulado de acuerdo al mock del cliente)
        $inventarioBajo = [
            ['nombre' => 'Papas Francesas', 'cantidad' => 3, 'unidad' => 'unidades'],
            ['nombre' => 'Coca-Cola', 'cantidad' => 5, 'unidad' => 'unidades'],
            ['nombre' => 'Hamburguesa Doble Mr.Giova', 'cantidad' => 2, 'unidad' => 'unidades']
        ];

        // 5. Estado de mesas
        $estadoMesas = [
            'Disponible' => Mesa::where('estado', 'Disponible')->count(),
            'Ocupada' => Mesa::where('estado', 'Ocupada')->count(),
            'Reservada' => Mesa::where('estado', 'Reservada')->count(),
            'Mantenimiento' => Mesa::where('estado', 'Mantenimiento')->count(),
        ];

        return response()->json([
            'kpis' => [
                'ventas_hoy' => (float)$ventasHoy,
                'pedidos_hoy' => $pedidosHoy,
                'mesas_activas' => $mesasActivas,
                'ticket_promedio' => (float)$ticketPromedio
            ],
            'ventas_por_dia' => $ventasPorDia,
            'productos_mas_vendidos' => $productosMasVendidos,
            'inventario_bajo' => $inventarioBajo,
            'estado_mesas' => $estadoMesas
        ]);
    }
}
