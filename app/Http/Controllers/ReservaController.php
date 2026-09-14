<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\Usuario;
use App\Models\AuditoriaReserva;
use App\Models\NotificacionCliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
            return DB::transaction(function () use ($validated, $request) {
            // Verificar que la mesa exista y no esté ocupada (con bloqueo)
            $mesa = Mesa::lockForUpdate()->find($validated['mesa_id']);
            if (!$mesa) {
                return response()->json([
                    'success' => false,
                    'error'   => 'La mesa seleccionada no existe.',
                ], 422);
            }

            if ($mesa->estado === 'Ocupada') {
                return response()->json([
                    'success' => false,
                    'error'   => 'La mesa seleccionada está ocupada en este momento.',
                ], 422);
            }

            // Verificar conflicto de horario: ventana de 3 horas por reserva.
// Las Pendientes también bloquean: si no, se genera overbooking silencioso
            // (una Pendiente + una Confirmada en la misma mesa y horario).
            $fechaHora = Carbon::parse($validated['fecha'] . ' ' . $validated['hora'] . ':00');
            $conflicto = Reserva::where('mesa_id', $validated['mesa_id'])
                ->whereIn('estado', ['Pendiente', 'Confirmada'])
                ->whereBetween('fecha_hora', [
                    $fechaHora->copy()->subMinutes(180),
                    $fechaHora->copy()->addMinutes(180),
                ])
                ->lockForUpdate()
                ->first();

            if ($conflicto) {
                return response()->json([
                    'success' => false,
                    'error'   => 'La mesa ya tiene una reserva pendiente o confirmada en un horario cercano.',
                ], 422);
            }

            // Obtener cliente: busco por nombre exacto primero; si no coincide,
            // crear el cliente real (inactivo: no puede iniciar sesión) en vez de
            // atribuir la reserva a un cliente aleatorio, que corrompía reportes.
            $cliente = Cliente::whereHas('usuario', function ($q) use ($validated) {
                $q->whereRaw('LOWER(TRIM(CONCAT(COALESCE(nombres,""), " ", COALESCE(apellidos,"")))) = ?', [strtolower(trim($validated['nombre']))]);
            })->first();

            if (!$cliente) {
                $partes = explode(' ', trim($validated['nombre']), 2);
                $base = Str::slug($partes[0], '') ?: 'cliente';
                $email = null;
                for ($i = 0; $i < 5; $i++) {
                    $candidato = 'reserva.' . strtolower($base) . rand(100, 999) . '@saborapueblo.com';
                    if (!Usuario::where('email', $candidato)->exists()) {
                        $email = $candidato;
                        break;
                    }
                }
                if (!$email) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'No se pudo registrar el cliente de la reserva. Intente de nuevo.',
                    ], 422);
                }
                $usuarioNuevo = Usuario::create([
                    'nombres'   => $partes[0],
                    'apellidos' => $partes[1] ?? '',
                    'email'     => $email,
                    'password'  => Hash::make(Str::password(16)),
                    'rol_id'    => \App\Models\Role::where('name', 'Cliente')->value('id') ?? 1,
                    'activo'    => false,
                ]);
                $cliente = Cliente::create(['usuario_id' => $usuarioNuevo->id]);
            }

            // Construir las notas con el nombre del cliente
            $notasCompletas = 'Cliente: ' . $validated['nombre'];
            if (!empty($validated['notas'])) {
                $notasCompletas .= ' — ' . $validated['notas'];
            }

            // ── Crear la reserva en la DB ─────────────────────────────────────────
            $reserva = Reserva::create([
                'cliente_id'   => $cliente->id,
                'mesa_id'      => $validated['mesa_id'],
                'fecha_hora'   => $validated['fecha'] . ' ' . $validated['hora'] . ':00',
                'num_personas' => $validated['personas'],
                'estado'       => 'Confirmada',
                'notas'        => $notasCompletas,
            ]);

            // Marcar la mesa como Reservada
            $mesa->update(['estado' => 'Reservada']);

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
            });

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
            return DB::transaction(function () use ($validated, $reserva) {
            // Re-leer con bloqueo: evita editar una reserva recién cancelada/completada
            $reserva = Reserva::lockForUpdate()->find($reserva->id);
            if (!$reserva) {
                return response()->json(['success' => false, 'error' => 'La reserva ya no existe.'], 404);
            }
            if (in_array($reserva->estado, ['Cancelada', 'Completada'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se pueden modificar reservas con estado ' . $reserva->estado . '.'
                ], 422);
            }

            // Verificar conflicto de horario si pasa a Confirmada (ventana de 3 horas)
            $nuevaFechaHora = $validated['fecha'] . ' ' . $validated['hora'] . ':00';
            $fechaHora = Carbon::parse($nuevaFechaHora);
            $conflicto = Reserva::where('mesa_id', $validated['mesa_id'])
                ->whereIn('estado', ['Pendiente', 'Confirmada'])
                ->where('id', '!=', $reserva->id)
                ->whereBetween('fecha_hora', [
                    $fechaHora->copy()->subMinutes(180),
                    $fechaHora->copy()->addMinutes(180),
                ])
                ->lockForUpdate()
                ->first();

            if ($conflicto && in_array($validated['estado'], ['Pendiente', 'Confirmada'])) {
                return response()->json([
                    'success' => false,
                    'error'   => 'La mesa ya tiene otra reserva pendiente o confirmada en un horario cercano.',
                ], 422);
            }

            // Verificar que la mesa nueva exista y no esté ocupada (con bloqueo)
            $mesaNueva = Mesa::lockForUpdate()->find($validated['mesa_id']);
            if (!$mesaNueva) {
                return response()->json([
                    'success' => false,
                    'error'   => 'La mesa seleccionada no existe.',
                ], 422);
            }
            if ($mesaNueva->estado === 'Ocupada') {
                return response()->json([
                    'success' => false,
                    'error'   => 'La mesa seleccionada está ocupada en este momento.',
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

            // ── Sincronizar estado de las mesas ──────────────────────────────────
            if ($valoresAnteriores['mesa_id'] != $reserva->mesa_id && $valoresAnteriores['estado'] === 'Confirmada') {
                $mesaAnterior = Mesa::find($valoresAnteriores['mesa_id']);
                if ($mesaAnterior && $mesaAnterior->estado === 'Reservada' &&
                    !Reserva::where('mesa_id', $mesaAnterior->id)
                        ->where('estado', 'Confirmada')
                        ->where('id', '!=', $reserva->id)
                        ->exists()
                ) {
                    $mesaAnterior->update(['estado' => 'Disponible']);
                }
            }

            if ($validated['estado'] === 'Confirmada') {
                $mesaNueva->update(['estado' => 'Reservada']);
            } elseif ($valoresAnteriores['estado'] === 'Confirmada') {
                // Cancelada o Completada: liberar la mesa si ya no tiene reservas confirmadas
                if ($mesaNueva->estado === 'Reservada' &&
                    !Reserva::where('mesa_id', $mesaNueva->id)
                        ->where('estado', 'Confirmada')
                        ->where('id', '!=', $reserva->id)
                        ->exists()
                ) {
                    $mesaNueva->update(['estado' => 'Disponible']);
                }
            }

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
            });

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
            return DB::transaction(function () use ($reserva, $id) {
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

            // Liberar la mesa si quedó marcada como Reservada y no tiene otras reservas confirmadas
            if ($reserva->mesa_id) {
                $mesaReserva = Mesa::lockForUpdate()->find($reserva->mesa_id);
                if ($mesaReserva && $mesaReserva->estado === 'Reservada' &&
                    !Reserva::where('mesa_id', $mesaReserva->id)
                        ->where('estado', 'Confirmada')
                        ->where('id', '!=', $reserva->id)
                        ->exists()
                ) {
                    $mesaReserva->update(['estado' => 'Disponible']);
                }
            }

            // Eliminar la reserva primero: si falla, no quedan auditorías ni avisos fantasma
            $reserva->delete();

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

            Log::info("[ReservaController@destroy] Reserva #{$id} eliminada por Admin: " . ($usuarioAdmin ? $usuarioAdmin->email : 'system'));

            return response()->json([
                'success' => true,
                'message' => 'Reserva eliminada con éxito y cliente notificado del cambio.'
            ]);
            });

        } catch (\Exception $e) {
            Log::error('[ReservaController@destroy] Error al eliminar reserva: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Error al eliminar la reserva.'], 500);
        }
    }
}
