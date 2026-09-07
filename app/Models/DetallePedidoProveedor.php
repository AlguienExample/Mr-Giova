<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallePedidoProveedor extends Model
{
    protected $table = 'detalle_pedido_proveedor';

    protected $fillable = [
        'pedido_proveedor_id',
        'materia_prima_id',
        'cantidad_pedida',
        'costo_unitario_momento',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    /**
     * Pedido al que pertenece esta línea de detalle.
     */
    public function pedidoProveedor()
    {
        return $this->belongsTo(PedidoProveedor::class, 'pedido_proveedor_id');
    }

    /**
     * Insumo / materia prima que se está pidiendo.
     */
    public function materiaPrima()
    {
        return $this->belongsTo(MateriaPrima::class, 'materia_prima_id');
    }
}
