<?php

/**
 * Catálogo institucional usado para limitar el alcance de las modificaciones.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea sedes con nombre y código únicos.
     */
    public function up(): void
    {
        Schema::create('sedes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120)->unique();
            $table->string('codigo', 20)->unique();
            $table->timestamps();
        });
    }

    /**
     * Revierte los cambios de esta migración al ejecutar un rollback.
     * La reversión elimina las tablas o columnas definidas, junto con sus datos.
     */
    public function down(): void
    {
        Schema::dropIfExists('sedes');
    }
};
