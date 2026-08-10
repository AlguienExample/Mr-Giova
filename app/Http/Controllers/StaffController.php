<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function index()
    {
        $staff = Empleado::with('usuario')->get()->map(function ($e) {
            return [
                'id' => $e->id,
                'nombre' => $e->usuario ? $e->usuario->nombres . ' ' . $e->usuario->apellidos : 'Desconocido',
                'cargo' => $e->cargo,
                'activo' => $e->usuario ? $e->usuario->activo : true
            ];
        });
        return response()->json($staff);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombres' => 'required|string|max:255',
            'cargo'   => 'required|string|max:50',
            'rol'     => 'required|string|exists:roles,name',
        ]);

        try {
            DB::beginTransaction();
            
            // Separar nombres y apellidos (simple)
            $parts     = explode(' ', trim($request->nombres));
            $nombres   = array_shift($parts);
            $apellidos = count($parts) > 0 ? implode(' ', $parts) : '';
            
            // Generar email temporal único
            $email = strtolower($nombres) . rand(100,999) . '@mrgiova.com';

            // Buscar el rol por nombre (validado ya arriba con exists:roles,name)
            $role = \App\Models\Role::where('name', $request->rol)->firstOrFail();

            // Generar contraseña segura aleatoria (12 caracteres)
            $passwordPlain = \Illuminate\Support\Str::password(12);

            $usuario = Usuario::create([
                'nombres'   => $nombres,
                'apellidos' => $apellidos,
                'email'     => $email,
                'password'  => Hash::make($passwordPlain),
                'rol_id'    => $role->id,
                'activo'    => true,
            ]);

            Empleado::create([
                'usuario_id'         => $usuario->id,
                'cargo'              => $request->cargo,
                'fecha_contratacion' => now(),
                'sueldo'             => 0,
                'turno'              => 'Rotativo',
            ]);

            DB::commit();
            return response()->json([
                'success'          => true,
                'message'          => 'Empleado registrado correctamente.',
                'credenciales'     => [
                    'email'    => $email,
                    'password' => $passwordPlain,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
