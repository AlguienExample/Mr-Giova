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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->foreignId('rol_id')->constrained('roles')->onDelete('restrict');
            $table->boolean('activo')->default(true);
            $table->rememberToken();
            $table->timestamps();

            // Índices
            $table->index('email');
            $table->index('rol_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
