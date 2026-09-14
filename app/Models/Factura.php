<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $table = 'factura';

    protected $fillable = [
        'pedido_id',
        'usuario_id',
        'fecha_pago',
        'metodo_pago',
        'subtotal',
        'iva',
        'descuento',
        'propina',
        'total_final',
        'estado_pago',
        'monto_recibido',
        'cambio',
    ];

    protected $casts = [
        'fecha_pago'     => 'datetime',
        'subtotal'       => 'decimal:2',
        'iva'            => 'decimal:2',
        'descuento'      => 'decimal:2',
        'propina'        => 'decimal:2',
        'total_final'    => 'decimal:2',
        'monto_recibido' => 'decimal:2',
        'cambio'         => 'decimal:2',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}