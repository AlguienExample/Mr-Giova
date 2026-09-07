<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('detalle_pedido_proveedor', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pedido_proveedor_id')
                  ->constrained('pedidos_proveedor')
                  ->cascadeOnDelete();

            // Sin cascadeOnDelete intencionalmente: queremos que la FK
            // lance una excepción si se intenta eliminar un insumo con historial.
            $table->foreignId('materia_prima_id')
                  ->constrained('materia_primas');

            $table->decimal('cantidad_pedida', 10, 2);

            // Snapshot del costo al momento de generar el pedido.
            // Se guarda para que los reportes no dependan del costo_unitario actual
            // de MateriaPrima, que puede cambiar después.
            $table->decimal('costo_unitario_momento', 10, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_pedido_proveedor');
    }
};
