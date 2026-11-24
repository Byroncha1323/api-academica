<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Coordina el CRUD académico: autoriza, valida y persiste, en ese orden.
 * Las decisiones por usuario y registro pertenecen a EstudiantePolicy.
 */
class EstudianteController extends Controller
{
    /**
     * Autoriza viewAny y devuelve el listado general en páginas de diez registros.
     * Carga las relaciones para evitar consultas separadas al serializar cada fila.
     * El listado no se filtra por propietario; el detalle utiliza otra regla.
     */
    public function index()
    {
        Gate::authorize('viewAny', Estudiante::class);

        return Estudiante::with(['carrera', 'sede', 'usuario'])
            ->paginate(10);
    }

    /**
     * Autoriza el detalle y carga sus relaciones antes de devolver el modelo.
     * Laravel resuelve {estudiante} por ID; si no existe, responde HTTP 404.
     */
    public function show(Estudiante $estudiante)
    {
        Gate::authorize('view', $estudiante);

        return $estudiante->load(['carrera', 'sede', 'usuario']);
    }

    /**
     * Exige permiso de creación y valida relaciones, formatos y valores únicos.
     * La ruta también utiliza el middleware permission:estudiantes.crear.
     * Una entrada inválida devuelve 422; la creación correcta devuelve 201.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Estudiante::class);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id', 'unique:estudiantes,user_id'],
            'carrera_id' => ['required', 'exists:carreras,id'],
            'sede_id' => ['required', 'exists:sedes,id'],
            'cedula' => ['required', 'string', 'max:30', 'unique:estudiantes,cedula'],
            'nombre' => ['required', 'string', 'max:120'],
            'apellido' => ['required', 'string', 'max:120'],
            'correo_institucional' => ['required', 'email', 'max:150', 'unique:estudiantes,correo_institucional'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        $estudiante = Estudiante::create($data);

        return response()->json($estudiante, 201);
    }

    /**
     * Autoriza el registro existente y actualiza solo los campos validados.
     * sometimes permite cambios parciales tanto por PUT como por PATCH.
     * user_id queda fuera de la validación para conservar la identidad propietaria.
     */
    public function update(Request $request, Estudiante $estudiante)
    {
        Gate::authorize('update', $estudiante);

        $data = $request->validate([
            'carrera_id' => ['sometimes', 'exists:carreras,id'],
            'sede_id' => ['sometimes', 'exists:sedes,id'],
            'cedula' => [
                'sometimes',
                'string',
                'max:30',
                // Conservar la cédula actual no debe colisionar con el propio registro.
                Rule::unique('estudiantes', 'cedula')->ignore($estudiante->id),
            ],
            'nombre' => ['sometimes', 'string', 'max:120'],
            'apellido' => ['sometimes', 'string', 'max:120'],
            'correo_institucional' => [
                'sometimes',
                'email',
                'max:150',
                Rule::unique('estudiantes', 'correo_institucional')->ignore($estudiante->id),
            ],
            'activo' => ['sometimes', 'boolean'],
        ]);

        $estudiante->update($data);

        return response()->json($estudiante);
    }

    /**
     * Exige permiso de eliminación, borra el registro académico y devuelve 200.
     * No elimina la cuenta User asociada; consultar el registro borrado devuelve 404.
     */
    public function destroy(Estudiante $estudiante)
    {
        Gate::authorize('delete', $estudiante);

        $estudiante->delete();

        return response()->json([
            'message' => 'Estudiante eliminado correctamente',
        ]);
    }
}
