<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('roles')->updateOrInsert(
            ['name' => 'Cajero'],
            [
                'name'        => 'Cajero',
                'description' => 'Maneja la caja, facturación e impresión de cuentas',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')->where('name', 'Cajero')->delete();
    }
};