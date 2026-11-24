<?php

namespace App\Policies;

use App\Models\Estudiante;
use App\Models\User;

class EstudiantePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('estudiantes.ver');
    }

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

    public function create(User $user): bool
    {
        return $user->can('estudiantes.crear');
    }

    public function update(User $user, Estudiante $estudiante): bool
    {
        if (! $user->can('estudiantes.editar')) {
            return false;
        }

        if ($user->hasRole('administrador')) {
            return true;
        }

        if ($user->hasAnyRole(['profesor', 'coordinador'])) {
            $mismaCarrera = $user->carrera_id !== null
                && $user->carrera_id === $estudiante->carrera_id;

            $mismaSede = $user->sede_id !== null
                && $user->sede_id === $estudiante->sede_id;

            return $mismaCarrera && $mismaSede;
        }

        return false;
    }

    public function delete(User $user, Estudiante $estudiante): bool
    {
        return $user->can('estudiantes.eliminar');
    }
}
