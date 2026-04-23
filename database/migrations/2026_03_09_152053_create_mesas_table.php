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
        Schema::create('mesas', function (Blueprint $table) {
            $table->id();
            $table->integer('numero_mesa')->unique();
            $table->integer('capacidad');
            $table->enum('estado', ['Disponible', 'Ocupada', 'Reservada', 'Mantenimiento'])->default('Disponible');
            $table->string('codigo_qr', 255)->unique();
            $table->string('ubicacion', 100)->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index('numero_mesa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mesas');
    }
};
