<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reserva;
use App\Models\Mesa;
use App\Models\Cliente;
use App\Models\Categoria;
use App\Models\Producto;
use Carbon\Carbon;

class HauteEpicureSeeder extends Seeder
{
    public function run(): void
    {
        // Premium Categories
        $catPremium = Categoria::firstOrCreate(
            ['nombre' => 'Premium & Delicatessen'],
            ['descripcion' => 'Platos de alta cocina e ingredientes premium', 'activo' => true]
        );

        $catBodega = Categoria::firstOrCreate(
            ['nombre' => 'Bodega Exclusive'],
            ['descripcion' => 'Vinos y licores exclusivos', 'activo' => true]
        );

        // Premium Products
        Producto::firstOrCreate(
            ['nombre' => 'Filete de Wagyu A5'],
            [
                'categoria_id' => $catPremium->id,
                'descripcion' => 'Corte premium de Wagyu A5 importado de Miyazaki.',
                'precio' => 450000.00,
                'imagen_url' => 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?auto=format&fit=crop&q=80&w=600',
                'disponible' => true,
                'tiempo_preparacion' => 20,
                'ingredientes' => 'Lomo Wagyu A5, Sal de mar',
                'stock' => 8
            ]
        );

        Producto::firstOrCreate(
            ['nombre' => 'Langosta Thermidor'],
            [
                'categoria_id' => $catPremium->id,
                'descripcion' => 'Langosta gratinada con salsa Thermidor clásica.',
                'precio' => 280000.00,
                'imagen_url' => 'https://images.unsplash.com/photo-1559742811-822873691df8?auto=format&fit=crop&q=80&w=600',
                'disponible' => true,
                'tiempo_preparacion' => 30,
                'ingredientes' => 'Langosta, Mantequilla, Queso Gruyere',
                'stock' => 12
            ]
        );

        Producto::firstOrCreate(
            ['nombre' => 'Risotto de Trufa Negra'],
            [
                'categoria_id' => $catPremium->id,
                'descripcion' => 'Risotto Arborio con trufa negra de Périgord.',
                'precio' => 180000.00,
                'imagen_url' => 'https://images.unsplash.com/photo-1626200419188-f56743b17c9d?auto=format&fit=crop&q=80&w=600',
                'disponible' => true,
                'tiempo_preparacion' => 25,
                'ingredientes' => 'Arroz Arborio, Trufa Negra, Parmesano Reggiano',
                'stock' => 0 // Critico
            ]
        );

        Producto::firstOrCreate(
            ['nombre' => 'Caviar Beluga Imperial'],
            [
                'categoria_id' => $catPremium->id,
                'descripcion' => 'Caviar del mar Caspio.',
                'precio' => 850000.00,
                'imagen_url' => 'https://images.unsplash.com/photo-1588691517409-f30f576ff38f?auto=format&fit=crop&q=80&w=600',
                'disponible' => true,
                'tiempo_preparacion' => 5,
                'ingredientes' => 'Caviar Beluga, Blinis',
                'stock' => 12
            ]
        );

        Producto::firstOrCreate(
            ['nombre' => 'Dom Pérignon Plénitude 2'],
            [
                'categoria_id' => $catBodega->id,
                'descripcion' => 'Añada 2003.',
                'precio' => 2100000.00,
                'imagen_url' => 'https://images.unsplash.com/photo-1596700078028-eb6e8b46e336?auto=format&fit=crop&q=80&w=600',
                'disponible' => true,
                'tiempo_preparacion' => 5,
                'ingredientes' => 'Champagne',
                'stock' => 24
            ]
        );


        // Reservations
        $clientes = Cliente::take(4)->get();
        $mesas = Mesa::all();

        if ($clientes->count() >= 4 && $mesas->count() >= 8) {
            Reserva::truncate();

            // Valeria Montgomery - Jueves 17 a las 20:30 (Mesa 12 -> use Mesa 6 instead, VIP 8 pers)
            Reserva::create([
                'cliente_id' => $clientes[0]->id,
                'mesa_id' => 6,
                'fecha_hora' => Carbon::now()->addDays(2)->setTime(20, 30),
                'num_personas' => 4,
                'estado' => 'Confirmada',
                'notas' => 'VIP Elite. "Aniversario de bodas. Preferencia por mesa cerca del ventanal. Alergia severa a frutos secos."'
            ]);

            // Julian Arquet - Jueves 17 a las 21:15 (Mesa 04)
            Reserva::create([
                'cliente_id' => $clientes[1]->id,
                'mesa_id' => 4,
                'fecha_hora' => Carbon::now()->addDays(2)->setTime(21, 15),
                'num_personas' => 2,
                'estado' => 'Confirmada',
                'notas' => 'Sin notas adicionales.'
            ]);

            // Clarisse Fontaine - Jueves 17 a las 21:30 (Mesa 08)
            Reserva::create([
                'cliente_id' => $clientes[2]->id,
                'mesa_id' => 8,
                'fecha_hora' => Carbon::now()->addDays(2)->setTime(21, 30),
                'num_personas' => 6,
                'estado' => 'Pendiente',
                'notas' => 'Lista de Espera. Cena corporativa. Requieren privacidad absoluta.'
            ]);
        }
    }
}
