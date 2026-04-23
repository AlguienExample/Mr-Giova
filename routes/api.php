<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\RoleController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('productos', ProductoController::class);
Route::apiResource('pedidos', PedidoController::class);
Route::apiResource('roles', RoleController::class);
