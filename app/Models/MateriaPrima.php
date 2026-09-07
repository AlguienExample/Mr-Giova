<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    // ── Relaciones ────────────────────────────────────────────────────────────

    /**
     * Productos que usan esta materia prima en su receta.
     */
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_materia_prima')
                     ->withPivot('cantidad_requerida')
                     ->withTimestamps();
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Filtra los insumos cuyo stock está en estado CRÍTICO
     * (cantidad_actual <= stock_minimo). Fuente única de la regla de negocio.
     */
    public function scopeCritico(Builder $query): Builder
    {
        return $query->whereColumn('cantidad_actual', '<=', 'stock_minimo');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Devuelve el estado del insumo: 'CRÍTICO' u 'ÓPTIMO'.
     * Fuente única de la regla de negocio para comparaciones en PHP.
     */
    public function getEstadoAttribute(): string
    {
        return $this->cantidad_actual <= $this->stock_minimo ? 'CRÍTICO' : 'ÓPTIMO';
    }
}
