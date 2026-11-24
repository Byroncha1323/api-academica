<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesYPermisosSeeder extends Seeder
{
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

        $roles = [
            'administrador' => $permisos,
            'profesor' => [
                'estudiantes.ver',
                'estudiantes.editar',
            ],
            'coordinador' => [
                'estudiantes.ver',
                'estudiantes.editar',
            ],
            'estudiante' => [
                'estudiantes.ver',
            ],
        ];

        foreach ($roles as $nombre => $permisosDelRol) {
            $rol = Role::firstOrCreate([
                'name' => $nombre,
                'guard_name' => 'web',
            ]);

            $rol->syncPermissions($permisosDelRol);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}