<?php

/**
 * Registros académicos y relaciones necesarias para propiedad, carrera y sede.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea un registro por usuario, cédula/correo únicos y estado activo por defecto.
     */
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            // Cuenta única por registro; borrar al usuario elimina su registro académico.
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            // No se puede borrar una carrera o sede referenciada por estudiantes.
            $table->foreignId('carrera_id')
                ->constrained('carreras')
                ->restrictOnDelete();

            $table->foreignId('sede_id')
                ->nullable()
                ->constrained('sedes')
                ->restrictOnDelete();

            $table->string('cedula', 30)->unique();
            $table->string('nombre', 120);
            $table->string('apellido', 120);
            $table->string('correo_institucional', 150)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Revierte los cambios de esta migración al ejecutar un rollback.
     * La reversión elimina las tablas o columnas definidas, junto con sus datos.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
