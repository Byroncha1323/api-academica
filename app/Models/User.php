<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Cuenta autenticable del sistema, separada del registro académico.
 * Sanctum gestiona sus tokens y Spatie sus roles y permisos.
 */
class User extends Authenticatable
{
    // Tokens de Sanctum, datos de prueba, RBAC de Spatie y notificaciones de Laravel.
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Campos que Eloquent permite asignar mediante create o update.
     * Esta lista no reemplaza la validación de entrada ni la Policy.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'carrera_id',
        'sede_id',
    ];

    /**
     * Atributos excluidos del JSON del usuario, incluidas las relaciones cargadas.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Convierte la fecha de verificación y aplica hash al asignar una contraseña.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Carrera del usuario, nullable para cuentas sin adscripción académica.
     */
    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }

    /**
     * Sede del usuario; un valor nulo no cumple la regla ABAC de modificación.
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    /**
     * Registro académico de esta cuenta; user_id único mantiene la relación uno a uno.
     */
    public function estudiante(): HasOne
    {
        return $this->hasOne(Estudiante::class, 'user_id');
    }
}
