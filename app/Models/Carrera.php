<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Carrera académica que agrupa usuarios y registros de estudiantes.
 */
class Carrera extends Model
{
    /**
     * Campos que Eloquent permite asignar mediante create o update.
     * Esta lista no reemplaza la validación de entrada ni la Policy.
     */
    protected $fillable = [
        'nombre',
        'codigo',
    ];

    /**
     * Usuarios asignados a esta carrera mediante users.carrera_id.
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'carrera_id');
    }

    /**
     * Registros académicos de esta carrera mediante estudiantes.carrera_id.
     */
    public function estudiantes(): HasMany
    {
        return $this->hasMany(Estudiante::class, 'carrera_id');
    }
}
