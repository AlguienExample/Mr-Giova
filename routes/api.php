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
Route::middleware(['throttle:public-api'])->group(function () {
    Route::apiResource('productos', ProductoController::class)->only(['index', 'show']);
    Route::get('/pedidos/{id}', [PedidoController::class, 'show'])->whereNumber('id');
});

// Creación de pedidos con límite más estricto (anti-spam)
Route::middleware(['throttle:crear-pedido'])->group(function () {
    Route::post('/pedidos', [PedidoController::class, 'store']);
});

// Rutas Protegidas para Personal (requieren sesión web y rol)
Route::middleware(['web', 'auth'])->group(function () {

    // Acciones exclusivas del Administrador
    Route::middleware(['role:Administrador'])->group(function () {
        Route::get('/admin/stats', [\App\Http\Controllers\AdminDashboardController::class, 'stats']);
        
        Route::get('/admin/reservas', [\App\Http\Controllers\ReservaController::class, 'index']);
        Route::post('/admin/reservas', [\App\Http\Controllers\ReservaController::class, 'store']);
        Route::put('/admin/reservas/{id}', [\App\Http\Controllers\ReservaController::class, 'update']);
        Route::delete('/admin/reservas/{id}', [\App\Http\Controllers\ReservaController::class, 'destroy']);
        
        Route::get('/admin/mesas', [\App\Http\Controllers\AdminDashboardController::class, 'getMesasEstado']);
        Route::get('/admin/mesas/{num}/pedido-activo', [\App\Http\Controllers\AdminDashboardController::class, 'getMesaPedidoActivo']);
        Route::put('/admin/mesas/{id}/coordenadas', [\App\Http\Controllers\AdminDashboardController::class, 'updateMesaCoordenadas']);
        Route::put('/admin/mesas/{id}/empleado', [\App\Http\Controllers\AdminDashboardController::class, 'assignEmpleadoMesa']);
        Route::post('/admin/mesas/comanda', [\App\Http\Controllers\AdminDashboardController::class, 'storeComanda']);
        
        Route::get('/admin/insumos', [\App\Http\Controllers\InventoryController::class, 'index']);
        Route::post('/admin/insumos', [\App\Http\Controllers\InventoryController::class, 'store']);
        Route::put('/admin/insumos/{id}', [\App\Http\Controllers\InventoryController::class, 'update']);
        Route::delete('/admin/insumos/{id}', [\App\Http\Controllers\InventoryController::class, 'destroy']);
        Route::post('/admin/insumos/pedido', [\App\Http\Controllers\InventoryController::class, 'storeReposicion']);
        
        Route::get('/admin/staff', [\App\Http\Controllers\StaffController::class, 'index']);
        Route::post('/admin/staff', [\App\Http\Controllers\StaffController::class, 'store']);

        Route::get('/pedidos', [PedidoController::class, 'index']); // Historial completo con paginación

        // --- Gestión de Inventario (CRUD de Productos) ---
        Route::get('/admin/productos',           [\App\Http\Controllers\ProductoController::class, 'adminIndex']);
        Route::post('/admin/productos',          [\App\Http\Controllers\ProductoController::class, 'store']);
        Route::put('/admin/productos/{id}',      [\App\Http\Controllers\ProductoController::class, 'update']);
        Route::delete('/admin/productos/{id}',   [\App\Http\Controllers\ProductoController::class, 'destroy']);
        
        // CRUD de Categorías
        Route::get('/admin/categorias',          [\App\Http\Controllers\CategoriaController::class, 'index']);
        Route::post('/admin/categorias',         [\App\Http\Controllers\CategoriaController::class, 'store']);
        Route::put('/admin/categorias/{id}',     [\App\Http\Controllers\CategoriaController::class, 'update']);
        Route::delete('/admin/categorias/{id}',  [\App\Http\Controllers\CategoriaController::class, 'destroy']);
    });

    // Acciones de Cocina (Cocinero y Administrador)
    Route::middleware(['role:Cocinero,Administrador'])->group(function () {
        Route::get('/pedidos/activos', [PedidoController::class, 'activeOrders']);
        Route::post('/pedidos/{id}/estado', [PedidoController::class, 'updateStatus']);
    });
});

