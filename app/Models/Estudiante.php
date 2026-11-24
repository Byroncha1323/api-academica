<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro académico asociado a una cuenta mediante user_id único.
 * La Policy compara su propietario, carrera y sede con los del solicitante.
 */
class Estudiante extends Model
{
    /**
     * Campos que Eloquent permite asignar mediante create o update.
     * Esta lista no reemplaza la validación de entrada ni la Policy.
     */
    protected $fillable = [
        'user_id',
        'carrera_id',
        'sede_id',
        'cedula',
        'nombre',
        'apellido',
        'correo_institucional',
        'activo',
    ];

    /**
     * Convierte activo a booleano al leer y serializar el registro.
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * Cuenta propietaria del registro; no necesariamente coincide con quien consulta.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Carrera del registro, utilizada en las reglas de consulta y modificación.
     */
    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }

    /**
     * Sede del registro, comparada en la autorización de modificación.
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }
}
