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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('restrict');
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 10, 2);
            $table->string('imagen_url', 500)->nullable();
            $table->boolean('disponible')->default(true);
            $table->integer('tiempo_preparacion')->nullable()->comment('Minutos');
            $table->text('ingredientes')->nullable();
            $table->timestamps();

            // Índices
            $table->index('categoria_id');
            $table->index('disponible');
            $table->index('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
