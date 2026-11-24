<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Configura los cuatro roles y permisos que utiliza el taller.
 * Las asignaciones son repetibles y no dependen de IDs preestablecidos.
 */
class RolesYPermisosSeeder extends Seeder
{
    /**
     * Crea o reutiliza roles/permisos y sincroniza sus capacidades exactas.
     * El guard web identifica estos roles de Spatie; las rutas autentican con Sanctum.
     * Se limpia la caché antes y después para que los cambios sean efectivos.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisos = [
            'estudiantes.ver',
            'estudiantes.crear',
            'estudiantes.editar',
            'estudiantes.eliminar',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => 'web',
            ]);
        }

        $admin = Role::firstOrCreate([
            'name' => 'administrador',
            'guard_name' => 'web',
        ]);

        $profesor = Role::firstOrCreate([
            'name' => 'profesor',
            'guard_name' => 'web',
        ]);

        $estudiante = Role::firstOrCreate([
            'name' => 'estudiante',
            'guard_name' => 'web',
        ]);

        $admin->syncPermissions($permisos);

        // Profesor y coordinador pueden editar, pero la Policy limita carrera y sede.
        $profesor->syncPermissions([
            'estudiantes.ver',
            'estudiantes.editar',
        ]);

        $estudiante->syncPermissions([
            'estudiantes.ver',
        ]);

        $coordinador = Role::firstOrCreate([
            'name' => 'coordinador',
            'guard_name' => 'web',
        ]);
        $coordinador->syncPermissions(['estudiantes.ver', 'estudiantes.editar']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
