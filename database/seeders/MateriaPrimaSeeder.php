<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MateriaPrima;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MateriaPrimaSeeder
 * 
 * Inserta insumos y materias primas reales y coherentes para el restaurante "Sabor a Pueblo".
 * Clasificados en las categorías del sistema:
 * - Carnes
 * - Lácteos
 * - Verduras
 * - Delicatessen (Panadería, Tortillas, Abarrotes y Salsas de la casa)
 * - Bodega Exclusiva (Coctelería, Licores, Granos y Especias)
 */
class MateriaPrimaSeeder extends Seeder
{
    public function run(): void
    {
        // Desactivar temporalmente revisión de foreign keys para truncar limpiamente
        Schema::disableForeignKeyConstraints();
        DB::table('producto_materia_prima')->truncate();
        DB::table('detalle_pedido_proveedor')->truncate();
        MateriaPrima::truncate();
        Schema::enableForeignKeyConstraints();

        $insumos = [
            // ── 1. CARNES ────────────────────────────────────────────────────────
            [
                'nombre'          => 'Carne de Res Molida Premium (80/20)',
                'categoria'       => 'Carnes',
                'cantidad_actual' => 8.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 10.0,  // CRÍTICO intencionado
                'costo_unitario'  => 28000,
            ],
            [
                'nombre'          => 'Carne de Cerdo Adobada al Pastor',
                'categoria'       => 'Carnes',
                'cantidad_actual' => 12.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 6.0,
                'costo_unitario'  => 22000,
            ],
            [
                'nombre'          => 'Carne de Cerdo Achiote (Pibil)',
                'categoria'       => 'Carnes',
                'cantidad_actual' => 10.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 5.0,
                'costo_unitario'  => 23000,
            ],
            [
                'nombre'          => 'Falda de Res para Birria',
                'categoria'       => 'Carnes',
                'cantidad_actual' => 14.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 7.0,
                'costo_unitario'  => 32000,
            ],
            [
                'nombre'          => 'Pechuga de Pollo Deshebrada',
                'categoria'       => 'Carnes',
                'cantidad_actual' => 9.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 5.0,
                'costo_unitario'  => 18000,
            ],
            [
                'nombre'          => 'Tocino Ahumado en Tiras',
                'categoria'       => 'Carnes',
                'cantidad_actual' => 5.5,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 3.0,
                'costo_unitario'  => 34000,
            ],

            // ── 2. LÁCTEOS ───────────────────────────────────────────────────────
            [
                'nombre'          => 'Queso Cheddar en Lonchas',
                'categoria'       => 'Lácteos',
                'cantidad_actual' => 6.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 3.5,
                'costo_unitario'  => 32000,
            ],
            [
                'nombre'          => 'Salsa de Queso Cheddar Fundido',
                'categoria'       => 'Lácteos',
                'cantidad_actual' => 7.5,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 4.0,
                'costo_unitario'  => 24000,
            ],
            [
                'nombre'          => 'Queso Oaxaca Artesanal',
                'categoria'       => 'Lácteos',
                'cantidad_actual' => 2.2,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 4.0,  // CRÍTICO intencionado
                'costo_unitario'  => 36000,
            ],
            [
                'nombre'          => 'Crema Agria Mexicana',
                'categoria'       => 'Lácteos',
                'cantidad_actual' => 8.0,
                'unidad_medida'   => 'litros',
                'stock_minimo'    => 4.0,
                'costo_unitario'  => 16000,
            ],
            [
                'nombre'          => 'Leche Entera Pasteurizada',
                'categoria'       => 'Lácteos',
                'cantidad_actual' => 20.0,
                'unidad_medida'   => 'litros',
                'stock_minimo'    => 8.0,
                'costo_unitario'  => 4200,
            ],
            [
                'nombre'          => 'Leche Condensada',
                'categoria'       => 'Lácteos',
                'cantidad_actual' => 10.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 4.0,
                'costo_unitario'  => 14000,
            ],
            [
                'nombre'          => 'Crema de Leche 35%',
                'categoria'       => 'Lácteos',
                'cantidad_actual' => 8.0,
                'unidad_medida'   => 'litros',
                'stock_minimo'    => 3.0,
                'costo_unitario'  => 18000,
            ],
            [
                'nombre'          => 'Mantequilla Pura de Vaca',
                'categoria'       => 'Lácteos',
                'cantidad_actual' => 4.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 2.0,
                'costo_unitario'  => 26000,
            ],

            // ── 3. VERDURAS & FRESCOS ─────────────────────────────────────────────
            [
                'nombre'          => 'Papa Especial para Freír',
                'categoria'       => 'Verduras',
                'cantidad_actual' => 35.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 15.0,
                'costo_unitario'  => 4500,
            ],
            [
                'nombre'          => 'Aguacate Hass Maduro',
                'categoria'       => 'Verduras',
                'cantidad_actual' => 2.5,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 5.0,  // CRÍTICO intencionado
                'costo_unitario'  => 11000,
            ],
            [
                'nombre'          => 'Tomate Chonto Maduro',
                'categoria'       => 'Verduras',
                'cantidad_actual' => 12.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 6.0,
                'costo_unitario'  => 4800,
            ],
            [
                'nombre'          => 'Cebolla Cabezona Blanca',
                'categoria'       => 'Verduras',
                'cantidad_actual' => 10.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 5.0,
                'costo_unitario'  => 3500,
            ],
            [
                'nombre'          => 'Cebolla Morada',
                'categoria'       => 'Verduras',
                'cantidad_actual' => 8.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 4.0,
                'costo_unitario'  => 4200,
            ],
            [
                'nombre'          => 'Lechuga Crespa Fresca',
                'categoria'       => 'Verduras',
                'cantidad_actual' => 5.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 2.5,
                'costo_unitario'  => 6000,
            ],
            [
                'nombre'          => 'Cilantro Fresco',
                'categoria'       => 'Verduras',
                'cantidad_actual' => 1.8,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 1.0,
                'costo_unitario'  => 8000,
            ],
            [
                'nombre'          => 'Piña Oro Miel',
                'categoria'       => 'Verduras',
                'cantidad_actual' => 6.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 3.0,
                'costo_unitario'  => 5000,
            ],
            [
                'nombre'          => 'Chile Jalapeño en Escabeche',
                'categoria'       => 'Verduras',
                'cantidad_actual' => 4.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 2.0,
                'costo_unitario'  => 15000,
            ],
            [
                'nombre'          => 'Chile Habanero Fresco',
                'categoria'       => 'Verduras',
                'cantidad_actual' => 0.8,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 0.5,
                'costo_unitario'  => 22000,
            ],
            [
                'nombre'          => 'Limón Tahití',
                'categoria'       => 'Verduras',
                'cantidad_actual' => 15.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 5.0,
                'costo_unitario'  => 5500,
            ],
            [
                'nombre'          => 'Fresas Frescas',
                'categoria'       => 'Verduras',
                'cantidad_actual' => 3.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 1.5,
                'costo_unitario'  => 12000,
            ],

            // ── 4. DELICATESSEN (Panadería, Tortillas, Abarrotes & Salsas) ─────────
            [
                'nombre'          => 'Pan Brioche Artesanal',
                'categoria'       => 'Delicatessen',
                'cantidad_actual' => 45.0,
                'unidad_medida'   => 'unidades',
                'stock_minimo'    => 20.0,
                'costo_unitario'  => 2200,
            ],
            [
                'nombre'          => 'Tortillas de Maíz Nixtamalizado',
                'categoria'       => 'Delicatessen',
                'cantidad_actual' => 150.0,
                'unidad_medida'   => 'unidades',
                'stock_minimo'    => 50.0,
                'costo_unitario'  => 350,
            ],
            [
                'nombre'          => 'Tortillas de Harina Gigantes (30cm)',
                'categoria'       => 'Delicatessen',
                'cantidad_actual' => 45.0,
                'unidad_medida'   => 'unidades',
                'stock_minimo'    => 20.0,
                'costo_unitario'  => 1200,
            ],
            [
                'nombre'          => 'Totopos de Maíz Artesanales',
                'categoria'       => 'Delicatessen',
                'cantidad_actual' => 12.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 5.0,
                'costo_unitario'  => 16000,
            ],
            [
                'nombre'          => 'Frijol Negro Refrito',
                'categoria'       => 'Delicatessen',
                'cantidad_actual' => 8.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 4.0,
                'costo_unitario'  => 12000,
            ],
            [
                'nombre'          => 'Aceite Vegetal para Freidora',
                'categoria'       => 'Delicatessen',
                'cantidad_actual' => 25.0,
                'unidad_medida'   => 'litros',
                'stock_minimo'    => 10.0,
                'costo_unitario'  => 9500,
            ],
            [
                'nombre'          => 'Harina de Trigo Fortificada',
                'categoria'       => 'Delicatessen',
                'cantidad_actual' => 15.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 5.0,
                'costo_unitario'  => 4000,
            ],
            [
                'nombre'          => 'Cajeta Tradicional de Cabra',
                'categoria'       => 'Delicatessen',
                'cantidad_actual' => 5.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 2.5,
                'costo_unitario'  => 28000,
            ],
            [
                'nombre'          => 'Salsa BBQ Ahumada de la Casa',
                'categoria'       => 'Delicatessen',
                'cantidad_actual' => 6.0,
                'unidad_medida'   => 'litros',
                'stock_minimo'    => 3.0,
                'costo_unitario'  => 16000,
            ],
            [
                'nombre'          => 'Salsa Especial Sabor a Pueblo',
                'categoria'       => 'Delicatessen',
                'cantidad_actual' => 7.0,
                'unidad_medida'   => 'litros',
                'stock_minimo'    => 3.5,
                'costo_unitario'  => 18000,
            ],
            [
                'nombre'          => 'Pasta de Achiote Tradicional',
                'categoria'       => 'Delicatessen',
                'cantidad_actual' => 2.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 1.0,
                'costo_unitario'  => 24000,
            ],

            // ── 5. BODEGA EXCLUSIVA (Bebidas, Licores & Granos) ───────────────────
            [
                'nombre'          => 'Flores de Jamaica Deshidratadas',
                'categoria'       => 'Bodega Exclusiva',
                'cantidad_actual' => 1.2,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 2.0,  // CRÍTICO intencionado
                'costo_unitario'  => 38000,
            ],
            [
                'nombre'          => 'Tequila Reposado 100% Agave',
                'categoria'       => 'Bodega Exclusiva',
                'cantidad_actual' => 2.0,
                'unidad_medida'   => 'botellas',
                'stock_minimo'    => 5.0,  // CRÍTICO intencionado
                'costo_unitario'  => 115000,
            ],
            [
                'nombre'          => 'Licor Triple Sec',
                'categoria'       => 'Bodega Exclusiva',
                'cantidad_actual' => 4.0,
                'unidad_medida'   => 'botellas',
                'stock_minimo'    => 2.0,
                'costo_unitario'  => 65000,
            ],
            [
                'nombre'          => 'Pulpa Concentrada de Tamarindo',
                'categoria'       => 'Bodega Exclusiva',
                'cantidad_actual' => 4.5,
                'unidad_medida'   => 'litros',
                'stock_minimo'    => 2.0,
                'costo_unitario'  => 19000,
            ],
            [
                'nombre'          => 'Chile Tajín & Sal de Mar',
                'categoria'       => 'Bodega Exclusiva',
                'cantidad_actual' => 2.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 1.0,
                'costo_unitario'  => 25000,
            ],
            [
                'nombre'          => 'Arroz Blanco Seleccionado',
                'categoria'       => 'Bodega Exclusiva',
                'cantidad_actual' => 18.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 6.0,
                'costo_unitario'  => 4800,
            ],
            [
                'nombre'          => 'Canela en Rama',
                'categoria'       => 'Bodega Exclusiva',
                'cantidad_actual' => 1.5,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 0.8,
                'costo_unitario'  => 45000,
            ],
            [
                'nombre'          => 'Azúcar Blanca Refinada',
                'categoria'       => 'Bodega Exclusiva',
                'cantidad_actual' => 25.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 10.0,
                'costo_unitario'  => 4500,
            ],
            [
                'nombre'          => 'Gaseosa Coca-Cola 350ml Vidrio',
                'categoria'       => 'Bodega Exclusiva',
                'cantidad_actual' => 48.0,
                'unidad_medida'   => 'unidades',
                'stock_minimo'    => 24.0,
                'costo_unitario'  => 2800,
            ],
        ];

        foreach ($insumos as $insumo) {
            MateriaPrima::create($insumo);
        }

        $this->command->info('✅ MateriaPrimaSeeder completado: ' . count($insumos) . ' materias primas creadas exitosamente.');
    }
}
