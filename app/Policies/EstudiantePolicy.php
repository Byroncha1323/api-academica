<?php

namespace App\Policies;

use App\Models\Estudiante;
use App\Models\User;

/**
 * Reglas de acceso al recurso Estudiante, descubiertas por convención de Laravel.
 * Combina RBAC mediante permisos con ABAC por propiedad, carrera y sede.
 */
class EstudiantePolicy
{
    /**
     * Autoriza el listado general con estudiantes.ver, sin filtrar sus registros.
     * Los cuatro roles sembrados lo tienen; una cuenta pública sin rol no lo recibe.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('estudiantes.ver');
    }

    /**
     * Autoriza un detalle: administrador tiene acceso general; estudiante exige
     * propiedad; profesor y coordinador necesitan permiso de ver y misma carrera.
     * La sede no limita esta consulta y el propietario puede ver su registro inactivo.
     */
    public function view(User $user, Estudiante $estudiante): bool
    {
        if ($user->hasRole('administrador')) {
            return true;
        }

        if ($user->hasRole('estudiante')) {
            // La propiedad se exige tanto para registros activos como inactivos.
            return $estudiante->user_id === $user->id;
        }

        if ($user->hasAnyRole(['profesor', 'coordinador'])) {
            return $user->can('estudiantes.ver')
                && $user->carrera_id !== null
                && $user->carrera_id === $estudiante->carrera_id;
        }

        return false;
    }

    /**
     * Comprueba el permiso RBAC de alta; solo el administrador lo recibe al sembrar.
     */
    public function create(User $user): bool
    {
        return $user->can('estudiantes.crear');
    }

    /**
     * Exige editar y restringe a profesor/coordinador por carrera y sede no nulas.
     * El administrador conserva alcance general si tiene editar. Se comparan los
     * atributos actuales del registro, antes de validar y guardar la modificación.
     */
    public function update(User $user, Estudiante $estudiante): bool
    {
        if (! $user->can('estudiantes.editar')) {
            return false;
        }

        if ($user->hasRole('administrador')) {
            return true;
        }

        if ($user->hasAnyRole(['profesor', 'coordinador'])) {
            // Un atributo ausente no prueba pertenencia, aunque ambos IDs fueran nulos.
            $mismaCarrera = $user->carrera_id !== null
                && $user->carrera_id === $estudiante->carrera_id;

            $mismaSede = $user->sede_id !== null
                && $user->sede_id === $estudiante->sede_id;

            return $mismaCarrera && $mismaSede;
        }

        return false;
    }

    /**
     * Comprueba el permiso de baja; solo el rol administrador lo recibe al sembrar.
     */
    public function delete(User $user, Estudiante $estudiante): bool
    {
        return $user->can('estudiantes.eliminar');
    }
}
