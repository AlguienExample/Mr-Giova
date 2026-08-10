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
        Schema::create('auditoria_reservas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reserva_id')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('accion'); // creada, editada, cancelada, eliminada
            $table->json('detalles')->nullable();
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
        });

        Schema::create('notificaciones_clientes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->string('cliente_nombre');
            $table->string('tipo_notificacion'); // SMS, Email
            $table->string('canal');
            $table->text('mensaje');
            $table->string('estado')->default('Enviada');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones_clientes');
        Schema::dropIfExists('auditoria_reservas');
    }
};
