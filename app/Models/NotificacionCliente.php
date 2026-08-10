<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificacionCliente extends Model
{
    protected $table = 'notificaciones_clientes';

    protected $fillable = [
        'cliente_id',
        'cliente_nombre',
        'tipo_notificacion',
        'canal',
        'mensaje',
        'estado',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
