<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\MateriaPrima;

/**
 * ProductoMateriaPrimaSeeder
 *
 * Asocia productos del menú con sus materias primas (recetas / BOM).
 * Ejecutar DESPUÉS de ProductoSeeder (vía DatabaseSeeder) y MateriaPrimaSeeder.
 *
 * Nota: usa firstWhere() sobre nombre para no depender de IDs hardcodeados.
 */
class ProductoMateriaPrimaSeeder extends Seeder
{
    public function run(): void
    {
        // Recuperar productos por nombre
        $hambClasica  = Producto::where('nombre', 'Hamburguesa Clásica Sabor a Pueblo')->first();
        $hambBBQ      = Producto::where('nombre', 'Hamburguesa BBQ Sabor a Pueblo')->first();
        $hambDoble    = Producto::where('nombre', 'Hamburguesa Doble Sabor a Pueblo')->first();
        $tacosP       = Producto::where('nombre', 'Tacos al Pastor')->first();
        $birria       = Producto::where('nombre', 'Quesadilla de Birria')->first();
        $cochinita    = Producto::where('nombre', 'Tacos de Cochinita Pibil')->first();
        $treLeches    = Producto::where('nombre', 'Pastel Tres Leches')->first();
        $churros      = Producto::where('nombre', 'Churros con Cajeta')->first();

        // Recuperar materias primas por nombre
        $fileteRes    = MateriaPrima::where('nombre', 'Filete de Res Angus')->first();
        $pechugaPato  = MateriaPrima::where('nombre', 'Pechuga de Pato Moulard')->first();
        $salmón       = MateriaPrima::where('nombre', 'Salmón del Atlántico Noruego')->first();
        $espárragos   = MateriaPrima::where('nombre', 'Espárragos Blancos de Navarra')->first();
        $rúcula       = MateriaPrima::where('nombre', 'Rúcula Baby')->first();
        $microgreens  = MateriaPrima::where('nombre', 'Microgreens Mix Premium')->first();
        $parmesano    = MateriaPrima::where('nombre', 'Parmigiano Reggiano 36 meses')->first();
        $mantequilla  = MateriaPrima::where('nombre', 'Mantequilla Clarificada (Ghee)')->first();
        $cremaLeche   = MateriaPrima::where('nombre', 'Crema de Leche Entera 35%')->first();
        $aceite       = MateriaPrima::where('nombre', 'Aceite de Oliva Virgen Extra Arbequina')->first();
        $foie         = MateriaPrima::where('nombre', 'Foie Gras de Pato')->first();

        $recetas = [];

        // ── Hamburguesa Clásica → Filete de Res Angus (0.15 kg), Mantequilla (0.02 kg)
        if ($hambClasica && $fileteRes)
            $recetas[] = [$hambClasica->id, $fileteRes->id, 0.150];
        if ($hambClasica && $mantequilla)
            $recetas[] = [$hambClasica->id, $mantequilla->id, 0.020];

        // ── Hamburguesa BBQ → Filete de Res Angus (0.15 kg), Aceite de Oliva (0.01 L)
        if ($hambBBQ && $fileteRes)
            $recetas[] = [$hambBBQ->id, $fileteRes->id, 0.150];
        if ($hambBBQ && $aceite)
            $recetas[] = [$hambBBQ->id, $aceite->id, 0.010];

        // ── Hamburguesa Doble → Filete de Res Angus (0.30 kg), Mantequilla (0.03 kg)
        if ($hambDoble && $fileteRes)
            $recetas[] = [$hambDoble->id, $fileteRes->id, 0.300];
        if ($hambDoble && $mantequilla)
            $recetas[] = [$hambDoble->id, $mantequilla->id, 0.030];

        // ── Tacos al Pastor → Filete de Res Angus como base de carne (0.12 kg), Aceite (0.005 L)
        if ($tacosP && $fileteRes)
            $recetas[] = [$tacosP->id, $fileteRes->id, 0.120];
        if ($tacosP && $aceite)
            $recetas[] = [$tacosP->id, $aceite->id, 0.005];

        // ── Quesadilla de Birria → Filete de Res Angus (0.20 kg), Crema de Leche (0.05 L)
        if ($birria && $fileteRes)
            $recetas[] = [$birria->id, $fileteRes->id, 0.200];
        if ($birria && $cremaLeche)
            $recetas[] = [$birria->id, $cremaLeche->id, 0.050];

        // ── Tacos de Cochinita Pibil → Aceite (0.005 L), Rúcula Baby (0.02 kg garnish)
        if ($cochinita && $aceite)
            $recetas[] = [$cochinita->id, $aceite->id, 0.005];
        if ($cochinita && $rúcula)
            $recetas[] = [$cochinita->id, $rúcula->id, 0.020];

        // ── Pastel Tres Leches → Crema de Leche (0.15 L), Parmesano (0.02 kg decoración)
        if ($treLeches && $cremaLeche)
            $recetas[] = [$treLeches->id, $cremaLeche->id, 0.150];
        if ($treLeches && $parmesano)
            $recetas[] = [$treLeches->id, $parmesano->id, 0.020];

        // ── Churros con Cajeta → Mantequilla (0.03 kg), Crema de Leche (0.05 L)
        if ($churros && $mantequilla)
            $recetas[] = [$churros->id, $mantequilla->id, 0.030];
        if ($churros && $cremaLeche)
            $recetas[] = [$churros->id, $cremaLeche->id, 0.050];

        // Insertar todas las relaciones en la pivote
        foreach ($recetas as [$productoId, $materiaPrimaId, $cantidadRequerida]) {
            \Illuminate\Support\Facades\DB::table('producto_materia_prima')->updateOrInsert(
                ['producto_id' => $productoId, 'materia_prima_id' => $materiaPrimaId],
                ['cantidad_requerida' => $cantidadRequerida, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $this->command->info('✅ ProductoMateriaPrimaSeeder completado: ' . count($recetas) . ' asociaciones insertadas.');
    }
}
