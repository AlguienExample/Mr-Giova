<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditoriaReserva extends Model
{
    protected $table = 'auditoria_reservas';

    protected $fillable = [
        'reserva_id',
        'usuario_id',
        'accion',
        'detalles',
    ];

    protected $casts = [
        'detalles' => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }
}
