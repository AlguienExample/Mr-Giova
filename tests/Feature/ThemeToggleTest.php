<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Verifica que el cambio Oscuro <-> Claro sea INSTANTANEO (sin refresh manual)
 * en todos los paneles y que el modal Historial > Ver Ticket sea legible
 * en modo oscuro.
 */
class ThemeToggleTest extends TestCase
{
    private string $base;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base = dirname(__DIR__, 2);
    }

    private function read(string $relative): string
    {
        $path = $this->base . DIRECTORY_SEPARATOR . $relative;
        $this->assertFileExists($path, "Falta el archivo: {$relative}");
        return (string) file_get_contents($path);
    }

    /** @test */
    public function theme_toggle_js_cambia_al_instante_sin_recargar(): void
    {
        $js = $this->read('public/js/theme-toggle.js');

        // API global del interruptor
        $this->assertStringContainsString('window.toggleTheme', $js);
        $this->assertStringContainsString('sabor-theme', $js);
        $this->assertStringContainsString('light-mode', $js);

        // Cambio instantáneo: usa classList.toggle con 2 args y NUNCA recarga
        $this->assertStringContainsString("classList.toggle('light-mode'", $js);
        $this->assertStringNotContainsString('location.reload', $js);
        $this->assertStringNotContainsString('location.href', $js);

        // Notifica a gráficos/contenido dinámico para repintar sin refresh
        $this->assertStringContainsString('sabor-theme-change', $js);
        $this->assertStringContainsString('CustomEvent', $js);

        // Sincroniza entre pestañas y botones inyectados tarde
        $this->assertStringContainsString("addEventListener('storage'", $js);
        $this->assertStringContainsString('theme-toggle-btn', $js);
    }

    /** @test */
    public function todos_los_paneles_tienen_boton_css_y_script_de_tema(): void
    {
        $paneles = [
            'resources/views/admin.blade.php',
            'resources/views/caja.blade.php',
            'resources/views/cocina.blade.php',
            'resources/views/menu.blade.php',
            'resources/views/login.blade.php',
        ];

        foreach ($paneles as $panel) {
            $html = $this->read($panel);

            // Botón del interruptor
            $this->assertStringContainsString(
                'theme-toggle-btn',
                $html,
                "{$panel}: falta el botón .theme-toggle-btn"
            );
            $this->assertStringContainsString(
                'toggleTheme()',
                $html,
                "{$panel}: el botón no llama a toggleTheme()"
            );

            // Hoja de modo claro
            $this->assertStringContainsString(
                'theme-light.css',
                $html,
                "{$panel}: falta css/theme-light.css"
            );

            // Script del interruptor (el admin NO lo tenía: el botón no hacía nada)
            $this->assertStringContainsString(
                'js/theme-toggle.js',
                $html,
                "{$panel}: falta js/theme-toggle.js (el cambio no sería instantáneo)"
            );

            // Snippet temprano: marca <html> al instante, sin esperar refresh del usuario
            $this->assertStringContainsString(
                'document.documentElement',
                $html,
                "{$panel}: falta el snippet temprano anti-FOUC"
            );
            $this->assertStringContainsString(
                'sabor-theme',
                $html,
                "{$panel}: el snippet no lee localStorage sabor-theme"
            );
        }
    }

    /** @test */
    public function admin_carga_theme_toggle_antes_de_admin_js(): void
    {
        $html = $this->read('resources/views/admin.blade.php');
        $posTheme = strpos($html, 'js/theme-toggle.js');
        $posAdmin = strpos($html, 'js/pages/admin.js');

        $this->assertNotFalse($posTheme, 'admin.blade.php: falta theme-toggle.js');
        $this->assertNotFalse($posAdmin, 'admin.blade.php: falta admin.js');
        $this->assertLessThan(
            $posAdmin,
            $posTheme,
            'theme-toggle.js debe cargarse ANTES que admin.js para aplicar el tema al instante'
        );
    }

    /** @test */
    public function ver_ticket_es_legible_en_modo_oscuro(): void
    {
        $blade = $this->read('resources/views/admin.blade.php');

        // El modal usa clases adaptativas, no fondo claro fijo con texto claro
        $this->assertStringContainsString('modalTicket', $blade);
        $this->assertStringContainsString('ticket-paper', $blade);
        $this->assertStringNotContainsString(
            'background:#fffdf9',
            $blade,
            'El ticket no debe fijar fondo claro con texto heredado claro (invisible en oscuro)'
        );

        // El JS inyecta líneas con clases (no divs heredando blanco sobre crema)
        $adminJs = $this->read('public/js/pages/admin.js');
        $this->assertStringContainsString('ticket-line', $adminJs);
        $this->assertStringContainsString('ticket-line-note', $adminJs);

        // CSS base (oscuro) + override (claro) con contraste explícito
        $adminCss = $this->read('public/css/admin.css');
        $this->assertStringContainsString('.ticket-paper', $adminCss);
        $this->assertStringContainsString('#modalTicket .ticket-line-sub', $adminCss);

        $lightCss = $this->read('public/css/theme-light.css');
        $this->assertStringContainsString('#modalTicket .modal-content.ticket-paper', $lightCss);
        $this->assertStringContainsString('#fffdf9', $lightCss, 'El modo claro debe conservar el papel crema');
    }

    /** @test */
    public function modo_claro_cubre_caja_pos_y_html_temprano(): void
    {
        $lightCss = $this->read('public/css/theme-light.css');

        // El <html> ya trae las variables antes del primer pintado (sin refresh)
        $this->assertStringContainsString('html.light-mode', $lightCss);

        // La terminal de caja usa --pos-* fijas oscuras: deben sobrescribirse en claro
        $this->assertStringContainsString('--pos-bg', $lightCss);
        $this->assertStringContainsString('--pos-card', $lightCss);

        // Transición suave = el fondo "se refresca solo" visualmente
        $this->assertStringContainsString('transition', $lightCss);
    }

    /** @test */
    public function grafico_admin_se_repinta_al_cambiar_tema_sin_recargar(): void
    {
        $adminJs = $this->read('public/js/pages/admin.js');
        $this->assertStringContainsString('sabor-theme-change', $adminJs);
        $this->assertStringContainsString('chartInstance.update()', $adminJs);
    }
}
