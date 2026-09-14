<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Role;

class SessionOverlapTest extends TestCase
{
    use RefreshDatabase;

    public function test_graceful_degradation_on_session_overlap()
    {
        // 1. Crear roles (la migración ya siembra 'Cajero': usar firstOrCreate)
        $rolAdmin = Role::firstOrCreate(['name' => 'Administrador'], ['description' => 'Admin']);
        $rolCajero = Role::firstOrCreate(['name' => 'Cajero'], ['description' => 'Caja']);

        // 2. Crear usuarios sin factory
        $admin = Usuario::create([
            'nombres' => 'Admin',
            'apellidos' => 'Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'rol_id' => $rolAdmin->id,
            'activo' => true,
        ]);
        
        $cajero = Usuario::create([
            'nombres' => 'Cajero',
            'apellidos' => 'Test',
            'email' => 'cajero@test.com',
            'password' => bcrypt('password'),
            'rol_id' => $rolCajero->id,
            'activo' => true,
        ]);

        // ========================================================
        // ESCENARIO: Pestaña 1 (Admin) inicia sesión y entra a /admin
        // ========================================================
        $this->actingAs($admin);
        
        // Verifica que el admin puede entrar a su panel
        $responseAdmin = $this->get('/admin');
        $responseAdmin->assertStatus(200); // 200 OK

        // ========================================================
        // ESCENARIO: Pestaña 2 (Mismo navegador) inicia sesión como Cajero
        // Esto muta la sesión global en el servidor al nuevo ID de usuario
        // ========================================================
        $this->actingAs($cajero);

        // Verifica que el cajero puede entrar a su panel
        $responseCajero = $this->get('/caja');
        $responseCajero->assertStatus(200); // 200 OK

        // ========================================================
        // ESCENARIO: El usuario vuelve a la Pestaña 1 y la refresca.
        // Ahora su sesión dice que es el "Cajero" pero la URL es /admin.
        // En lugar de dar un 403 Forbidden, debe redirigir a /caja.
        // ========================================================
        
        // El Cajero (sesión actual) intenta entrar a /admin (que pide rol Administrador)
        $responseRefresco = $this->get('/admin');
        
        // Aseguramos que NO devuelve 403 Forbidden
        $responseRefresco->assertStatus(302);
        
        // Aseguramos que redirige al panel que le corresponde según su rol actual (Caja)
        $responseRefresco->assertRedirect('/caja');
        
        // Aseguramos que lleva el mensaje de advertencia para el usuario
        $responseRefresco->assertSessionHas('warning', 'La sesión activa ha cambiado. Has sido redirigido a tu panel actual.');
    }
}
