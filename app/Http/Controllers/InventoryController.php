<?php

namespace App\Http\Controllers;

use App\Models\DetallePedidoProveedor;
use App\Models\MateriaPrima;
use App\Models\PedidoProveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * InventoryController
 *
 * Gestiona el inventario de materias primas (insumos) del restaurante,
 * así como el ciclo de vida completo de los pedidos de reposición a proveedores.
 * Todas las operaciones quedan registradas en el log del servidor.
 */
class InventoryController extends Controller
{
    /**
     * Lista todos los insumos con KPIs calculados desde la DB.
     */
    public function index()
    {
        try {
            $insumos = MateriaPrima::all()->map(function ($p) {
                return [
                    'id'          => $p->id,
                    'nombre'      => $p->nombre,
                    'categoria'   => $p->categoria,
                    'stock'       => (float) $p->cantidad_actual,
                    'unidad'      => $p->unidad_medida,
                    'precio'      => (float) $p->costo_unitario,
                    'estado'      => $p->estado,          // accessor del modelo
                    'stock_minimo'=> (float) $p->stock_minimo,
                ];
            });

            // Valor total del inventario calculado en BD (una sola query)
            $valorTotal = (float) MateriaPrima::selectRaw('SUM(cantidad_actual * costo_unitario) as total')->value('total') ?? 0;

            $alertasCount   = $insumos->where('estado', 'CRÍTICO')->count();
            $itemsActivos   = $insumos->count();

            return response()->json([
                'insumos' => $insumos->values(),
                'kpis'    => [
                    'valor_total'  => round($valorTotal, 2),
                    'alertas'      => $alertasCount,
                    'rotacion'     => '84%',     // Dato estático de referencia
                    'items_activos'=> $itemsActivos,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[InventoryController@index] Error: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener el inventario.'], 500);
        }
    }

    /**
     * Crea un nuevo insumo / materia prima.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre'          => 'required|string|max:100',
                'categoria'       => 'required|string|max:50',
                'cantidad_actual' => 'required|numeric|min:0',
                'unidad_medida'   => 'required|string|max:20',
                'stock_minimo'    => 'required|numeric|min:0',
                'costo_unitario'  => 'required|numeric|min:0',
            ], [
                'nombre.required'          => 'El nombre del insumo es obligatorio.',
                'categoria.required'       => 'La categoría es obligatoria.',
                'cantidad_actual.required' => 'La cantidad actual es obligatoria.',
                'cantidad_actual.numeric'  => 'La cantidad debe ser un número válido.',
                'unidad_medida.required'   => 'La unidad de medida es obligatoria.',
                'stock_minimo.required'    => 'El stock mínimo es obligatorio.',
                'costo_unitario.required'  => 'El costo unitario es obligatorio.',
            ]);

            $materia = MateriaPrima::create($validated);

            // ── Log de confirmación ─────────────────────────────────────────────
            Log::info('[InventoryController@store] ✅ Insumo creado', [
                'id'       => $materia->id,
                'nombre'   => $materia->nombre,
                'categoria'=> $materia->categoria,
                'stock'    => $materia->cantidad_actual,
                'costo'    => $materia->costo_unitario,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Insumo '{$materia->nombre}' creado correctamente.",
                'data'    => $materia,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Datos inválidos.',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[InventoryController@store] ❌ Error: ' . $e->getMessage(), [
                'payload' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'error'   => 'Error interno al crear el insumo.',
            ], 500);
        }
    }

    /**
     * Actualiza un insumo existente.
     */
    public function update(Request $request, $id)
    {
        try {
            $materia = MateriaPrima::findOrFail($id);

            $validated = $request->validate([
                'nombre'          => 'sometimes|string|max:100',
                'categoria'       => 'sometimes|string|max:50',
                'cantidad_actual' => 'sometimes|numeric|min:0',
                'unidad_medida'   => 'sometimes|string|max:20',
                'stock_minimo'    => 'sometimes|numeric|min:0',
                'costo_unitario'  => 'sometimes|numeric|min:0',
            ]);

            $materia->update($validated);

            Log::info('[InventoryController@update] ✅ Insumo actualizado', [
                'id'      => $id,
                'cambios' => $validated,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Insumo '{$materia->nombre}' actualizado correctamente.",
                'data'    => $materia->fresh(),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'error' => 'Insumo no encontrado.'], 404);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'error' => 'Datos inválidos.', 'errors' => $e->errors()], 422);

        } catch (\Exception $e) {
            Log::error('[InventoryController@update] ❌ Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Error interno al actualizar.'], 500);
        }
    }

