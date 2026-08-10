<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\AuditoriaReserva;
use App\Models\NotificacionCliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * ReservaController
 *
 * Gestiona las operaciones de reservas del restaurante.
 * Todas las operaciones de escritura quedan registradas en los logs del servidor.
 */
class ReservaController extends Controller
{
    /**
     * Retorna todas las reservas del día (o de la fecha filtrada).
     * Soporta filtro por query param ?fecha=YYYY-MM-DD
     */
    public function index(Request $request)
    {
        try {
            $query = Reserva::with('cliente.usuario', 'mesa')
                ->orderBy('fecha_hora', 'asc');

            if ($request->has('fecha') && $request->fecha) {
                $query->whereDate('fecha_hora', $request->fecha);
            }

            $reservas = $query->get()->map(function ($r) {
                return [
                    'id'             => $r->id,
                    'cliente_nombre' => $r->cliente && $r->cliente->usuario
                        ? $r->cliente->usuario->nombres . ' ' . $r->cliente->usuario->apellidos
                        : 'Cliente Estándar',
                    'mesa_numero'    => $r->mesa ? $r->mesa->numero_mesa : null,
                    'mesa_id'        => $r->mesa_id,
                    'fecha_hora'     => $r->fecha_hora->format('Y-m-d H:i'),
                    'fecha'          => $r->fecha_hora->format('Y-m-d'),
                    'hora'           => $r->fecha_hora->format('H:i'),
                    'num_personas'   => $r->num_personas,
                    'estado'         => $r->estado,
                    'notas'          => $r->notas,
                    'is_vip'         => str_contains(strtolower($r->notas ?? ''), 'vip'),
                ];
            });

            return response()->json($reservas);

        } catch (\Exception $e) {
            Log::error('[ReservaController@index] Error al obtener reservas: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener las reservas.'], 500);
        }
    }

    /**
     * Crea una nueva reserva.
     *
     * Requiere:
     *  - nombre: nombre del cliente (se guarda en notas)
     *  - fecha: formato YYYY-MM-DD
     *  - hora: formato HH:MM
     *  - personas: número entero >= 1
     *  - mesa_id: ID de mesa existente (REQUERIDO — el admin debe asignar mesa)
     *  - notas: observaciones opcionales
     */
    public function store(\App\Http\Requests\StoreReservaRequest $request)
    {
        $validated = $request->validated();


        try {
            // Verificar que la mesa no tenga otra reserva Confirmada en el mismo horario
            $conflicto = Reserva::where('mesa_id', $validated['mesa_id'])
                ->where('estado', 'Confirmada')
                ->whereDate('fecha_hora', $validated['fecha'])
                ->first();

            if ($conflicto) {
                return response()->json([
                    'success' => false,
                    'error'   => 'La mesa ya tiene una reserva confirmada para esa fecha.',
                ], 422);
            }

            // Obtener cliente (fallback al primer cliente disponible para el panel admin)
            $cliente = Cliente::first();

            if (!$cliente) {
                Log::warning('[ReservaController@store] No hay clientes en la DB — no se puede crear reserva.');
                return response()->json([
                    'success' => false,
                    'error'   => 'No hay clientes registrados en el sistema.',
                ], 422);
            }

            // Construir las notas con el nombre del cliente
            $notasCompletas = 'Cliente: ' . $validated['nombre'];
            if (!empty($validated['notas'])) {
                $notasCompletas .= ' — ' . $validated['notas'];
            }

            // ── Crear la reserva en la DB ─────────────────────────────────────────
            $reserva = Reserva::create([
                'cliente_id'  => $cliente->id,
                'mesa_id'     => $validated['mesa_id'],
                'fecha_hora'  => $validated['fecha'] . ' ' . $validated['hora'] . ':00',
                'num_personas'=> $validated['personas'],
                'estado'      => 'Confirmada',
                'notas'       => $notasCompletas,
            ]);

            // ── Log de confirmación ───────────────────────────────────────────────
            Log::info('[ReservaController@store] ✅ Reserva creada', [
                'reserva_id'  => $reserva->id,
                'cliente'     => $validated['nombre'],
                'mesa_id'     => $validated['mesa_id'],
                'fecha_hora'  => $reserva->fecha_hora,
                'personas'    => $validated['personas'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reserva confirmada exitosamente.',
                'reserva' => [
                    'id'          => $reserva->id,
                    'mesa_id'     => $reserva->mesa_id,
                    'fecha_hora'  => $reserva->fecha_hora->format('Y-m-d H:i'),
                    'num_personas'=> $reserva->num_personas,
                    'estado'      => $reserva->estado,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('[ReservaController@store] ❌ Error al crear reserva: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Error interno al crear la reserva. Intente de nuevo.',
            ], 500);
        }
    }

    /**
     * Edita una reserva existente.
     */
    public function update(Request $request, $id)
    {
        $reserva = Reserva::with('cliente.usuario')->findOrFail($id);
        $hoy = Carbon::now();

        // Restricción de tiempo: no editar reservas en el pasado
        if ($reserva->fecha_hora->isBefore($hoy)) {
            return response()->json([
                'success' => false,
                'error' => 'No se puede editar una reserva que ya ha pasado o está en curso.'
            ], 422);
        }

        // Restricción de estado: no editar canceladas o completadas
        if (in_array($reserva->estado, ['Cancelada', 'Completada'])) {
            return response()->json([
                'success' => false,
                'error' => 'No se pueden modificar reservas con estado ' . $reserva->estado . '.'
            ], 422);
        }

        $validated = $request->validate([
            'nombre'   => 'required|string|max:100',
            'fecha'    => 'required|date|after_or_equal:today',
            'hora'     => 'required|string',
            'personas' => 'required|integer|min:1|max:50',
            'mesa_id'  => 'required|integer|exists:mesas,id',
            'estado'   => 'required|string|in:Pendiente,Confirmada,Cancelada,Completada',
            'notas'    => 'nullable|string|max:500',
        ], [
            'nombre.required'   => 'El nombre del cliente es obligatorio.',
            'fecha.required'    => 'La fecha es obligatoria.',
            'fecha.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'hora.required'     => 'La hora es obligatoria.',
            'personas.required' => 'El número de comensales es obligatorio.',
            'personas.min'      => 'Debe haber al menos 1 comensal.',
            'mesa_id.required'  => 'Debe seleccionar una mesa.',
            'mesa_id.exists'    => 'La mesa seleccionada no existe.',
        ]);

        try {
            // Verificar conflicto de mesa si cambia mesa, fecha u hora
            $nuevaFechaHora = $validated['fecha'] . ' ' . $validated['hora'] . ':00';
            $conflicto = Reserva::where('mesa_id', $validated['mesa_id'])
                ->where('estado', 'Confirmada')
                ->where('id', '!=', $reserva->id)
                ->whereDate('fecha_hora', $validated['fecha'])
                ->first();

            if ($conflicto && $validated['estado'] === 'Confirmada') {
                return response()->json([
                    'success' => false,
                    'error'   => 'La mesa ya tiene otra reserva confirmada para esa fecha.',
                ], 422);
            }

            // Guardar valores anteriores para la auditoría
            $valoresAnteriores = $reserva->toArray();

            // Construir notas
            $notasCompletas = 'Cliente: ' . $validated['nombre'];
            if (!empty($validated['notas'])) {
                $notasCompletas .= ' — ' . $validated['notas'];
            }

            // Actualizar la reserva
            $reserva->update([
                'mesa_id'     => $validated['mesa_id'],
                'fecha_hora'  => $nuevaFechaHora,
                'num_personas'=> $validated['personas'],
                'estado'      => $validated['estado'],
                'notas'       => $notasCompletas,
            ]);

            // Auditoría
            $usuarioAdmin = Auth::user();
            AuditoriaReserva::create([
                'reserva_id' => $reserva->id,
                'usuario_id' => $usuarioAdmin ? $usuarioAdmin->id : null,
                'accion'     => 'editada',
                'detalles'   => [
                    'admin_email' => $usuarioAdmin ? $usuarioAdmin->email : 'admin_sistema',
                    'valores_anteriores' => $valoresAnteriores,
                    'valores_nuevos' => $reserva->toArray(),
                ]
            ]);

            // Notificación Simulada al Cliente
            $clienteNombre = $validated['nombre'];
            $tipoNotif = 'Email/SMS';
            $canal = $reserva->cliente && $reserva->cliente->telefono ? $reserva->cliente->telefono : 'correo@cliente.com';
            $mensaje = "Hola $clienteNombre, tu reserva en Sabor a Pueblo ha sido actualizada al día {$validated['fecha']} a las {$validated['hora']} para {$validated['personas']} personas (Estado: {$validated['estado']}). ¡Te esperamos!";
            
            NotificacionCliente::create([
                'cliente_id' => $reserva->cliente_id,
                'cliente_nombre' => $clienteNombre,
                'tipo_notificacion' => $tipoNotif,
                'canal' => $canal,
                'mensaje' => $mensaje,
                'estado' => 'Enviada'
            ]);

            Log::info("[ReservaController@update] Reserva #{$reserva->id} editada por Admin: " . ($usuarioAdmin ? $usuarioAdmin->email : 'system'));

            return response()->json([
                'success' => true,
                'message' => 'Reserva actualizada y cliente notificado.',
                'reserva' => $reserva
            ]);

        } catch (\Exception $e) {
            Log::error('[ReservaController@update] Error al editar reserva: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Error al actualizar la reserva.'], 500);
        }
    }

    /**
     * Elimina una reserva existente.
     */
    public function destroy(Request $request, $id)
    {
        $reserva = Reserva::with('cliente.usuario')->findOrFail($id);
        $hoy = Carbon::now();

        // Restricción de tiempo: no eliminar pasadas
        if ($reserva->fecha_hora->isBefore($hoy)) {
            return response()->json([
                'success' => false,
                'error' => 'No se puede eliminar una reserva que ya ha pasado.'
            ], 422);
        }

        // Si la reserva está en curso (por ejemplo, hoy dentro del rango de servicio), o ya completada, no permitir
        if ($reserva->estado === 'Completada') {
            return response()->json([
                'success' => false,
                'error' => 'No se puede eliminar una reserva que ya ha sido completada.'
            ], 422);
        }

        try {
            $valoresAnteriores = $reserva->toArray();
            $clienteNombre = $reserva->cliente && $reserva->cliente->usuario 
                ? $reserva->cliente->usuario->nombres . ' ' . $reserva->cliente->usuario->apellidos 
                : 'Cliente';

            if (str_contains(strtolower($reserva->notas ?? ''), 'cliente:')) {
                // Extraer el nombre si está en las notas
                preg_match('/Cliente:\s*([^—\n]+)/i', $reserva->notas, $matches);
                if (isset($matches[1])) {
                    $clienteNombre = trim($matches[1]);
                }
            }

            // Auditoría de Eliminación
            $usuarioAdmin = Auth::user();
            AuditoriaReserva::create([
                'reserva_id' => null, // Ya no existirá la reserva
                'usuario_id' => $usuarioAdmin ? $usuarioAdmin->id : null,
                'accion'     => 'eliminada',
                'detalles'   => [
                    'reserva_id_original' => $id,
                    'admin_email' => $usuarioAdmin ? $usuarioAdmin->email : 'admin_sistema',
                    'reserva_detalles' => $valoresAnteriores,
                ]
            ]);

            // Notificación al Cliente sobre la cancelación
            $canal = $reserva->cliente && $reserva->cliente->telefono ? $reserva->cliente->telefono : 'correo@cliente.com';
            $mensaje = "Hola $clienteNombre, te informamos que tu reserva para el día {$reserva->fecha_hora->format('Y-m-d')} a las {$reserva->fecha_hora->format('H:i')} en Sabor a Pueblo ha sido Cancelada por el establecimiento. Disculpa los inconvenientes.";

            NotificacionCliente::create([
                'cliente_id' => $reserva->cliente_id,
                'cliente_nombre' => $clienteNombre,
                'tipo_notificacion' => 'SMS/Email',
                'canal' => $canal,
                'mensaje' => $mensaje,
                'estado' => 'Enviada'
            ]);

            // Eliminar la reserva
            $reserva->delete();

            Log::info("[ReservaController@destroy] Reserva #{$id} eliminada por Admin: " . ($usuarioAdmin ? $usuarioAdmin->email : 'system'));

            return response()->json([
                'success' => true,
                'message' => 'Reserva eliminada con éxito y cliente notificado del cambio.'
            ]);

        } catch (\Exception $e) {
            Log::error('[ReservaController@destroy] Error al eliminar reserva: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Error al eliminar la reserva.'], 500);
        }
    }
}
