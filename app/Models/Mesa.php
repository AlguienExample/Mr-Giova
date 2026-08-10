<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mesa extends Model
{
    use HasFactory;

    protected $table = 'mesas';

    protected $fillable = [
        'numero_mesa',
        'capacidad',
        'estado',
        'codigo_qr',
        'ubicacion',
        'empleado_id',
        'zona',
        'timer_inicio',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'mesa_id');
    }
}
