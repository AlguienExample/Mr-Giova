<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\MateriaPrima;
use Illuminate\Support\Facades\DB;

/**
 * ProductoMateriaPrimaSeeder
 *
 * Asocia cada producto del menú con sus materias primas (recetas / Bill of Materials).
 * Define las cantidades exactas que descuenta cada unidad vendida.
 */
class ProductoMateriaPrimaSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar la tabla pivote de recetas antes de sembrar
        DB::table('producto_materia_prima')->truncate();

        // Mapeo estructurado de recetas: [Nombre Producto => [[Nombre Insumo, Cantidad Requerida], ...]]
        $recetario = [
            // ── 1. HAMBURGUESAS ──────────────────────────────────────────────────
            'Hamburguesa Clásica Sabor a Pueblo' => [
                ['Pan Brioche Artesanal', 1.0],
                ['Carne de Res Molida Premium (80/20)', 0.150],
                ['Queso Cheddar en Lonchas', 0.030],
                ['Tomate Chonto Maduro', 0.040],
                ['Cebolla Cabezona Blanca', 0.030],
                ['Lechuga Crespa Fresca', 0.025],
                ['Salsa Especial Sabor a Pueblo', 0.025],
                ['Mantequilla Pura de Vaca', 0.010],
            ],

            'Hamburguesa BBQ Sabor a Pueblo' => [
                ['Pan Brioche Artesanal', 1.0],
                ['Carne de Res Molida Premium (80/20)', 0.150],
                ['Tocino Ahumado en Tiras', 0.040],
                ['Queso Cheddar en Lonchas', 0.030],
                ['Cebolla Cabezona Blanca', 0.030],
                ['Salsa BBQ Ahumada de la Casa', 0.030],
                ['Mantequilla Pura de Vaca', 0.010],
            ],

            'Hamburguesa Doble Sabor a Pueblo' => [
                ['Pan Brioche Artesanal', 1.0],
                ['Carne de Res Molida Premium (80/20)', 0.300],
                ['Queso Cheddar en Lonchas', 0.060],
                ['Tomate Chonto Maduro', 0.040],
                ['Lechuga Crespa Fresca', 0.025],
                ['Salsa Especial Sabor a Pueblo', 0.030],
                ['Mantequilla Pura de Vaca', 0.015],
            ],

            'Hamburguesa Tex-Mex Especial' => [
                ['Pan Brioche Artesanal', 1.0],
                ['Carne de Res Molida Premium (80/20)', 0.150],
                ['Tocino Ahumado en Tiras', 0.030],
                ['Queso Cheddar en Lonchas', 0.030],
                ['Aguacate Hass Maduro', 0.050],
                ['Chile Jalapeño en Escabeche', 0.020],
                ['Salsa Especial Sabor a Pueblo', 0.020],
            ],

            // ── 2. TACOS & QUESADILLAS ───────────────────────────────────────────
            'Tacos al Pastor' => [
                ['Carne de Cerdo Adobada al Pastor', 0.180],
                ['Tortillas de Maíz Nixtamalizado', 3.0],
                ['Piña Oro Miel', 0.050],
                ['Cebolla Cabezona Blanca', 0.020],
                ['Cilantro Fresco', 0.010],
                ['Limón Tahití', 0.040],
            ],

            'Quesadilla de Birria' => [
                ['Falda de Res para Birria', 0.200],
                ['Tortillas de Harina Gigantes (30cm)', 1.0],
                ['Queso Oaxaca Artesanal', 0.100],
                ['Cebolla Cabezona Blanca', 0.020],
                ['Cilantro Fresco', 0.010],
                ['Limón Tahití', 0.040],
            ],

            'Tacos de Cochinita Pibil' => [
                ['Carne de Cerdo Achiote (Pibil)', 0.180],
                ['Tortillas de Maíz Nixtamalizado', 3.0],
                ['Cebolla Morada', 0.040],
                ['Chile Habanero Fresco', 0.005],
                ['Cilantro Fresco', 0.010],
                ['Limón Tahití', 0.040],
            ],

            'Tacos Dorados de Pollo' => [
                ['Pechuga de Pollo Deshebrada', 0.150],
                ['Tortillas de Maíz Nixtamalizado', 3.0],
                ['Aceite Vegetal para Freidora', 0.050],
                ['Crema Agria Mexicana', 0.030],
                ['Lechuga Crespa Fresca', 0.030],
                ['Tomate Chonto Maduro', 0.025],
            ],

            'Burrito Norteño de Res' => [
                ['Falda de Res para Birria', 0.150],
                ['Tortillas de Harina Gigantes (30cm)', 1.0],
                ['Frijol Negro Refrito', 0.080],
                ['Queso Oaxaca Artesanal', 0.060],
                ['Arroz Blanco Seleccionado', 0.050],
                ['Crema Agria Mexicana', 0.030],
                ['Aguacate Hass Maduro', 0.040],
            ],

            // ── 3. ACOMPAÑAMIENTOS ───────────────────────────────────────────────
            'Papas Francesas' => [
                ['Papa Especial para Freír', 0.250],
                ['Aceite Vegetal para Freidora', 0.040],
            ],

            'Papas Mexicanas' => [
                ['Papa Especial para Freír', 0.250],
                ['Aceite Vegetal para Freidora', 0.040],
                ['Salsa de Queso Cheddar Fundido', 0.060],
                ['Crema Agria Mexicana', 0.030],
                ['Tomate Chonto Maduro', 0.030],
                ['Chile Jalapeño en Escabeche', 0.020],
                ['Cilantro Fresco', 0.005],
            ],

            'Nachos Supremos Especiales' => [
                ['Totopos de Maíz Artesanales', 0.150],
                ['Carne de Res Molida Premium (80/20)', 0.100],
                ['Salsa de Queso Cheddar Fundido', 0.080],
                ['Frijol Negro Refrito', 0.060],
                ['Aguacate Hass Maduro', 0.060],
                ['Crema Agria Mexicana', 0.030],
                ['Chile Jalapeño en Escabeche', 0.025],
            ],

            // ── 4. BEBIDAS ───────────────────────────────────────────────────────
            'Agua Fresca de Horchata' => [
                ['Arroz Blanco Seleccionado', 0.040],
                ['Leche Entera Pasteurizada', 0.100],
                ['Leche Condensada', 0.030],
                ['Canela en Rama', 0.005],
                ['Azúcar Blanca Refinada', 0.020],
            ],

            'Agua Fresca de Jamaica' => [
                ['Flores de Jamaica Deshidratadas', 0.025],
                ['Azúcar Blanca Refinada', 0.030],
            ],

            'Gaseosa Coca-Cola' => [
                ['Gaseosa Coca-Cola 350ml Vidrio', 1.0],
            ],

            'Margarita Clásica de la Casa' => [
                ['Tequila Reposado 100% Agave', 0.060],
                ['Licor Triple Sec', 0.030],
                ['Limón Tahití', 0.050],
                ['Chile Tajín & Sal de Mar', 0.005],
            ],

            'Margarita de Tamarindo' => [
                ['Tequila Reposado 100% Agave', 0.060],
                ['Licor Triple Sec', 0.030],
                ['Pulpa Concentrada de Tamarindo', 0.040],
                ['Limón Tahití', 0.030],
                ['Chile Tajín & Sal de Mar', 0.005],
            ],

            // ── 5. POSTRES ───────────────────────────────────────────────────────
            'Churros Tradicionales con Cajeta' => [
                ['Harina de Trigo Fortificada', 0.100],
                ['Mantequilla Pura de Vaca', 0.020],
                ['Aceite Vegetal para Freidora', 0.040],
                ['Azúcar Blanca Refinada', 0.020],
                ['Canela en Rama', 0.005],
                ['Cajeta Tradicional de Cabra', 0.050],
            ],

            'Pastel Tres Leches' => [
                ['Harina de Trigo Fortificada', 0.060],
                ['Leche Entera Pasteurizada', 0.060],
                ['Leche Condensada', 0.060],
                ['Crema de Leche 35%', 0.060],
                ['Fresas Frescas', 0.030],
            ],
        ];

        // Cachear las materias primas y productos por nombre para evitar cientos de queries
        $productos     = Producto::all()->keyBy('nombre');
        $materiasPrimas = MateriaPrima::all()->keyBy('nombre');

        $totalRelaciones = 0;

        foreach ($recetario as $productoNombre => $insumosRequeridos) {
            $producto = $productos->get($productoNombre);
            if (!$producto) {
                // Si el nombre tiene ligeras variaciones (e.g. Churros con Cajeta vs Churros Tradicionales)
                $producto = Producto::where('nombre', 'like', "%{$productoNombre}%")->first();
            }

            if (!$producto) {
                $this->command->warn("⚠️  Producto no encontrado en catálogo: '{$productoNombre}'");
                continue;
            }

            foreach ($insumosRequeridos as [$insumoNombre, $cantRequerida]) {
                $mp = $materiasPrimas->get($insumoNombre);
                if (!$mp) {
                    $this->command->warn("⚠️  Materia prima no encontrada: '{$insumoNombre}' para '{$productoNombre}'");
                    continue;
                }

                DB::table('producto_materia_prima')->updateOrInsert(
                    [
                        'producto_id'      => $producto->id,
                        'materia_prima_id' => $mp->id,
                    ],
                    [
                        'cantidad_requerida' => $cantRequerida,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]
                );

                $totalRelaciones++;
            }
        }

        $this->command->info("✅ ProductoMateriaPrimaSeeder completado: {$totalRelaciones} ingredientes vinculados a las recetas.");
    }
}
