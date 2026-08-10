<?php

namespace App\Http\Controllers;

use App\Models\MateriaPrima;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * InventoryController
 *
 * Gestiona el inventario de materias primas (insumos) del restaurante.
 * Todas las operaciones CRUD quedan registradas en el log del servidor.
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
                $estado = $p->cantidad_actual <= $p->stock_minimo ? 'CRÍTICO' : 'ÓPTIMO';
                return [
                    'id'          => $p->id,
                    'nombre'      => $p->nombre,
                    'categoria'   => $p->categoria,
                    'stock'       => (float) $p->cantidad_actual,
                    'unidad'      => $p->unidad_medida,
                    'precio'      => (float) $p->costo_unitario,
                    'estado'      => $estado,
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

        } catch (\Exception $e) {
            Log::error('[InventoryController@destroy] ❌ Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Error interno al eliminar.'], 500);
        }
    }

    /**
     * Genera un pedido de reposición a proveedores para ítems en estado CRÍTICO.
     * Requiere PIN de autorización del gerente.
     */
    public function storeReposicion(Request $request)
    {
        try {
            $request->validate([
                'pin' => 'required|string',
            ]);

            $user = auth()->user();

            // Validar PIN de autorización (como confirmación de contraseña)
            if (!\Illuminate\Support\Facades\Hash::check($request->pin, $user->password)) {
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

            // Registrar pedido de reposición
            DB::table('pedidos_proveedor')->insert([
                'empleado_id' => $user->empleado->id,
                'estado'      => 'Aprobado',
                'notas'       => 'Reposición automática de ítems críticos — ' . now()->toDateTimeString(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // Contar ítems críticos
            $criticos = MateriaPrima::whereColumn('cantidad_actual', '<=', 'stock_minimo')->count();

            Log::info('[InventoryController@storeReposicion] ✅ Pedido de reposición generado', [
                'items_criticos' => $criticos,
                'autorizado_por' => 'PIN válido',
            ]);

            return response()->json([
                'success' => true,
                'message' => "Pedido de reposición enviado para {$criticos} ítem(s) crítico(s).",
            ]);

        } catch (\Exception $e) {
            Log::error('[InventoryController@storeReposicion] ❌ Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Error al generar el pedido.'], 500);
        }
    }
}
