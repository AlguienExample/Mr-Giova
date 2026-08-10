<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $table = 'reservas';

    protected $fillable = [
        'cliente_id',
        'mesa_id',
        'fecha_hora',
        'num_personas',
        'estado',
        'notas',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function mesa()
    {
        return $this->belongsTo(Mesa::class);
    }
}
