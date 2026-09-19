<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Factura;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Role;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Terminal de Caja: estilos completos de pago/historial, bugs JS corregidos
 * y flujo real de cobro (factura, cambio, liberación de mesa).
 */
class CajaTerminalTest extends TestCase
{
    use RefreshDatabase;

    private $cajero;
    private $mesa;
    private $pedido;

    protected function setUp(): void
    {
        parent::setUp();

        $rolCajero = Role::firstOrCreate(['name' => 'Cajero'], ['description' => 'Caja']);
        Role::firstOrCreate(['name' => 'Administrador'], ['description' => 'Admin']);
        Role::firstOrCreate(['name' => 'Cocinero'], ['description' => 'Cocina']);
        $rolCliente = Role::firstOrCreate(['name' => 'Cliente'], ['description' => 'Cliente']);

        $this->cajero = Usuario::create([
            'nombres' => 'Caja',
            'apellidos' => 'Test',
            'email' => 'cajero@test.com',
            'password' => Hash::make('password'),
            'rol_id' => $rolCajero->id,
            'activo' => true,
        ]);
        Empleado::create([
            'usuario_id' => $this->cajero->id,
            'cargo' => 'Cajero',
            'fecha_contratacion' => now(),
            'sueldo' => 1000,
        ]);

        $this->mesa = Mesa::create([
            'numero_mesa' => 5,
            'capacidad' => 4,
            'estado' => 'Ocupada',
            'codigo_qr' => 'qr_caja_test_5',
            'timer_inicio' => now()->subMinutes(30),
        ]);

        $clienteUser = Usuario::create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'email' => 'juan@test.com',
            'password' => Hash::make('password'),
            'rol_id' => $rolCliente->id,
            'activo' => true,
        ]);
        $cliente = Cliente::create(['usuario_id' => $clienteUser->id]);

        $this->pedido = Pedido::create([
            'cliente_id' => $cliente->id,
            'mesa_id' => $this->mesa->id,
            'estado' => 'Listo',
            'tipo_pedido' => 'Presencial',
            'total' => 50000,
        ]);
    }

    private function base(string $relative): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $relative;
    }

    private function read(string $relative): string
    {
        $path = $this->base($relative);
        $this->assertFileExists($path, "Falta el archivo: {$relative}");
        return (string) file_get_contents($path);
    }

    // ── Estáticos: CSS ──────────────────────────────────────────────

    public function test_caja_css_cubre_toda_la_seccion_de_pago_e_historial()
    {
        $css = $this->read('public/css/pages/caja.css');

        foreach ([
            '.metodo-pago-grid', '.metodo-btn',
            '.efectivo-box', '.efectivo-input-wrap', '#input-recibido',
            '.quick-amounts', '.quick-btn',
            '.cambio-row', '.aviso-falta',
            '.btn-cobrar', '.btn-sec', '.pago-section',
            '.historial-resumen', '.historial-table', '.metodo-chip',
            '.item-name', '.item-qty', '.empty-title',
        ] as $selector) {
            $this->assertStringContainsString($selector, $css, "caja.css: falta estilo para {$selector}");
        }

        // Responsive: la grilla 1fr+380px debe colapsar en tabletas/móvil
        $this->assertStringContainsString('@media', $css, 'caja.css: falta responsive para la terminal');

        // Impresión: solo el ticket o solo el cierre (el botón promete imprimir)
        $this->assertStringContainsString('@media print', $css, 'caja.css: falta hoja de impresión');
        $this->assertStringContainsString('print-ticket', $css);
        $this->assertStringContainsString('print-cierre', $css);
    }

    // ── Estáticos: JS ───────────────────────────────────────────────

    public function test_caja_js_sin_bugs_conocidos()
    {
        $js = $this->read('public/js/pages/caja.js');

        // El botón recupera su etiqueta original tras un error (no "PROCESAR PAGO")
        $this->assertStringContainsString('BTN_COBRAR_HTML', $js);
        $this->assertStringContainsString('COBRAR E IMPRIMIR TICKET', $js);
        $this->assertStringNotContainsString('PROCESAR PAGO', $js);

        // "Efectivo" del resumen = total cobrado en efectivo (no el recibido)
        $this->assertStringNotContainsString(
            '.reduce((s, p) => s + (p.monto_recibido ?? 0), 0)',
            $js,
            'El resumen de efectivo debe sumar total_final, no monto_recibido'
        );

        // Botón "Exacto" se marca como activo y el historial cierra con Escape/fondo
        $this->assertStringContainsString('qb-exacto', $js);
        $this->assertStringContainsString("'Escape'", $js);
        $this->assertStringContainsString('e.target === modal', $js);

        // Propina con tope 0–100 (una negativa sería un descuento encubierto)
        $this->assertStringContainsString('Math.min(100', $js);

        // Zonas funcionales con filtro persistente
        $this->assertStringContainsString('filtrarZona', $js);
        $this->assertStringContainsString('aplicarFiltrosMesas', $js);
        $this->assertStringContainsString('caja-solo-jornada', $js);

        // Montos rápidos según el total + buscador e impresión de cierre
        $this->assertStringContainsString('renderQuickAmounts', $js);
        $this->assertStringContainsString('filtrarHistorial', $js);
        $this->assertStringContainsString('imprimirCierre', $js);
        $this->assertStringContainsString('print-ticket', $js);
        $this->assertStringContainsString('afterprint', $js);
    }

    public function test_caja_blade_tiene_controles_de_pago_e_historial()
    {
        $blade = $this->read('resources/views/caja.blade.php');

        foreach (['quick-amounts', 'modal-historial', 'historial-rows', 'historialSearch', 'input-recibido', 'btn-procesar', 'txt-cambio', 'btn-refresh-caja', 'data-zona', 'filtrarZona', 'imprimirCierre'] as $id) {
            $this->assertStringContainsString($id, $blade, "caja.blade.php: falta {$id}");
        }
    }

    public function test_cuenta_del_ticket_es_amplia_y_legible()
    {
        $css = $this->read('public/css/pages/caja.css');

        // Panel de la cuenta con ancho suficiente (antes: 380px)
        $this->assertStringContainsString('410px', $css, 'El panel de la cuenta debe ser más ancho');
        $this->assertStringContainsString('min-height: 220px', $css, 'El cuerpo del ticket necesita altura mínima');

        // Tipografía legible: ítems y total destacados
        $this->assertStringContainsString('font-size: 14.5px', $css, 'Nombre del ítem muy pequeño');
        $this->assertStringContainsString('font-size: 21px', $css, 'Total a pagar muy pequeño');
        $this->assertStringContainsString('#pedido-titulo', $css, 'Falta realce del título del pedido');
    }

    // ── Flujo real de cobro ─────────────────────────────────────────

    public function test_pago_efectivo_exitoso_crea_factura_y_libera_mesa()
    {
        $response = $this->actingAs($this->cajero)->postJson('/caja/pagos', [
            'pedido_id' => $this->pedido->id,
            'metodo_pago' => 'Efectivo',
            'propina' => 5000,
            'monto_recibido' => 60000,
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertEquals(5000, $response->json('cambio'));

        $factura = Factura::where('pedido_id', $this->pedido->id)->first();
        $this->assertNotNull($factura, 'Debe registrarse la factura');
        $this->assertEquals(55000, (float) $factura->total_final);
        $this->assertEquals(5000, (float) $factura->propina);
        $this->assertEquals(5000, (float) $factura->cambio);

        $this->assertEquals('Entregado', $this->pedido->fresh()->estado);
        $this->assertEquals('Disponible', $this->mesa->fresh()->estado);
    }

    public function test_pago_efectivo_insuficiente_es_rechazado()
    {
        $response = $this->actingAs($this->cajero)->postJson('/caja/pagos', [
            'pedido_id' => $this->pedido->id,
            'metodo_pago' => 'Efectivo',
            'monto_recibido' => 10000,
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertNull(Factura::where('pedido_id', $this->pedido->id)->first());
        $this->assertEquals('Ocupada', $this->mesa->fresh()->estado);
    }

    public function test_doble_pago_del_mismo_pedido_es_rechazado()
    {
        $this->actingAs($this->cajero)->postJson('/caja/pagos', [
            'pedido_id' => $this->pedido->id,
            'metodo_pago' => 'Tarjeta',
        ])->assertStatus(200);

        $this->actingAs($this->cajero)->postJson('/caja/pagos', [
            'pedido_id' => $this->pedido->id,
            'metodo_pago' => 'Tarjeta',
        ])->assertStatus(422)->assertJson(['success' => false]);

        $this->assertEquals(1, Factura::where('pedido_id', $this->pedido->id)->count());
    }

    public function test_historial_lista_pagos_y_mesa_devuelve_pedido_activo()
    {
        $this->actingAs($this->cajero)->postJson('/caja/pagos', [
            'pedido_id' => $this->pedido->id,
            'metodo_pago' => 'Efectivo',
            'monto_recibido' => 50000,
        ])->assertStatus(200);

        $this->actingAs($this->cajero)->getJson('/caja/pagos')
            ->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonFragment(['pedido_id' => $this->pedido->id]);

        // Tras el pago la mesa ya no tiene pedido activo
        $this->actingAs($this->cajero)->getJson("/caja/mesa/{$this->mesa->id}/pedido")
            ->assertStatus(200)
            ->assertJson(['success' => false]);
    }
}
