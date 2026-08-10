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
        // Añadir identificación a empleados
        Schema::table('empleados', function (Blueprint $table) {
            if (!Schema::hasColumn('empleados', 'identificacion')) {
                $table->string('identificacion', 20)->nullable()->after('usuario_id');
            }
        });

        // Añadir empleado_id a mesas para asignación directa
        Schema::table('mesas', function (Blueprint $table) {
            if (!Schema::hasColumn('mesas', 'empleado_id')) {
                $table->foreignId('empleado_id')->nullable()->constrained('empleados')->onDelete('set null');
            }
            if (!Schema::hasColumn('mesas', 'zona')) {
                $table->string('zona', 50)->default('Principal')->after('ubicacion');
            }
            if (!Schema::hasColumn('mesas', 'timer_inicio')) {
                $table->timestamp('timer_inicio')->nullable()->after('zona');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn('identificacion');
        });

        Schema::table('mesas', function (Blueprint $table) {
            $table->dropForeign(['empleado_id']);
            $table->dropColumn(['empleado_id', 'zona', 'timer_inicio']);
        });
    }
};
