<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos';

    protected $fillable = [
        'cliente_id',
        'empleado_id',
        'mesa_id',
        'estado',
        'tipo_pedido',
        'total',
        'notas',
        'hora_inicio_preparacion',
        'hora_listo',
        'hora_entregado',
        'prioridad',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'hora_inicio_preparacion' => 'datetime',
        'hora_listo' => 'datetime',
        'hora_entregado' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function mesa()
    {
        return $this->belongsTo(Mesa::class, 'mesa_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class, 'pedido_id');
    }

    public function factura()
    {
        return $this->hasOne(Factura::class, 'pedido_id');
    }

    /**
     * ¿La mesa tiene otros pedidos pendientes de pago?
     * (no cancelados y sin factura registrada).
     */
    public static function mesaTienePendientes($mesaId, $exceptPedidoId = null): bool
    {
        if (!$mesaId) {
            return false;
        }

        return static::where('mesa_id', $mesaId)
            ->where('estado', '!=', 'Cancelado')
            ->whereDoesntHave('factura')
            ->when($exceptPedidoId, fn ($q) => $q->where('id', '!=', $exceptPedidoId))
            ->exists();
    }
}
