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
        Schema::create('detalle_pedido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('restrict');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->string('notas_especiales', 255)->nullable();
            $table->enum('estado_item', ['Pendiente', 'En_Preparacion', 'Listo'])->default('Pendiente');
            $table->timestamps();

            // Índices
            $table->index('pedido_id');
            $table->index('producto_id');
            $table->index('estado_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_pedido');
    }
};
