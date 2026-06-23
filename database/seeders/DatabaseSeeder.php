<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\Usuario;
use App\Models\Empleado;
use App\Models\Cliente;
use App\Models\Mesa;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Pedido;
use App\Models\DetallePedido;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles
        $rolAdmin = Role::create([
            'name' => 'Administrador',
            'description' => 'Acceso total al sistema y reportes'
        ]);

        $rolCocina = Role::create([
            'name' => 'Cocinero',
            'description' => 'Visualiza y gestiona preparación de pedidos'
        ]);

        $rolMesero = Role::create([
            'name' => 'Mesero',
            'description' => 'Toma pedidos presenciales y gestiona mesas'
        ]);

        $rolCliente = Role::create([
            'name' => 'Cliente',
            'description' => 'Comensal del restaurante'
        ]);

        // 2. Usuarios y perfiles asociados
        $userAdmin = Usuario::create([
            'nombres' => 'Don',
            'apellidos' => 'Giova',
            'email' => 'admin@mrgiova.com',
            'password' => Hash::make('admin123'),
            'rol_id' => $rolAdmin->id,
            'activo' => true,
        ]);
        Empleado::create([
            'usuario_id' => $userAdmin->id,
            'cargo' => 'Gerente General',
            'fecha_contratacion' => Carbon::now()->subMonths(12),
            'sueldo' => 3500000.00
        ]);

        $userCocina = Usuario::create([
            'nombres' => 'Mateo',
            'apellidos' => 'Gómez',
            'email' => 'cocina@mrgiova.com',
            'password' => Hash::make('cocina123'),
            'rol_id' => $rolCocina->id,
            'activo' => true,
        ]);
        Empleado::create([
            'usuario_id' => $userCocina->id,
            'cargo' => 'Chef Principal',
            'fecha_contratacion' => Carbon::now()->subMonths(6),
            'sueldo' => 2200000.00
        ]);

        $userMesero = Usuario::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Ruiz',
            'email' => 'mesero@mrgiova.com',
            'password' => Hash::make('mesero123'),
            'rol_id' => $rolMesero->id,
            'activo' => true,
        ]);
        Empleado::create([
            'usuario_id' => $userMesero->id,
            'cargo' => 'Mesero Principal',
            'fecha_contratacion' => Carbon::now()->subMonths(3),
            'sueldo' => 1500000.00
        ]);

        // Clientes recurrentes y genéricos
        $clientes = [];
        $nombresClientes = [
            ['Juan', 'Pérez', 'juan.perez@email.com'],
            ['María', 'Rodríguez', 'maria.rod@email.com'],
            ['Sofía', 'Martínez', 'sofia.mtz@email.com'],
            ['Alejandro', 'Sánchez', 'ale.sanchez@email.com']
        ];

        foreach ($nombresClientes as $index => $nc) {
            $userCli = Usuario::create([
                'nombres' => $nc[0],
                'apellidos' => $nc[1],
                'email' => $nc[2],
                'password' => Hash::make('cliente123'),
                'rol_id' => $rolCliente->id,
                'activo' => true,
            ]);
            $clientes[] = Cliente::create([
                'usuario_id' => $userCli->id,
                'telefono' => '312' . rand(1000000, 9999999),
                'direccion' => 'Calle ' . rand(1, 100) . ' # ' . rand(1, 50),
                'puntos_fidelidad' => rand(10, 150)
            ]);
        }

        // Crear clientes de mesa (invitados para las mesas)
        $clientesMesa = [];
        for ($i = 1; $i <= 8; $i++) {
            $userMesa = Usuario::create([
                'nombres' => 'Cliente',
                'apellidos' => 'Mesa ' . $i,
                'email' => "cliente.mesa{$i}@mrgiova.com",
                'password' => Hash::make("mesa{$i}secret"),
                'rol_id' => $rolCliente->id,
                'activo' => true,
            ]);
            $clientesMesa[$i] = Cliente::create([
                'usuario_id' => $userMesa->id,
                'telefono' => null,
                'direccion' => null,
                'puntos_fidelidad' => 0
            ]);
        }

        // 3. Mesas
        $mesas = [];
        $capacidades = [4, 2, 4, 6, 2, 4, 6, 8];
        for ($i = 1; $i <= 8; $i++) {
            $mesas[$i] = Mesa::create([
                'numero_mesa' => $i,
                'capacidad' => $capacidades[$i - 1],
                'estado' => $i === 5 || $i === 2 || $i === 8 || $i === 1 ? 'Ocupada' : 'Disponible',
                'codigo_qr' => "qr_mesa_{$i}_hash_" . rand(1000, 9999),
                'ubicacion' => $i <= 4 ? 'Terraza Principal' : 'Salón Central',
            ]);
        }

        // 4. Categorías
        $catHamb = Categoria::create(['nombre' => 'Hamburguesas', 'descripcion' => 'Hamburguesas premium con ingredientes de primera', 'activo' => true]);
        $catTaco = Categoria::create(['nombre' => 'Tacos & Quesadillas', 'descripcion' => 'Tradición mexicana con nuestro toque especial', 'activo' => true]);
        $catAcom = Categoria::create(['nombre' => 'Acompañamientos', 'descripcion' => 'Papas y entradas crujientes para picar', 'activo' => true]);
        $catBebi = Categoria::create(['nombre' => 'Bebidas', 'descripcion' => 'Aguas frescas naturales y cocteles mexicanos', 'activo' => true]);
        $catPost = Categoria::create(['nombre' => 'Postres', 'descripcion' => 'El toque dulce para terminar', 'activo' => true]);

        // 5. Productos
        // Hamburguesas
        $p1 = Producto::create([
            'categoria_id' => $catHamb->id,
            'nombre' => 'Hamburguesa Clásica Mr.Giova',
            'descripcion' => 'Carne 150g al carbón, lechuga, tomate fresco, cebolla, queso cheddar y salsa especial Mr.Giova.',
            'precio' => 22900.00,
            'imagen_url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&q=80&w=600',
            'disponible' => true,
            'tiempo_preparacion' => 15,
            'ingredientes' => 'Carne res, Pan brioche, Queso cheddar, Lechuga, Tomate, Cebolla, Salsa Mr.Giova',
            'stock' => 15
        ]);

        $p2 = Producto::create([
            'categoria_id' => $catHamb->id,
            'nombre' => 'Hamburguesa BBQ Mr.Giova',
            'descripcion' => 'Carne 150g, tocino ahumado crujiente, doble queso cheddar, cebolla caramelizada y salsa BBQ ahumada.',
            'precio' => 24900.00,
            'imagen_url' => 'https://images.unsplash.com/photo-1594212699903-ec8a3eca50f5?auto=format&fit=crop&q=80&w=600',
            'disponible' => true,
            'tiempo_preparacion' => 15,
            'ingredientes' => 'Carne res, Pan brioche, Tocino, Queso cheddar, Cebolla caramelizada, Salsa BBQ',
            'stock' => 12
        ]);

        $p3 = Producto::create([
            'categoria_id' => $catHamb->id,
            'nombre' => 'Hamburguesa Doble Mr.Giova',
            'descripcion' => 'Doble carne de 150g (300g total), doble queso cheddar fundido, lechuga, tomate y salsa especial.',
            'precio' => 29900.00,
            'imagen_url' => 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?auto=format&fit=crop&q=80&w=600',
            'disponible' => true,
            'tiempo_preparacion' => 18,
            'ingredientes' => 'Doble Carne res, Pan brioche, Doble Queso cheddar, Lechuga, Tomate, Salsa especial',
            'stock' => 2
        ]);

        // Tacos y Quesadillas
        $p4 = Producto::create([
            'categoria_id' => $catTaco->id,
            'nombre' => 'Tacos al Pastor',
            'descripcion' => '3 tacos en tortilla de maíz con jugosa carne de cerdo adobada, piña asada, cilantro y cebolla.',
            'precio' => 18900.00,
            'imagen_url' => 'https://images.unsplash.com/photo-1551504734-5ee1c4a1479b?auto=format&fit=crop&q=80&w=600',
            'disponible' => true,
            'tiempo_preparacion' => 10,
            'ingredientes' => 'Cerdo adobado, Tortillas de maíz, Piña, Cilantro, Cebolla, Salsa verde',
            'stock' => 20
        ]);

        $p5 = Producto::create([
            'categoria_id' => $catTaco->id,
            'nombre' => 'Quesadilla de Birria',
            'descripcion' => 'Tortilla de harina gigante rellena de queso Oaxaca fundido y birria de res terna, acompañada de consomé calientito para remojar.',
            'precio' => 21900.00,
            'imagen_url' => 'https://images.unsplash.com/photo-1599974579688-8dbdd335c77f?auto=format&fit=crop&q=80&w=600',
            'disponible' => true,
            'tiempo_preparacion' => 12,
            'ingredientes' => 'Birria de res, Tortilla de harina grande, Queso Oaxaca, Consomé, Cebolla, Cilantro',
            'stock' => 18
        ]);

        $p6 = Producto::create([
            'categoria_id' => $catTaco->id,
            'nombre' => 'Tacos de Cochinita Pibil',
            'descripcion' => '3 tacos de carne deshebrada de cerdo cocinada a fuego lento marinada en achiote, cebolla morada curtida con habanero y cilantro.',
            'precio' => 19900.00,
            'imagen_url' => 'https://images.unsplash.com/photo-1615870216519-2f9fa575fa5c?auto=format&fit=crop&q=80&w=600',
            'disponible' => true,
            'tiempo_preparacion' => 10,
            'ingredientes' => 'Cerdo en achiote, Tortillas de maíz, Cebolla morada curtida, Chile habanero, Cilantro',
            'stock' => 22
        ]);

        // Acompañamientos
        $p7 = Producto::create([
            'categoria_id' => $catAcom->id,
            'nombre' => 'Papas Francesas',
            'descripcion' => 'Papas fritas crujientes espolvoreadas con sal de mar y una pizca de pimentón, servidas con salsa de la casa.',
            'precio' => 8900.00,
            'imagen_url' => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&q=80&w=600',
            'disponible' => true,
            'tiempo_preparacion' => 5,
            'ingredientes' => 'Papas cortadas, Aceite vegetal, Pimentón, Sal marina',
            'stock' => 3
        ]);

        $p8 = Producto::create([
            'categoria_id' => $catAcom->id,
            'nombre' => 'Papas Mexicanas',
            'descripcion' => 'Gajos de papas crujientes bañados en salsa de queso fundido, pico de gallo fresco, crema agria y jalapeños.',
            'precio' => 12900.00,
            'imagen_url' => 'https://images.unsplash.com/photo-1518013770417-ce371a414136?auto=format&fit=crop&q=80&w=600',
            'disponible' => true,
            'tiempo_preparacion' => 7,
            'ingredientes' => 'Papas rústicas, Salsa de queso, Pico de gallo, Crema agria, Rodajas de jalapeño',
            'stock' => 10
        ]);

        // Bebidas
        $p9 = Producto::create([
            'categoria_id' => $catBebi->id,
            'nombre' => 'Agua Fresca de Horchata',
            'descripcion' => 'Bebida tradicional mexicana elaborada a base de arroz, leche condesada, vainilla y canela. Servida muy fría.',
            'precio' => 5900.00,
            'imagen_url' => 'https://images.unsplash.com/photo-1553530666-ba11a7da3888?auto=format&fit=crop&q=80&w=600',
            'disponible' => true,
            'tiempo_preparacion' => 3,
            'ingredientes' => 'Arroz, Agua, Leche, Canela, Azúcar, Vainilla',
            'stock' => 25
        ]);

        $p10 = Producto::create([
            'categoria_id' => $catBebi->id,
            'nombre' => 'Agua Fresca de Jamaica',
            'descripcion' => 'Infusión helada de flores de Jamaica orgánicas, endulzada al punto exacto. Refrescante y natural.',
            'precio' => 5900.00,
            'imagen_url' => 'https://images.unsplash.com/photo-1497534446932-c925b458314e?auto=format&fit=crop&q=80&w=600',
            'disponible' => true,
            'tiempo_preparacion' => 3,
            'ingredientes' => 'Flores de Jamaica, Agua, Azúcar',
            'stock' => 30
        ]);

        $p11 = Producto::create([
            'categoria_id' => $catBebi->id,
            'nombre' => 'Gaseosa Coca-Cola',
            'descripcion' => 'Refresco clásico frío en botella de vidrio de 350ml.',
            'precio' => 4500.00,
            'imagen_url' => 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&q=80&w=600',
            'disponible' => true,
            'tiempo_preparacion' => 2,
            'ingredientes' => 'Coca-Cola original',
            'stock' => 5
        ]);

        $p12 = Producto::create([
            'categoria_id' => $catBebi->id,
            'nombre' => 'Margarita de Tamarindo',
            'descripcion' => 'Cóctel helado elaborado con tequila reposado, licor de naranja, pulpa concentrada de tamarindo, jarabe y escarchado con sal y chile Tajín.',
            'precio' => 14900.00,
            'imagen_url' => 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?auto=format&fit=crop&q=80&w=600',
            'disponible' => true,
            'tiempo_preparacion' => 5,
            'ingredientes' => 'Tequila, Triple sec, Tamarindo, Limón, Chile Tajín',
            'stock' => 15
        ]);

        // Postres
        $p13 = Producto::create([
            'categoria_id' => $catPost->id,
            'nombre' => 'Churros con Cajeta',
            'descripcion' => '3 churros crujientes recién fritos, espolvoreados con azúcar y canela, acompañados de una porción generosa de cajeta de cabra.',
            'precio' => 8900.00,
            'imagen_url' => 'https://images.unsplash.com/photo-1589135306090-e7f09099c9d9?auto=format&fit=crop&q=80&w=600',
            'disponible' => true,
            'tiempo_preparacion' => 8,
            'ingredientes' => 'Harina de trigo, Azúcar, Canela, Cajeta tradicional',
            'stock' => 8
        ]);

        $p14 = Producto::create([
            'categoria_id' => $catPost->id,
            'nombre' => 'Pastel Tres Leches',
            'descripcion' => 'Esponjoso bizcocho empapado en nuestra mezcla secreta de tres leches, decorado con crema batida, fresas frescas y canela.',
            'precio' => 10900.00,
            'imagen_url' => 'https://images.unsplash.com/photo-1542826438-bd32f43d626f?auto=format&fit=crop&q=80&w=600',
            'disponible' => true,
            'tiempo_preparacion' => 5,
            'ingredientes' => 'Huevo, Harina, Leche evaporada, Leche condensada, Crema de leche, Fresas',
            'stock' => 6
        ]);

        // 6. Historial de Pedidos para el Dashboard (Últimos 7 días)
        $diasAtras = 7;
        $productosLista = [$p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9, $p10, $p11, $p12, $p13, $p14];

        for ($dia = $diasAtras; $dia >= 1; $dia--) {
            $fecha = Carbon::now()->subDays($dia)->setTime(rand(12, 22), rand(0, 59));
            $cantidadPedidos = rand(5, 12);

            for ($p = 0; $p < $cantidadPedidos; $p++) {
                $cliente = $clientes[rand(0, count($clientes) - 1)];
                $mesa = $mesas[rand(1, 8)];

                // Crear pedido completado
                $pedido = Pedido::create([
                    'cliente_id' => $cliente->id,
                    'empleado_id' => $userMesero->empleado->id,
                    'mesa_id' => $mesa->id,
                    'estado' => 'Entregado',
                    'tipo_pedido' => 'Presencial',
                    'total' => 0,
                    'notas' => 'Pedido de historial',
                    'hora_inicio_preparacion' => (clone $fecha)->addMinutes(rand(1, 5)),
                    'hora_listo' => (clone $fecha)->addMinutes(rand(15, 30)),
                    'hora_entregado' => (clone $fecha)->addMinutes(rand(30, 45)),
                    'prioridad' => 'Normal',
                    'created_at' => $fecha,
                    'updated_at' => (clone $fecha)->addMinutes(45),
                ]);

                // Agregar 1 a 4 productos aleatorios
                $total = 0;
                $numProductos = rand(1, 4);
                $prodSeleccionados = array_rand($productosLista, $numProductos);
                if (!is_array($prodSeleccionados)) {
                    $prodSeleccionados = [$prodSeleccionados];
                }

                foreach ($prodSeleccionados as $prodIndex) {
                    $prod = $productosLista[$prodIndex];
                    $cant = rand(1, 2);
                    $subt = $prod->precio * $cant;
                    $total += $subt;

                    DetallePedido::create([
                        'pedido_id' => $pedido->id,
                        'producto_id' => $prod->id,
                        'cantidad' => $cant,
                        'precio_unitario' => $prod->precio,
                        'subtotal' => $subt,
                        'notas_especiales' => null,
                        'estado_item' => 'Listo',
                        'created_at' => $fecha,
                        'updated_at' => (clone $fecha)->addMinutes(25),
                    ]);
                }

                $pedido->update(['total' => $total]);
            }
        }

        // 7. Pedidos Activos para Hoy (Simulando los del mock)
        $hoy = Carbon::now();

        // Pedido #1258: Mesa 5 (PENDIENTE - NUEVO) - Creado hoy hace 5 minutos
        $ped1258 = Pedido::create([
            'id' => 1258,
            'cliente_id' => $clientesMesa[5]->id,
            'empleado_id' => null,
            'mesa_id' => $mesas[5]->id,
            'estado' => 'Nuevo',
            'tipo_pedido' => 'Presencial',
            'total' => 36300.00,
            'notas' => 'Sin cebolla en la hamburguesa.',
            'prioridad' => 'Normal',
            'created_at' => (clone $hoy)->subMinutes(5),
            'updated_at' => (clone $hoy)->subMinutes(5)
        ]);

        DetallePedido::create([
            'pedido_id' => $ped1258->id,
            'producto_id' => $p1->id, // Hamb Clásica
            'cantidad' => 1,
            'precio_unitario' => $p1->precio,
            'subtotal' => $p1->precio,
            'notas_especiales' => 'Sin cebolla en la hamburguesa',
            'estado_item' => 'Pendiente'
        ]);
        DetallePedido::create([
            'pedido_id' => $ped1258->id,
            'producto_id' => $p7->id, // Papas Francesas
            'cantidad' => 1,
            'precio_unitario' => $p7->precio,
            'subtotal' => $p7->precio,
            'estado_item' => 'Pendiente'
        ]);
        DetallePedido::create([
            'pedido_id' => $ped1258->id,
            'producto_id' => $p11->id, // Coca Cola
            'cantidad' => 1,
            'precio_unitario' => $p11->precio,
            'subtotal' => $p11->precio,
            'estado_item' => 'Pendiente'
        ]);

        // Pedido #1259: Mesa 3 (PENDIENTE - NUEVO) - Creado hoy hace 10 minutos
        $ped1259 = Pedido::create([
            'id' => 1259,
            'cliente_id' => $clientesMesa[3]->id,
            'empleado_id' => null,
            'mesa_id' => $mesas[3]->id,
            'estado' => 'Nuevo',
            'tipo_pedido' => 'Para_Llevar',
            'total' => 29800.00,
            'notas' => 'En salsa verde las enchiladas si se puede',
            'prioridad' => 'Normal',
            'created_at' => (clone $hoy)->subMinutes(10),
            'updated_at' => (clone $hoy)->subMinutes(10)
        ]);
        DetallePedido::create([
            'pedido_id' => $ped1259->id,
            'producto_id' => $p4->id, // Tacos al pastor
            'cantidad' => 1,
            'precio_unitario' => $p4->precio,
            'subtotal' => $p4->precio,
            'estado_item' => 'Pendiente'
        ]);
        DetallePedido::create([
            'pedido_id' => $ped1259->id,
            'producto_id' => $p14->id, // Tres Leches
            'cantidad' => 1,
            'precio_unitario' => $p14->precio,
            'subtotal' => $p14->precio,
            'estado_item' => 'Pendiente'
        ]);

        // Pedido #1255: Mesa 2 (EN PREPARACION) - Iniciado preparación hoy hace 8 minutos
        $ped1255 = Pedido::create([
            'id' => 1255,
            'cliente_id' => $clientesMesa[2]->id,
            'empleado_id' => $userMesero->empleado->id,
            'mesa_id' => $mesas[2]->id,
            'estado' => 'En_Preparacion',
            'tipo_pedido' => 'Presencial',
            'total' => 34400.00,
            'notas' => null,
            'hora_inicio_preparacion' => (clone $hoy)->subMinutes(8),
            'prioridad' => 'Alta',
            'created_at' => (clone $hoy)->subMinutes(15),
            'updated_at' => (clone $hoy)->subMinutes(8)
        ]);
        DetallePedido::create([
            'pedido_id' => $ped1255->id,
            'producto_id' => $p3->id, // Hamb Doble
            'cantidad' => 1,
            'precio_unitario' => $p3->precio,
            'subtotal' => $p3->precio,
            'estado_item' => 'En_Preparacion'
        ]);
        DetallePedido::create([
            'pedido_id' => $ped1255->id,
            'producto_id' => $p11->id, // Coca Cola
            'cantidad' => 1,
            'precio_unitario' => $p11->precio,
            'subtotal' => $p11->precio,
            'estado_item' => 'Listo'
        ]);

        // Pedido #1256: Mesa 8 (EN PREPARACION) - Iniciado hoy hace 3 minutos
        $ped1256 = Pedido::create([
            'id' => 1256,
            'cliente_id' => $clientesMesa[8]->id,
            'empleado_id' => $userMesero->empleado->id,
            'mesa_id' => $mesas[8]->id,
            'estado' => 'En_Preparacion',
            'tipo_pedido' => 'Presencial',
            'total' => 27800.00,
            'notas' => 'Con bastante consomé.',
            'hora_inicio_preparacion' => (clone $hoy)->subMinutes(3),
            'prioridad' => 'Normal',
            'created_at' => (clone $hoy)->subMinutes(6),
            'updated_at' => (clone $hoy)->subMinutes(3)
        ]);
        DetallePedido::create([
            'pedido_id' => $ped1256->id,
            'producto_id' => $p5->id, // Quesadilla de Birria
            'cantidad' => 1,
            'precio_unitario' => $p5->precio,
            'subtotal' => $p5->precio,
            'estado_item' => 'En_Preparacion'
        ]);
        DetallePedido::create([
            'pedido_id' => $ped1256->id,
            'producto_id' => $p9->id, // Horchata
            'cantidad' => 1,
            'precio_unitario' => $p9->precio,
            'subtotal' => $p9->precio,
            'estado_item' => 'Listo'
        ]);

        // Pedido #1252: Mesa 1 (LISTO) - Terminado hace 2 minutos
        $ped1252 = Pedido::create([
            'id' => 1252,
            'cliente_id' => $clientesMesa[1]->id,
            'empleado_id' => $userMesero->empleado->id,
            'mesa_id' => $mesas[1]->id,
            'estado' => 'Listo',
            'tipo_pedido' => 'Presencial',
            'total' => 31800.00,
            'notas' => null,
            'hora_inicio_preparacion' => (clone $hoy)->subMinutes(20),
            'hora_listo' => (clone $hoy)->subMinutes(2),
            'prioridad' => 'Normal',
            'created_at' => (clone $hoy)->subMinutes(25),
            'updated_at' => (clone $hoy)->subMinutes(2)
        ]);
        DetallePedido::create([
            'pedido_id' => $ped1252->id,
            'producto_id' => $p1->id, // Hamb Clásica
            'cantidad' => 1,
            'precio_unitario' => $p1->precio,
            'subtotal' => $p1->precio,
            'estado_item' => 'Listo'
        ]);
        DetallePedido::create([
            'pedido_id' => $ped1252->id,
            'producto_id' => $p7->id, // Papas Francesas
            'cantidad' => 1,
            'precio_unitario' => $p7->precio,
            'subtotal' => $p7->precio,
            'estado_item' => 'Listo'
        ]);
    }
}
