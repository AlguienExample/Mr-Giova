<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    /**
     * Display a listing of products for the public menu (grouped by active category).
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
     * Display all products for admin (including hidden/disabled, with stock).
     */
    public function adminIndex()
    {
        $productos = Producto::with('categoria')
            ->orderBy('categoria_id')
            ->orderBy('nombre')
            ->get();

        return response()->json($productos);
    }

    /**
     * Return all categories for admin forms.
     */
    public function categorias()
    {
        return response()->json(Categoria::orderBy('nombre')->get());
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

    /**
     * Store a newly created product (admin only).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'categoria_id'       => 'required|exists:categorias,id',
            'nombre'             => 'required|string|max:150',
            'descripcion'        => 'nullable|string',
            'precio'             => 'required|numeric|min:0',
            'imagen_url'         => 'nullable|url|max:500',
            'disponible'         => 'boolean',
            'tiempo_preparacion' => 'nullable|integer|min:0',
            'ingredientes'       => 'nullable|string',
            'stock'              => 'required|integer|min:0',
        ]);

        $producto = Producto::create($validated);
        $producto->load('categoria');

        return response()->json(['success' => true, 'producto' => $producto], 201);
    }

    /**
     * Update the specified product (admin only).
     */
    public function update(Request $request, $id)
    {
        $producto = Producto::find($id);
        if (!$producto) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        $validated = $request->validate([
            'categoria_id'       => 'required|exists:categorias,id',
            'nombre'             => 'required|string|max:150',
            'descripcion'        => 'nullable|string',
            'precio'             => 'required|numeric|min:0',
            'imagen_url'         => 'nullable|url|max:500',
            'disponible'         => 'boolean',
            'tiempo_preparacion' => 'nullable|integer|min:0',
            'ingredientes'       => 'nullable|string',
            'stock'              => 'required|integer|min:0',
        ]);

        $producto->update($validated);
        $producto->load('categoria');

        return response()->json(['success' => true, 'producto' => $producto]);
    }

    /**
     * Remove the specified product (admin only).
     */
    public function destroy($id)
    {
        $producto = Producto::find($id);
        if (!$producto) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        // Prevent deletion if there are order details referencing this product
        if ($producto->detallesPedido()->count() > 0) {
            // Instead of deleting, mark as unavailable
            $producto->update(['disponible' => false, 'stock' => 0]);
            return response()->json([
                'success' => true,
                'message' => 'El producto tiene pedidos asociados. Se marcó como no disponible y su stock fue puesto en 0.'
            ]);
        }

        $producto->delete();
        return response()->json(['success' => true, 'message' => 'Producto eliminado correctamente.']);
    }
}