    /**
     * Elimina un insumo del inventario.
     *
     * Si el insumo tiene historial de pedidos de reposición asociados (FK en
     * detalle_pedido_proveedor) se devuelve HTTP 409 con un mensaje descriptivo
     * en vez del genérico "Error interno al eliminar.".
     */
    public function destroy($id)
    {
        try {
            $materia = MateriaPrima::findOrFail($id);
            $nombre  = $materia->nombre;
            $materia->delete();

            Log::info('[InventoryController@destroy] 🗑️ Insumo eliminado', [
                'id'     => $id,
                'nombre' => $nombre,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Insumo '{$nombre}' eliminado correctamente.",
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'error' => 'Insumo no encontrado.'], 404);

        } catch (\Illuminate\Database\QueryException $e) {
            // FK constraint: el insumo tiene filas en detalle_pedido_proveedor
            Log::warning('[InventoryController@destroy] ⚠️ FK constraint al intentar eliminar insumo', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error'   => 'No se puede eliminar: este insumo tiene historial de pedidos de reposición asociados.',
            ], 409);

        } catch (\Exception $e) {
            Log::error('[InventoryController@destroy] ❌ Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Error interno al eliminar.'], 500);
        }
    }

    /**
     * Genera un pedido de reposición a proveedores para ítems en estado CRÍTICO.
     * Requiere PIN de autorización del gerente.
     *
     * Crea un PedidoProveedor en estado 'Pendiente' y un DetallePedidoProveedor
     * por cada MateriaPrima::critico() encontrada, con cantidad_pedida sugerida
     * (stock_minimo * 2 - cantidad_actual, nunca negativa) y snapshot del costo.
     */
    public function storeReposicion(Request $request)
    {
        try {
            $request->validate([
                'pin' => 'required|string',
            ]);

            $user = auth()->user();

            // Validar PIN de autorización (confirmación de contraseña)
            if (!Hash::check($request->pin, $user->password)) {
                Log::warning('[InventoryController@storeReposicion] PIN inválido intentado por ' . $user->email);
                return response()->json([
                    'success' => false,
                    'error'   => 'PIN de autorización inválido.',
                ], 403);
            }

            if (!$user->empleado) {
                return response()->json([
                    'success' => false,
                    'error'   => 'No tienes un perfil de empleado asociado para realizar pedidos.',
                ], 422);
            }

            $pedido = DB::transaction(function () use ($user) {
                // Obtener insumos críticos dentro de la transacción
                $criticos = MateriaPrima::critico()->get();

                if ($criticos->isEmpty()) {
                    throw new HttpException(422, 'No hay insumos en estado crítico que requieran reposición.');
                }

                // Crear el pedido cabecera
                $pedido = PedidoProveedor::create([
                    'empleado_id' => $user->empleado->id,
                    'estado'      => 'Pendiente',
                    'notas'       => 'Reposición automática de ítems críticos — ' . now()->toDateTimeString(),
                ]);

                // Crear una línea de detalle por cada insumo crítico
                foreach ($criticos as $insumo) {
                    $cantidadSugerida = max(0, ($insumo->stock_minimo * 2) - $insumo->cantidad_actual);

                    DetallePedidoProveedor::create([
                        'pedido_proveedor_id'    => $pedido->id,
                        'materia_prima_id'       => $insumo->id,
                        'cantidad_pedida'        => $cantidadSugerida,
                        'costo_unitario_momento' => $insumo->costo_unitario,
                    ]);
                }

                return $pedido;
            });

            $pedido->load('detalles.materiaPrima');

            Log::info('[InventoryController@storeReposicion] ✅ Pedido de reposición generado', [
                'pedido_id'      => $pedido->id,
                'items_criticos' => $pedido->detalles->count(),
                'autorizado_por' => 'PIN válido',
                'empleado_id'    => $user->empleado->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Pedido de reposición generado para {$pedido->detalles->count()} ítem(s) crítico(s).",
                'data'    => $pedido,
            ]);

        } catch (HttpException $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], $e->getStatusCode());

        } catch (\Exception $e) {
            Log::error('[InventoryController@storeReposicion] ❌ Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Error al generar el pedido.'], 500);
        }
    }

    /**
     * Devuelve el historial completo de pedidos de reposición.
     * Paginación 100% client-side: se devuelve toda la lista de una vez.
     * Filtro opcional por ?estado= (Pendiente | Enviado | Recibido | Cancelado).
     */
    public function historialReposiciones(Request $request)
    {
        try {
            $query = PedidoProveedor::with('detalles.materiaPrima', 'empleado.usuario')
                ->latest();

            if ($request->filled('estado')) {
                $query->where('estado', $request->input('estado'));
            }

            $pedidos = $query->get();

            Log::info('[InventoryController@historialReposiciones] 📋 Historial consultado', [
                'total'  => $pedidos->count(),
                'filtro' => $request->input('estado', 'todos'),
            ]);

            return response()->json(['data' => $pedidos]);

        } catch (\Exception $e) {
            Log::error('[InventoryController@historialReposiciones] ❌ Error: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener el historial de reposiciones.'], 500);
        }
    }

    /**
     * Marca un pedido de reposición como 'Recibido' y suma el stock de cada insumo.
     *
     * Usa el patrón lock-first para prevenir race conditions:
     * la transacción se abre primero y el lock sobre el pedido se adquiere ANTES
     * de leer su estado, garantizando que dos peticiones simultáneas no dupliquen
     * el incremento de stock.
     */
    public function marcarRecibido($id)
    {
        try {
            $pedido = DB::transaction(function () use ($id) {
                // 1. Adquirir lock sobre el pedido ANTES de leer su estado
                $pedido = PedidoProveedor::with('detalles')
                    ->lockForUpdate()
                    ->findOrFail($id);

                // 2. Verificar estado DESPUÉS de tener el lock
                if ($pedido->estado === 'Recibido') {
                    throw new HttpException(422, 'Este pedido ya fue marcado como recibido.');
                }

                // 3. Sumar stock a cada insumo, con lockForUpdate() dentro de la misma transacción
                foreach ($pedido->detalles as $detalle) {
                    $mp = MateriaPrima::lockForUpdate()->findOrFail($detalle->materia_prima_id);
                    $mp->increment('cantidad_actual', $detalle->cantidad_pedida);
                }

                // 4. Actualizar estado al final, dentro de la misma transacción
                $pedido->update([
                    'estado'         => 'Recibido',
                    'fecha_recibido' => now(),
                ]);

                return $pedido;
            });

            $pedido->load('detalles.materiaPrima', 'empleado.usuario');

            Log::info('[InventoryController@marcarRecibido] ✅ Pedido recibido y stock actualizado', [
                'pedido_id'    => $pedido->id,
                'items'        => $pedido->detalles->count(),
                'fecha_recibido' => $pedido->fecha_recibido,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido marcado como recibido. Stock actualizado correctamente.',
                'data'    => $pedido,
            ]);

        } catch (HttpException $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], $e->getStatusCode());

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'error' => 'Pedido no encontrado.'], 404);

        } catch (\Exception $e) {
            Log::error('[InventoryController@marcarRecibido] ❌ Error: ' . $e->getMessage(), [
                'pedido_id' => $id,
            ]);
            return response()->json(['success' => false, 'error' => 'Error al procesar la recepción del pedido.'], 500);
        }
    }

    /**
     * Cancela un pedido de reposición (no toca el stock, ya que nunca se sumó).
     *
     * Usa el mismo patrón lock-first que marcarRecibido() para evitar que
     * ambas operaciones corran en paralelo y dejen el pedido en estado inconsistente.
     */
    public function cancelarReposicion($id)
    {
        try {
            $pedido = DB::transaction(function () use ($id) {
                // 1. Adquirir lock sobre el pedido ANTES de leer su estado
                $pedido = PedidoProveedor::lockForUpdate()->findOrFail($id);

                // 2. Verificar estado DESPUÉS de tener el lock
                if ($pedido->estado === 'Recibido') {
                    throw new HttpException(422, 'No se puede cancelar un pedido ya recibido.');
                }

                // 3. Cancelar — sin tocar stock porque nunca se sumó
                $pedido->update(['estado' => 'Cancelado']);

                return $pedido;
            });

            $pedido->load('detalles.materiaPrima', 'empleado.usuario');

            Log::info('[InventoryController@cancelarReposicion] 🚫 Pedido cancelado', [
                'pedido_id' => $pedido->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido de reposición cancelado correctamente.',
                'data'    => $pedido,
            ]);

        } catch (HttpException $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], $e->getStatusCode());

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'error' => 'Pedido no encontrado.'], 404);

        } catch (\Exception $e) {
            Log::error('[InventoryController@cancelarReposicion] ❌ Error: ' . $e->getMessage(), [
                'pedido_id' => $id,
            ]);
            return response()->json(['success' => false, 'error' => 'Error al cancelar el pedido.'], 500);
        }
    }
}
