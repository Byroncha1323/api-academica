<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sede extends Model
{
    protected $fillable = [
        'nombre',
        'codigo',
    ];

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'sede_id');
    }

    public function estudiantes(): HasMany
    {
        return $this->hasMany(Estudiante::class, 'sede_id');
    }
}
