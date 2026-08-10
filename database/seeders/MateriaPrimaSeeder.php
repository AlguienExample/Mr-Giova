<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MateriaPrima;

/**
 * MateriaPrimaSeeder
 * 
 * Inserta datos de prueba realistas en la tabla materia_primas.
 * Incluye ingredientes premium de un restaurante de lujo con diferentes
 * categorías, estados de stock y costos unitarios.
 */
class MateriaPrimaSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar tabla antes de sembrar para evitar duplicados
        MateriaPrima::truncate();

        $insumos = [
            // ── CARNES PREMIUM ──────────────────────────────────────────────────
            [
                'nombre'          => 'Wagyu A5 Japonés',
                'categoria'       => 'Carnes',
                'cantidad_actual' => 4.5,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 5,   // CRÍTICO intencionado
                'costo_unitario'  => 850000,
            ],
            [
                'nombre'          => 'Filete de Res Angus',
                'categoria'       => 'Carnes',
                'cantidad_actual' => 18.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 8,
                'costo_unitario'  => 95000,
            ],
            [
                'nombre'          => 'Pechuga de Pato Moulard',
                'categoria'       => 'Carnes',
                'cantidad_actual' => 6.2,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 4,
                'costo_unitario'  => 75000,
            ],
            [
                'nombre'          => 'Cordero Neozelandés',
                'categoria'       => 'Carnes',
                'cantidad_actual' => 3.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 4,   // CRÍTICO intencionado
                'costo_unitario'  => 68000,
            ],

            // ── DELICATESSEN ─────────────────────────────────────────────────────
            [
                'nombre'          => 'Trufa Negra (Tuber melanosporum)',
                'categoria'       => 'Delicatessen',
                'cantidad_actual' => 0.3,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 0.2,
                'costo_unitario'  => 4200000,
            ],
            [
                'nombre'          => 'Caviar Beluga',
                'categoria'       => 'Delicatessen',
                'cantidad_actual' => 0.08,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 0.1, // CRÍTICO intencionado
                'costo_unitario'  => 9500000,
            ],
            [
                'nombre'          => 'Foie Gras de Pato',
                'categoria'       => 'Delicatessen',
                'cantidad_actual' => 2.5,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 1.5,
                'costo_unitario'  => 320000,
            ],
            [
                'nombre'          => 'Aceite de Oliva Virgen Extra Arbequina',
                'categoria'       => 'Delicatessen',
                'cantidad_actual' => 12.0,
                'unidad_medida'   => 'litros',
                'stock_minimo'    => 5,
                'costo_unitario'  => 55000,
            ],

            // ── BODEGA EXCLUSIVA ─────────────────────────────────────────────────
            [
                'nombre'          => 'Vino Tinto Reserva Especial',
                'categoria'       => 'Bodega Exclusiva',
                'cantidad_actual' => 24.0,
                'unidad_medida'   => 'botellas',
                'stock_minimo'    => 12,
                'costo_unitario'  => 180000,
            ],
            [
                'nombre'          => 'Champagne Brut Vintage',
                'categoria'       => 'Bodega Exclusiva',
                'cantidad_actual' => 8.0,
                'unidad_medida'   => 'botellas',
                'stock_minimo'    => 10, // CRÍTICO intencionado
                'costo_unitario'  => 420000,
            ],
            [
                'nombre'          => 'Whisky Single Malt 18 años',
                'categoria'       => 'Bodega Exclusiva',
                'cantidad_actual' => 6.0,
                'unidad_medida'   => 'botellas',
                'stock_minimo'    => 3,
                'costo_unitario'  => 650000,
            ],

            // ── PESCADOS & MARISCOS ───────────────────────────────────────────────
            [
                'nombre'          => 'Salmón del Atlántico Noruego',
                'categoria'       => 'Pescados & Mariscos',
                'cantidad_actual' => 9.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 5,
                'costo_unitario'  => 52000,
            ],
            [
                'nombre'          => 'Langosta del Atlántico',
                'categoria'       => 'Pescados & Mariscos',
                'cantidad_actual' => 5.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 3,
                'costo_unitario'  => 195000,
            ],
            [
                'nombre'          => 'Pulpo de Roca',
                'categoria'       => 'Pescados & Mariscos',
                'cantidad_actual' => 7.5,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 4,
                'costo_unitario'  => 38000,
            ],

            // ── VERDURAS & HIERBAS ───────────────────────────────────────────────
            [
                'nombre'          => 'Espárragos Blancos de Navarra',
                'categoria'       => 'Verduras',
                'cantidad_actual' => 3.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 2,
                'costo_unitario'  => 28000,
            ],
            [
                'nombre'          => 'Rúcula Baby',
                'categoria'       => 'Verduras',
                'cantidad_actual' => 1.5,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 1,
                'costo_unitario'  => 12000,
            ],
            [
                'nombre'          => 'Microgreens Mix Premium',
                'categoria'       => 'Verduras',
                'cantidad_actual' => 0.8,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 1, // CRÍTICO intencionado
                'costo_unitario'  => 45000,
            ],

            // ── LÁCTEOS & QUESOS ─────────────────────────────────────────────────
            [
                'nombre'          => 'Parmigiano Reggiano 36 meses',
                'categoria'       => 'Lácteos',
                'cantidad_actual' => 4.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 2,
                'costo_unitario'  => 95000,
            ],
            [
                'nombre'          => 'Mantequilla Clarificada (Ghee)',
                'categoria'       => 'Lácteos',
                'cantidad_actual' => 6.0,
                'unidad_medida'   => 'kg',
                'stock_minimo'    => 3,
                'costo_unitario'  => 22000,
            ],
            [
                'nombre'          => 'Crema de Leche Entera 35%',
                'categoria'       => 'Lácteos',
                'cantidad_actual' => 15.0,
                'unidad_medida'   => 'litros',
                'stock_minimo'    => 5,
                'costo_unitario'  => 8500,
            ],
        ];

        foreach ($insumos as $insumo) {
            MateriaPrima::create($insumo);
        }

        $this->command->info('✅ MateriaPrimaSeeder completado: ' . count($insumos) . ' insumos insertados.');
    }
}
