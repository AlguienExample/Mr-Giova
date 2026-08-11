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
        Schema::create('domicilios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->unique()->constrained('pedidos')->onDelete('cascade');
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->foreignId('repartidor_id')->nullable()->constrained('empleados')->onDelete('set null');
            $table->string('direccion_entrega', 255);
            $table->string('barrio', 100)->nullable();
            $table->string('ciudad', 100)->default('Medellín');
            $table->text('referencias')->nullable();
            $table->string('telefono_contacto', 20);
            $table->string('nombre_recibe', 150)->nullable();
            $table->decimal('costo_envio', 10, 2)->default(0);
            $table->decimal('distancia_km', 5, 2)->nullable();
            $table->enum('estado_domicilio', ['Pendiente', 'Asignado', 'En_Camino', 'Entregado', 'Cancelado'])->default('Pendiente');
            $table->timestamp('hora_salida')->nullable();
            $table->timestamp('hora_entrega')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('latitud', 50)->nullable();
            $table->string('longitud', 50)->nullable();
            $table->timestamps();

            // Índices
            $table->index('pedido_id');
            $table->index('cliente_id');
            $table->index('repartidor_id');
            $table->index('estado_domicilio');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domicilios');
    }
};
