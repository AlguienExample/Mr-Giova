<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Factura;
use App\Models\DetallePedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CajaController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();
        $estadosActivos = ['Nuevo', 'En_Preparacion', 'Listo'];

        // Obtener todas las mesas con información de pedidos de hoy
        $mesas = Mesa::orderBy('numero_mesa', 'asc')->get()->map(function ($mesa) use ($hoy, $estadosActivos) {
            // ¿Tiene algún pedido pendiente de pago en la jornada de hoy?
            // (servido pero no cobrado también cuenta: Entregado sin factura).
            $pedidoHoy = Pedido::where('mesa_id', $mesa->id)
                ->whereDate('created_at', $hoy)
                ->whereIn('estado', array_merge($estadosActivos, ['Entregado']))
                ->whereDoesntHave('factura')
                ->latest()
                ->first();

            // Tiempo de ocupación (minutos)
            $minutosOcupada = null;
            if ($mesa->timer_inicio && $mesa->estado === 'Ocupada') {
                $minutosOcupada = (int) Carbon::parse($mesa->timer_inicio)->diffInMinutes(now());
            }

            return [
                'id'              => $mesa->id,
                'numero_mesa'     => $mesa->numero_mesa,
                'capacidad'       => $mesa->capacidad,
                'estado'          => $mesa->estado,
                'zona'            => $mesa->zona ?? 'Principal',
                'timer_inicio'    => $mesa->timer_inicio,
                'minutos_ocupada' => $minutosOcupada,
                'tiene_pedido_hoy'=> $pedidoHoy !== null,
                'pedido_id_activo'=> $pedidoHoy?->id,
                'total_pedido'    => $pedidoHoy ? (float) $pedidoHoy->total : 0,
                'estado_pedido'   => $pedidoHoy?->estado,
            ];
        });

        // Estadísticas para el header de la vista
        $libres   = $mesas->where('estado', 'Disponible')->count();
        $ocupadas = $mesas->whereIn('estado', ['Ocupada', 'Reservada'])->count();
        $conPedidoHoy = $mesas->where('tiene_pedido_hoy', true)->count();

        // Para llevar pendientes de pago (no usan mesa: se listan aparte).
        $paraLlevar = Pedido::with('detalles')
            ->where('tipo_pedido', 'Para_Llevar')
            ->whereDate('created_at', $hoy)
            ->whereIn('estado', ['Nuevo', 'En_Preparacion', 'Listo', 'Entregado'])
            ->whereDoesntHave('factura')
            ->latest()
            ->get()
            ->map(fn ($p) => [
                'pedido_id' => $p->id,
                'estado'    => $p->estado,
                'total'     => (float) $p->total,
                'items'     => $p->detalles->sum('cantidad'),
            ]);

        return view('caja', compact('mesas', 'libres', 'ocupadas', 'conPedidoHoy', 'paraLlevar'));
    }

    public function getPedidoMesa($mesa_id)
    {
        // Pedido pendiente de pago: servido (Entregado) pero aún sin factura también cuenta.
        // Si no hay ninguno de hoy (p. ej. corte de luz sin cierre), se busca el más
        // reciente sin importar la fecha para no dejar mesas ocupadas huérfanas.
        $base = Pedido::with(['detalles.producto', 'empleado.usuario'])
            ->where('mesa_id', $mesa_id)
            ->whereIn('estado', ['Nuevo', 'En_Preparacion', 'Listo', 'Entregado'])
            ->whereDoesntHave('factura');

        $pedido = (clone $base)->whereDate('created_at', Carbon::today())->latest()->first()
            ?? $base->latest()->first();

        if (!$pedido) {
            return response()->json(['success' => false, 'message' => 'No hay pedido activo']);
        }

        return $this->pedidoToJson($pedido);
    }

    public function getPedido($id)
    {
        $pedido = Pedido::with(['detalles.producto', 'empleado.usuario'])
            ->where('id', $id)
            ->whereIn('estado', ['Nuevo', 'En_Preparacion', 'Listo', 'Entregado'])
            ->whereDoesntHave('factura')
            ->first();

        if (!$pedido) {
            return response()->json(['success' => false, 'message' => 'No hay pedido activo']);
        }

        return $this->pedidoToJson($pedido);
    }

    private function pedidoToJson($pedido)
    {
        $items = $pedido->detalles->map(function ($detalle) {
            return [
                'cantidad'       => $detalle->cantidad,
                'nombre'         => $detalle->producto ? $detalle->producto->nombre : 'Producto ' . $detalle->producto_id,
                'notas'          => $detalle->notas_especiales,
                'precio_unitario'=> $detalle->precio_unitario,
                'subtotal'       => $detalle->subtotal,
            ];
        });

        $camarero = 'Sin asignar';
        if ($pedido->empleado && $pedido->empleado->usuario) {
            $camarero = $pedido->empleado->usuario->nombres . ' ' . $pedido->empleado->usuario->apellidos;
        }

        return response()->json([
            'success'    => true,
            'pedido_id'  => $pedido->id,
            'camarero'   => $camarero,
            'estado'     => $pedido->estado,
            'tipo'       => $pedido->tipo_pedido,
            'items'      => $items,
            'total'      => (float) $pedido->total,
            'created_at' => $pedido->created_at->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Procesa el pago de un pedido y registra la factura.
     */
    public function procesarPago(Request $request)
    {
        $validated = $request->validate([
            'pedido_id'      => 'required|integer|exists:pedidos,id',
            'metodo_pago'    => 'required|in:Efectivo,Tarjeta,Transferencia,Billetera_Digital',
            'propina'        => 'nullable|numeric|min:0',
            'monto_recibido' => 'nullable|numeric|min:0',
        ]);

        $propina = (float) ($validated['propina'] ?? 0);

        try {
            $pago = DB::transaction(function () use ($validated, $propina) {
                $pedido = Pedido::lockForUpdate()->findOrFail($validated['pedido_id']);
                $pedido->loadMissing('factura');

                // Un pedido pagado (con factura) o cancelado es inmutable.
                if ($pedido->estado === 'Cancelado' || $pedido->factura) {
                    throw new \RuntimeException('El pedido ya fue procesado o cancelado.');
                }

                $totalBruto  = (float) $pedido->total;
                $totalFinal = $totalBruto + $propina;

                $montoRecibido = null;
                $cambio        = null;
                if ($validated['metodo_pago'] === 'Efectivo') {
                    $montoRecibido = (float) ($validated['monto_recibido'] ?? 0);
                    $cambio        = $montoRecibido - $totalFinal;
                    if ($cambio < 0) {
                        throw new \RuntimeException('El monto recibido es menor al total a pagar.');
                    }
                }

                Factura::create([
                    'pedido_id'       => $pedido->id,
                    'usuario_id'      => auth()->id(),
                    'fecha_pago'      => now(),
                    'metodo_pago'     => $validated['metodo_pago'],
                    'subtotal'        => round($totalBruto / 1.10, 2),
                    'iva'             => round($totalBruto - ($totalBruto / 1.10), 2),
                    'descuento'       => 0,
                    'propina'         => round($propina, 2),
                    'total_final'     => round($totalFinal, 2),
                    'estado_pago'     => 'Pagado',
                    'monto_recibido'  => $montoRecibido !== null ? round($montoRecibido, 2) : null,
                    'cambio'          => $cambio !== null ? round($cambio, 2) : null,
                ]);

                $pedido->update(['estado' => 'Entregado', 'hora_entregado' => now()]);
                DetallePedido::where('pedido_id', $pedido->id)->update(['estado_item' => 'Listo']);

                // Liberar la mesa solo si no quedan otros pedidos pendientes de pago.
                if ($pedido->mesa_id && !Pedido::mesaTienePendientes($pedido->mesa_id, $pedido->id)) {
                    Mesa::where('id', $pedido->mesa_id)->update(['estado' => 'Disponible', 'timer_inicio' => null]);
                }

                return [
                    'pedido_id' => $pedido->id,
                    'metodo'    => $validated['metodo_pago'],
                    'total'     => $totalFinal,
                    'cambio'    => $cambio,
                ];
            });

            return response()->json(['success' => true] + $pago);

        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            if ($e instanceof \Illuminate\Database\QueryException && (int) $e->getCode() === 23000) {
                return response()->json(['success' => false, 'message' => 'Este pedido ya tiene una factura registrada.'], 422);
            }
            Log::error('[CajaController@procesarPago]', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al procesar el pago.'], 500);
        }
    }

    /**
     * Historial de pagos registrados en caja.
     */
    public function pagoHistorial()
    {
        $pagos = Factura::with(['pedido.mesa', 'usuario'])
            ->orderByDesc('fecha_pago')
            ->limit(100)
            ->get()
            ->map(function ($factura) {
                return [
                    'id'             => $factura->id,
                    'pedido_id'      => $factura->pedido_id,
                    'mesa'           => $factura->pedido?->mesa?->numero_mesa,
                    'fecha'          => $factura->fecha_pago ? $factura->fecha_pago->format('d/m/Y H:i') : '',
                    'metodo_pago'    => $factura->metodo_pago,
                    'total_final'    => (float) $factura->total_final,
                    'propina'        => (float) $factura->propina,
                    'monto_recibido' => $factura->monto_recibido !== null ? (float) $factura->monto_recibido : null,
                    'cambio'         => $factura->cambio !== null ? (float) $factura->cambio : null,
                    'cajero'         => $factura->usuario
                        ? trim($factura->usuario->nombres . ' ' . $factura->usuario->apellidos)
                        : '—',
                ];
            });

        return response()->json(['success' => true, 'pagos' => $pagos]);
    }
}
