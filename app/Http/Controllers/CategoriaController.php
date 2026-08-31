<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index()
    {
        return response()->json(Categoria::orderBy('nombre')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean'
        ]);

        $categoria = Categoria::create($validated);
        return response()->json(['success' => true, 'categoria' => $categoria], 201);
    }

    public function update(Request $request, $id)
    {
        $categoria = Categoria::find($id);
        if (!$categoria) {
            return response()->json(['error' => 'Categoría no encontrada'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean'
        ]);

        $categoria->update($validated);
        return response()->json(['success' => true, 'categoria' => $categoria]);
    }

    public function destroy($id)
    {
        $categoria = Categoria::find($id);
        if (!$categoria) {
            return response()->json(['error' => 'Categoría no encontrada'], 404);
        }

        if ($categoria->productos()->count() > 0) {
            return response()->json(['error' => 'No se puede eliminar la categoría porque tiene productos asociados.'], 422);
        }

        $categoria->delete();
        return response()->json(['success' => true, 'message' => 'Categoría eliminada correctamente.']);
    }
}
