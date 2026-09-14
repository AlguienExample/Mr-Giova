<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Las coordenadas del plano van en columnas dedicadas para no
     * sobrescribir `ubicacion` (texto legible: Terraza, Salón...).
     */
    public function up(): void
    {
        Schema::table('mesas', function (Blueprint $table) {
            $table->decimal('pos_x', 5, 2)->nullable()->after('ubicacion');
            $table->decimal('pos_y', 5, 2)->nullable()->after('pos_x');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mesas', function (Blueprint $table) {
            $table->dropColumn(['pos_x', 'pos_y']);
        });
    }
};