<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sede institucional utilizada como atributo de alcance en la autorización.
 */
class Sede extends Model
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
     * Usuarios asignados a esta sede mediante users.sede_id.
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'sede_id');
    }

    /**
     * Registros académicos de esta sede mediante estudiantes.sede_id.
     */
    public function estudiantes(): HasMany
    {
        return $this->hasMany(Estudiante::class, 'sede_id');
    }
}
