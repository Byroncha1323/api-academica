<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $sanCarlos = Sede::firstOrCreate(['codigo' => 'SC'], ['nombre' => 'San Carlos']);
        $central = Sede::firstOrCreate(['codigo' => 'CEN'], ['nombre' => 'Central']);

        $sistemas = Carrera::firstOrCreate(
            ['codigo' => 'ITI'],
            ['nombre' => 'Ingeniería en Tecnologías de Información']
        );

        $software = Carrera::firstOrCreate(
            ['codigo' => 'ISW'],
            ['nombre' => 'Ingeniería de Software']
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@academica.test'],
            [
                'name' => 'Administrador General',
                'password' => Hash::make('Password123!'),
                'carrera_id' => null,
            ]
        );
        $admin->syncRoles(['administrador']);

        $profesorITI = User::firstOrCreate(
            ['email' => 'profesor.iti@academica.test'],
            [
                'name' => 'Profesor de TI',
                'password' => Hash::make('Password123!'),
                'carrera_id' => $sistemas->id,
                'sede_id' => $sanCarlos->id,
            ]
        );
        $profesorITI->syncRoles(['profesor']);

        $profesorISW = User::firstOrCreate(
            ['email' => 'profesor.isw@academica.test'],
            [
                'name' => 'Profesor de Software',
                'password' => Hash::make('Password123!'),
                'carrera_id' => $software->id,
                'sede_id' => $central->id,
            ]
        );
        $profesorISW->syncRoles(['profesor']);

        $usuarioEstudiante1 = User::firstOrCreate(
            ['email' => 'estudiante1@academica.test'],
            [
                'name' => 'Ana Estudiante',
                'password' => Hash::make('Password123!'),
                'carrera_id' => $sistemas->id,
                'sede_id' => $sanCarlos->id,
            ]
        );
        $usuarioEstudiante1->syncRoles(['estudiante']);

        $usuarioEstudiante2 = User::firstOrCreate(
            ['email' => 'estudiante2@academica.test'],
            [
                'name' => 'Luis Estudiante',
                'password' => Hash::make('Password123!'),
                'carrera_id' => $software->id,
                'sede_id' => $central->id,
            ]
        );
        $usuarioEstudiante2->syncRoles(['estudiante']);

        Estudiante::firstOrCreate(
            ['user_id' => $usuarioEstudiante1->id],
            [
                'carrera_id' => $sistemas->id,
                'sede_id' => $sanCarlos->id,
                'cedula' => '1-1111-1111',
                'nombre' => 'Ana',
                'apellido' => 'Estudiante',
                'correo_institucional' => 'ana@estudiantes.test',
                'activo' => true,
            ]
        );

        Estudiante::firstOrCreate(
            ['user_id' => $usuarioEstudiante2->id],
            [
                'carrera_id' => $software->id,
                'sede_id' => $central->id,
                'cedula' => '2-2222-2222',
                'nombre' => 'Luis',
                'apellido' => 'Estudiante',
                'correo_institucional' => 'luis@estudiantes.test',
                'activo' => true,
            ]
        );

        $coordinador = User::firstOrCreate(['email' => 'coordinador.iti@academica.test'], [
            'name' => 'Coordinador ITI', 'password' => Hash::make('Password123!'),
            'carrera_id' => $sistemas->id, 'sede_id' => $sanCarlos->id,
        ]);
        $coordinador->syncRoles(['coordinador']);

        foreach ([
            [3, 'María', $sistemas, $central, true],
            [4, 'Pedro', $software, $sanCarlos, false],
        ] as [$numero, $nombre, $carrera, $sede, $activo]) {
            $usuario = User::firstOrCreate(['email' => "estudiante{$numero}@academica.test"], [
                'name' => "$nombre Estudiante", 'password' => Hash::make('Password123!'),
                'carrera_id' => $carrera->id, 'sede_id' => $sede->id,
            ]);
            $usuario->syncRoles(['estudiante']);
            Estudiante::firstOrCreate(['user_id' => $usuario->id], [
                'carrera_id' => $carrera->id, 'sede_id' => $sede->id,
                'cedula' => "$numero-0000-0000", 'nombre' => $nombre, 'apellido' => 'Estudiante',
                'correo_institucional' => "estudiante{$numero}@estudiantes.test", 'activo' => $activo,
            ]);
        }
    }
}
