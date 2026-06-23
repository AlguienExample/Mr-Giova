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
Route::post('/login', [AuthController::class, 'login']);
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas protegidas por Autenticación y Roles
Route::middleware(['auth'])->group(function () {
    
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
});

