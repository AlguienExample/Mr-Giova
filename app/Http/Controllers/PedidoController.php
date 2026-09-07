<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Producto;
use App\Models\MateriaPrima;
use App\Models\Mesa;
use App\Models\Cliente;
use App\Models\Usuario;
use App\Models\Reserva;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\Categoria;
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

        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                if (is_numeric($search)) {
                    // Búsqueda indexada directa para número o ID
                    $q->where('id', intval($search))
                      ->orWhereHas('mesa', function($mq) use ($search) {
                          $mq->where('numero_mesa', intval($search));
                      });
                } else {
                    $q->where('id', 'like', "%{$search}%")
                      ->orWhereHas('mesa', function($mq) use ($search) {
                          $mq->where('numero_mesa', 'like', "%{$search}%");
                      })
                      ->orWhereHas('cliente.usuario', function($uq) use ($search) {
                          $uq->where('nombres', 'like', "%{$search}%")
                            ->orWhere('apellidos', 'like', "%{$search}%");
                      });
                }
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
    public function store(\App\Http\Requests\StorePedidoRequest $request)
    {

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

            $mesa->update(['estado' => 'Ocupada', 'timer_inicio' => now()]);

            // Encontrar el cliente asociado a esta mesa o auto-crear uno genérico
            $emailMesa = "cliente.mesa{$mesa->numero_mesa}@saborapueblo.com";
            $usuarioMesa = Usuario::where('email', $emailMesa)->first();
            if ($usuarioMesa && $usuarioMesa->cliente) {
                $clienteId = $usuarioMesa->cliente->id;
            } else {
                // Auto-crear usuario y cliente genérico para esta mesa
                $usuarioMesa = Usuario::create([
                    'nombres'   => 'Cliente',
                    'apellidos' => "Mesa {$mesa->numero_mesa}",
                    'email'     => $emailMesa,
                    'password'  => Hash::make(\Illuminate\Support\Str::password(16)),
                    'rol_id'    => \App\Models\Role::where('name', 'Cliente')->value('id') ?? 1,
                    'activo'    => true,
                ]);
                $clienteMesa = Cliente::create([
                    'usuario_id'     => $usuarioMesa->id,
                ]);
                $clienteId = $clienteMesa->id;
                Log::info('[PedidoController@store] Cliente genérico auto-creado para mesa ' . $mesa->numero_mesa);
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

            // Crear detalles de pedido y descontar stock
            foreach ($request->items as $item) {
                // Bloquear la fila del producto para evitar condiciones de carrera
                $producto = Producto::where('id', $item['producto_id'])->lockForUpdate()->first();
                
                if (!$producto || $producto->stock < $item['cantidad']) {
                    DB::rollBack();
                    return response()->json([
                        'error' => "Stock insuficiente para el producto '{$producto->nombre}'. Disponible: {$producto->stock}"
                    ], 422);
                }

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

                // Descontar stock atómicamente
                $producto->decrement('stock', $item['cantidad']);

                // ── Descontar materias primas de la receta (BOM) ─────────────────
                // Si el producto no tiene receta asociada, se omite sin fallar el pedido.
                $receta = $producto->materiasPrimas()->get();

                foreach ($receta as $mp) {
                    $cantidadADescontar = $mp->pivot->cantidad_requerida * $item['cantidad'];

                    $materiaPrima = MateriaPrima::where('id', $mp->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$materiaPrima || $materiaPrima->cantidad_actual < $cantidadADescontar) {
                        DB::rollBack();
                        $disponible = $materiaPrima ? $materiaPrima->cantidad_actual : 0;
                        $nombreMP   = $materiaPrima ? $materiaPrima->nombre : "ID {$mp->id}";
                        Log::warning('[PedidoController@store] Stock de materia prima insuficiente', [
                            'materia_prima' => $nombreMP,
                            'requerido'     => $cantidadADescontar,
                            'disponible'    => $disponible,
                        ]);
                        return response()->json([
                            'error' => "Stock de materia prima insuficiente: '{$nombreMP}'. Disponible: {$disponible} {$materiaPrima->unidad_medida}"
                        ], 422);
                    }

                    $materiaPrima->decrement('cantidad_actual', $cantidadADescontar);
                }
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
     * Wrapped in a DB transaction for atomicity.
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

        // ── Guardia: si ya está en el estado solicitado, retornar sin cambios ──
        if ($pedido->estado === $nuevoEstado) {
            return response()->json([
                'success' => true, 
                'pedido_id' => $pedido->id, 
                'estado' => $pedido->estado, 
                'message' => 'El pedido ya se encuentra en el estado solicitado.'
            ]);
        }

        // ── Guardia: no permitir cancelar un pedido ya cancelado ──
        if ($pedido->estado === 'Cancelado') {
            return response()->json([
                'success' => false,
                'error'   => 'No se puede cambiar el estado de un pedido ya cancelado.',
            ], 422);
        }

        return DB::transaction(function () use ($pedido, $nuevoEstado) {
            $dataUpdate = ['estado' => $nuevoEstado];

            if ($nuevoEstado === 'En_Preparacion') {
                $dataUpdate['hora_inicio_preparacion'] = Carbon::now();
                // Asignar el empleado de cocina si aplica
                $cocina = Usuario::where('email', 'cocina@saborapueblo.com')->first();
                if ($cocina && $cocina->empleado) {
                    $dataUpdate['empleado_id'] = $cocina->empleado->id;
                }
            } elseif ($nuevoEstado === 'Listo') {
                $dataUpdate['hora_listo'] = Carbon::now();
            } elseif ($nuevoEstado === 'Entregado') {
                $dataUpdate['hora_entregado'] = Carbon::now();
                // Liberar la mesa
                if ($pedido->mesa_id) {
                    Mesa::where('id', $pedido->mesa_id)->update(['estado' => 'Disponible', 'timer_inicio' => null]);
                }
            } elseif ($nuevoEstado === 'Cancelado') {
                // Liberar la mesa si se cancela
                if ($pedido->mesa_id) {
                    Mesa::where('id', $pedido->mesa_id)->update(['estado' => 'Disponible', 'timer_inicio' => null]);
                }
                // Restaurar el stock de los productos cancelados con bloqueo
                $detalles = DetallePedido::where('pedido_id', $pedido->id)->get();
                foreach ($detalles as $detalle) {
                    $prod = Producto::where('id', $detalle->producto_id)->lockForUpdate()->first();
                    if ($prod) {
                        $prod->increment('stock', $detalle->cantidad);

                        // ── Revertir materias primas de la receta (BOM) ──────────────
                        $receta = $prod->materiasPrimas()->get();
                        foreach ($receta as $mp) {
                            $cantidadARestaurar = $mp->pivot->cantidad_requerida * $detalle->cantidad;
                            MateriaPrima::where('id', $mp->id)
                                ->lockForUpdate()
                                ->first()
                                ?->increment('cantidad_actual', $cantidadARestaurar);
                        }
                    }
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
                'estado' => $nuevoEstado
            ]);
        });
    }

    public function activeOrders()
    {
        $pedidos = Pedido::with(['mesa', 'detalles.producto'])
            ->whereIn('estado', ['Nuevo', 'En_Preparacion', 'Listo'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($pedidos);
    }
}
