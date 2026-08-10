<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mesa;
use App\Models\Pedido;
use Carbon\Carbon;

class CajaController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();

        // Obtener todas las mesas con información de pedidos de hoy
        $mesas = Mesa::orderBy('numero_mesa', 'asc')->get()->map(function ($mesa) use ($hoy) {
            // ¿Tiene algún pedido en la jornada de hoy?
            $pedidoHoy = Pedido::where('mesa_id', $mesa->id)
                ->whereDate('created_at', $hoy)
                ->whereNotIn('estado', ['Cancelado'])
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
        $ocupadas = $mesas->whereIn('estado', ['Ocupada', 'Cuenta'])->count();
        $conPedidoHoy = $mesas->where('tiene_pedido_hoy', true)->count();

        return view('caja', compact('mesas', 'libres', 'ocupadas', 'conPedidoHoy'));
    }

    public function getPedidoMesa($mesa_id)
    {
        $pedido = Pedido::with(['detalles.producto', 'empleado.usuario'])
            ->where('mesa_id', $mesa_id)
            ->whereNotIn('estado', ['Cancelado'])
            ->latest()
            ->first();

        if (!$pedido) {
            return response()->json(['success' => false, 'message' => 'No hay pedido activo']);
        }

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
            'items'      => $items,
            'total'      => (float) $pedido->total,
            'created_at' => $pedido->created_at->format('Y-m-d H:i:s'),
        ]);
    }
}
