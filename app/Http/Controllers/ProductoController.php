<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource (grouped by active category).
     */
    public function index()
    {
        $categorias = Categoria::with(['productos' => function($query) {
            $query->where('disponible', true);
        }])
        ->where('activo', true)
        ->get();

        return response()->json($categorias);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $producto = Producto::with('categoria')->find($id);
        if (!$producto) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }
        return response()->json($producto);
    }
}
