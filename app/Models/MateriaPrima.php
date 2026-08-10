<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MateriaPrima extends Model
{
    protected $table = 'materia_primas';

    protected $fillable = [
        'nombre',
        'categoria',
        'cantidad_actual',
        'unidad_medida',
        'stock_minimo',
        'costo_unitario',
    ];
}
