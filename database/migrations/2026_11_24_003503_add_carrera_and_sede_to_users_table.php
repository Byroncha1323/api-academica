<?php

/**
 * Atributos académicos del usuario utilizados en ABAC.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade relaciones opcionales con carrera y sede; su eliminación deja el atributo nulo.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('carrera_id')
                ->nullable()
                ->constrained('carreras')
                ->nullOnDelete();

            $table->foreignId('sede_id')
                ->nullable()
                ->constrained('sedes')
                ->nullOnDelete();
        });
    }

    /**
     * Revierte los cambios de esta migración al ejecutar un rollback.
     * La reversión elimina las tablas o columnas definidas, junto con sus datos.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('carrera_id');
            $table->dropConstrainedForeignId('sede_id');
        });
    }
};
