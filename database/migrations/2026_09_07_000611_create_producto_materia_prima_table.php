<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla pivote producto_materia_prima
     *
     * Almacena la receta (bill of materials) de cada producto del menú:
     * qué materias primas consume y la cantidad requerida por unidad vendida.
     */
    public function up(): void
    {
        Schema::create('producto_materia_prima', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')
                  ->constrained('productos')
                  ->cascadeOnDelete();
            $table->foreignId('materia_prima_id')
                  ->constrained('materia_primas')
                  ->cascadeOnDelete();
            $table->decimal('cantidad_requerida', 10, 3)
                  ->comment('Cantidad de materia prima que consume 1 unidad del producto');
            $table->timestamps();

            // Un mismo insumo no puede estar duplicado en la receta de un producto
            $table->unique(['producto_id', 'materia_prima_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_materia_prima');
    }
};
