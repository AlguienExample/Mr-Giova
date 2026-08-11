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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->foreignId('empleado_id')->nullable()->constrained('empleados')->onDelete('set null');
            $table->foreignId('mesa_id')->nullable()->constrained('mesas')->onDelete('set null');
            $table->enum('estado', ['Nuevo', 'En_Preparacion', 'Listo', 'Entregado', 'Cancelado'])->default('Nuevo');
            $table->enum('tipo_pedido', ['Presencial', 'Delivery', 'Para_Llevar']);
            $table->decimal('total', 10, 2)->default(0);
            $table->text('notas')->nullable();
            $table->timestamp('hora_inicio_preparacion')->nullable();
            $table->timestamp('hora_listo')->nullable();
            $table->timestamp('hora_entregado')->nullable();
            $table->enum('prioridad', ['Normal', 'Alta', 'Urgente'])->default('Normal');
            $table->timestamps();

            // Índices
            $table->index('cliente_id');
            $table->index('empleado_id');
            $table->index('mesa_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
