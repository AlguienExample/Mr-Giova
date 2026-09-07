<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoProveedor extends Model
{
    protected $table = 'pedidos_proveedor';

    protected $fillable = [
        'empleado_id',
        'estado',
        'notas',
        'fecha_recibido',
    ];

    protected $casts = [
        'fecha_recibido' => 'datetime',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    /**
     * Empleado que autorizó / generó el pedido.
     */
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    /**
     * Líneas de detalle del pedido (insumos pedidos y cantidades).
     */
    public function detalles()
    {
        return $this->hasMany(DetallePedidoProveedor::class, 'pedido_proveedor_id');
    }
}
