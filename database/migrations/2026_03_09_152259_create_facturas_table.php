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
        Schema::create('factura', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->unique()->constrained('pedidos')->onDelete('restrict');
            $table->timestamp('fecha_pago')->useCurrent();
            $table->enum('metodo_pago', ['Efectivo', 'Tarjeta', 'Transferencia', 'Billetera_Digital']);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('iva', 10, 2)->default(0);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('propina', 10, 2)->default(0);
            $table->decimal('total_final', 10, 2);
            $table->enum('estado_pago', ['Pendiente', 'Pagado', 'Anulado'])->default('Pendiente');
            $table->timestamps();

            // Índices
            $table->index('pedido_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factura');
    }
};
