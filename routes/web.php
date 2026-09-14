<?php

use Illuminate\Support\Facades\Route;
use App\Models\Mesa;
use App\Http\Controllers\AuthController;

// Redireccionar raíz al menú del cliente (mesa 5 por defecto)
Route::get('/', function () {
    return redirect('/menu/mesa/5');
});

// Menú público del cliente (sin autenticación requerida para realizar pedidos)
Route::get('/menu/mesa/{numero_mesa?}', function ($numero_mesa = 5) {
    // Validar el número antes de tocar la BD: evita crear mesas basura con QR manipulados.
    if (!is_numeric($numero_mesa) || (int) $numero_mesa < 1 || (int) $numero_mesa > 999) {
        abort(404);
    }
    $numero_mesa = (int) $numero_mesa;
    $mesa = Mesa::where('numero_mesa', $numero_mesa)->first();
    if (!$mesa) {
        $mesa = Mesa::firstOrCreate(
            ['numero_mesa' => $numero_mesa],
            ['capacidad' => 4, 'estado' => 'Disponible', 'codigo_qr' => 'qr_default_mesa_' . $numero_mesa]
        );
    }
    return view('menu', compact('mesa'));
});

// Rutas de Autenticación para el personal
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas de recuperación de contraseña (sin autenticación)
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('throttle:6,1');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update')->middleware('throttle:6,1');

// Rutas protegidas por Autenticación y Roles
Route::middleware(['auth', 'single.session'])->group(function () {
    
    // Panel de Administración: Restringido únicamente a Administradores
    Route::middleware(['role:Administrador'])->group(function () {
        Route::get('/admin', function () {
            $mesas = Mesa::orderBy('numero_mesa', 'asc')->get();
            return view('admin', compact('mesas'));
        });
    });

    // Tablero de Cocina: Disponible para Cocineros y Administradores
    Route::middleware(['role:Cocinero,Administrador'])->group(function () {
        Route::get('/cocina', function () {
            return view('cocina');
        });
    });

    // Terminal de Caja: Disponible para Cajeros y Administradores
    Route::middleware(['role:Cajero,Administrador'])->group(function () {
        Route::get('/caja', [\App\Http\Controllers\CajaController::class, 'index'])->name('caja');
        Route::get('/caja/mesa/{id}/pedido', [\App\Http\Controllers\CajaController::class, 'getPedidoMesa']);
        Route::get('/caja/pedido/{id}', [\App\Http\Controllers\CajaController::class, 'getPedido']);
        Route::get('/caja/pagos', [\App\Http\Controllers\CajaController::class, 'pagoHistorial']);
        Route::post('/caja/pagos', [\App\Http\Controllers\CajaController::class, 'procesarPago']);
    });
});
