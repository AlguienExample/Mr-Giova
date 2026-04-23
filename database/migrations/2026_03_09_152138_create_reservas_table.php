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
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('mesa_id')->constrained('mesas')->onDelete('restrict');
            $table->dateTime('fecha_hora');
            $table->integer('num_personas');
            $table->enum('estado', ['Pendiente', 'Confirmada', 'Cancelada', 'Completada'])->default('Pendiente');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index('cliente_id');
            $table->index('mesa_id');
            $table->index('fecha_hora');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
