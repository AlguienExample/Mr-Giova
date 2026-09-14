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
        Schema::table('factura', function (Blueprint $table) {
            $table->foreignId('usuario_id')->nullable()->after('id')->constrained('usuarios')->nullOnDelete();
            $table->decimal('monto_recibido', 10, 2)->nullable()->after('total_final');
            $table->decimal('cambio', 10, 2)->nullable()->after('monto_recibido');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factura', function (Blueprint $table) {
            $table->dropConstrainedForeignId('usuario_id');
            $table->dropColumn(['monto_recibido', 'cambio']);
        });
    }
};