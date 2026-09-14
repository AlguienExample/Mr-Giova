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
            
            // Email sanitizado (sin acentos/espacios) y garantizado único con reintentos
            $base = \Illuminate\Support\Str::slug($nombres, '') ?: 'usuario';
            $email = null;
            for ($i = 0; $i < 5; $i++) {
                $candidato = strtolower($base) . rand(100, 999) . '@saborapueblo.com';
                if (!Usuario::where('email', $candidato)->exists()) {
                    $email = $candidato;
                    break;
                }
            }
            if (!$email) {
                DB::rollBack();
                return response()->json(['error' => 'No se pudo generar un correo único. Intente de nuevo.'], 422);
            }

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
            \Illuminate\Support\Facades\Log::error('[StaffController@store] ' . $e->getMessage());
            return response()->json(['error' => 'No se pudo registrar el empleado. Intente de nuevo.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'cargo'  => 'sometimes|string|max:50',
            'activo' => 'sometimes|boolean',
        ]);

        $empleado = Empleado::with('usuario')->find($id);
        if (!$empleado) {
            return response()->json(['error' => 'Empleado no encontrado.'], 404);
        }

        if ($request->has('activo') && !$request->boolean('activo')
            && $empleado->usuario_id === auth()->id()) {
            return response()->json(['error' => 'No puedes desactivar tu propio usuario.'], 422);
        }

        DB::transaction(function () use ($request, $empleado) {
            if ($request->has('cargo')) {
                $empleado->update(['cargo' => $request->input('cargo')]);
            }
            if ($empleado->usuario && $request->has('activo')) {
                $empleado->usuario->update(['activo' => $request->boolean('activo')]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Empleado actualizado correctamente.']);
    }

    public function destroy($id)
    {
        $empleado = Empleado::find($id);
        if (!$empleado) {
            return response()->json(['error' => 'Empleado no encontrado.'], 404);
        }

        if ($empleado->usuario_id === auth()->id()) {
            return response()->json(['error' => 'No puedes eliminar tu propio usuario.'], 422);
        }

        // Con historial de reposiciones: no borrar, desactivar en su lugar.
        if (\App\Models\PedidoProveedor::where('empleado_id', $empleado->id)->exists()) {
            return response()->json([
                'error' => 'No se puede eliminar: el empleado tiene reposiciones registradas. Desactívalo en su lugar.',
            ], 422);
        }

        // Borrar el usuario arrastra al empleado (FK cascade).
        $empleado->usuario?->delete();

        return response()->json(['success' => true, 'message' => 'Empleado eliminado correctamente.']);
    }
}
