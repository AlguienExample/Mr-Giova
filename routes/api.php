<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\PedidoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas Públicas para Clientes (Menú y creación de pedidos)
Route::apiResource('productos', ProductoController::class)->only(['index', 'show']);
Route::post('/pedidos', [PedidoController::class, 'store']);
Route::get('/pedidos/{id}', [PedidoController::class, 'show'])->whereNumber('id');

// Rutas Protegidas para Personal (requieren sesión web y rol)
Route::middleware(['web', 'auth'])->group(function () {

    // Acciones exclusivas del Administrador
    Route::middleware(['role:Administrador'])->group(function () {
        Route::get('/admin/stats', [PedidoController::class, 'dashboardStats']);
        Route::get('/pedidos', [PedidoController::class, 'index']); // Historial completo con paginación

        // --- Gestión de Inventario (CRUD de Productos) ---
        Route::get('/admin/productos',           [ProductoController::class, 'adminIndex']);
        Route::post('/admin/productos',          [ProductoController::class, 'store']);
        Route::put('/admin/productos/{id}',      [ProductoController::class, 'update']);
        Route::delete('/admin/productos/{id}',   [ProductoController::class, 'destroy']);
        Route::get('/admin/categorias',          [ProductoController::class, 'categorias']);
    });

    // Acciones de Cocina (Cocinero y Administrador)
    Route::middleware(['role:Cocinero,Administrador'])->group(function () {
        Route::get('/pedidos/activos', [PedidoController::class, 'activeOrders']);
        Route::post('/pedidos/{id}/estado', [PedidoController::class, 'updateStatus']);
    });
});

