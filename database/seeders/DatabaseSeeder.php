<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Punto de entrada de php artisan db:seed y de las pruebas con datos de laboratorio.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Crea primero roles y permisos; luego las cuentas que necesitan esos roles.
     */
    public function run(): void
    {
        $this->call([RolesYPermisosSeeder::class, DatosPruebaSeeder::class]);
    }
}
