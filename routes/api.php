<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\PedidoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Endpoints personalizados para el flujo en tiempo real
Route::get('/pedidos/activos', [PedidoController::class, 'activeOrders']);
Route::post('/pedidos/{id}/estado', [PedidoController::class, 'updateStatus']);
Route::get('/admin/stats', [PedidoController::class, 'dashboardStats']);

// Recursos estándar
Route::apiResource('productos', ProductoController::class);
Route::apiResource('pedidos', PedidoController::class);
