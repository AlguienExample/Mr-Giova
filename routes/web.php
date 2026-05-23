<?php

use Illuminate\Support\Facades\Route;
use App\Models\Mesa;

Route::get('/', function () {
    return redirect('/menu/mesa/5');
});

Route::get('/menu/mesa/{numero_mesa?}', function ($numero_mesa = 5) {
    // Buscar la mesa para enviarla como contexto
    $mesa = Mesa::where('numero_mesa', $numero_mesa)->first();
    if (!$mesa) {
        $mesa = Mesa::firstOrCreate(
            ['numero_mesa' => $numero_mesa],
            ['capacidad' => 4, 'estado' => 'Disponible', 'codigo_qr' => 'qr_default_mesa_' . $numero_mesa]
        );
    }
    return view('menu', compact('mesa'));
});

Route::get('/cocina', function () {
    return view('cocina');
});

Route::get('/admin', function () {
    // Obtener mesas para el estado de mesas del dashboard admin
    $mesas = Mesa::orderBy('numero_mesa', 'asc')->get();
    return view('admin', compact('mesas'));
});
