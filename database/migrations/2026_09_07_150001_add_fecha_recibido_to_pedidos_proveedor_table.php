<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega fecha_recibido a pedidos_proveedor.
     * El campo `estado` ya existe como string('estado')->default('Pendiente').
     * Estados válidos del flujo: 'Pendiente' → 'Enviado' → 'Recibido' | 'Cancelado'.
     */
    public function up(): void
    {
        Schema::table('pedidos_proveedor', function (Blueprint $table) {
            $table->timestamp('fecha_recibido')->nullable()->after('notas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_proveedor', function (Blueprint $table) {
            $table->dropColumn('fecha_recibido');
        });
    }
};
