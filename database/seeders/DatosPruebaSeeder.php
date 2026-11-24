<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $iti = Carrera::firstOrCreate(
            ['codigo' => 'ITI'],
            ['nombre' => 'Ingeniería en Tecnologías de Información']
        );

        $isw = Carrera::firstOrCreate(
            ['codigo' => 'ISW'],
            ['nombre' => 'Ingeniería de Software']
        );

        $cuentas = [
            [
                'email' => 'admin@academica.test',
                'name' => 'Administrador General',
                'rol' => 'administrador',
                'carrera_id' => null,
                'sede_id' => null,
            ],
            [
                'email' => 'profesor.iti@academica.test',
                'name' => 'Profesor de TI',
                'rol' => 'profesor',
                'carrera_id' => $iti->id,
                'sede_id' => 1,
            ],
            [
                'email' => 'profesor.isw@academica.test',
                'name' => 'Profesor de Software',
                'rol' => 'profesor',
                'carrera_id' => $isw->id,
                'sede_id' => 2,
            ],
            [
                'email' => 'coordinador.iti@academica.test',
                'name' => 'Coordinador de TI',
                'rol' => 'coordinador',
                'carrera_id' => $iti->id,
                'sede_id' => 1,
            ],
        ];

        foreach ($cuentas as $cuenta) {
            $usuario = User::firstOrCreate(
                ['email' => $cuenta['email']],
                [
                    'name' => $cuenta['name'],
                    'password' => Hash::make('Password123!'),
                    'carrera_id' => $cuenta['carrera_id'],
                    'sede_id' => $cuenta['sede_id'],
                ]
            );

            $usuario->syncRoles([$cuenta['rol']]);
        }

        $estudiantes = [
            [
                'email' => 'estudiante1@academica.test',
                'nombre' => 'Ana',
                'apellido' => 'Estudiante',
                'carrera_id' => $iti->id,
                'sede_id' => 1,
                'cedula' => '1-1111-1111',
                'correo_institucional' => 'sebas@estudiantes.test',
                'activo' => true,
            ],
            [
                'email' => 'estudiante2@academica.test',
                'nombre' => 'Luis',
                'apellido' => 'Estudiante',
                'carrera_id' => $isw->id,
                'sede_id' => 2,
                'cedula' => '2-2222-2222',
                'correo_institucional' => 'jimena@estudiantes.test',
                'activo' => true,
            ],
            [
                'email' => 'estudiante3@academica.test',
                'nombre' => 'Marta',
                'apellido' => 'Estudiante',
                'carrera_id' => $iti->id,
                'sede_id' => 2,
                'cedula' => '3-3333-3333',
                'correo_institucional' => 'owen@estudiantes.test',
                'activo' => false,
            ],
            [
                'email' => 'estudiante4@academica.test',
                'nombre' => 'Carlos',
                'apellido' => 'Estudiante',
                'carrera_id' => $isw->id,
                'sede_id' => 1,
                'cedula' => '4-4444-4444',
                'correo_institucional' => 'carlos@estudiantes.test',
                'activo' => true,
            ],
        ];

        foreach ($estudiantes as $datos) {
            $usuario = User::firstOrCreate(
                ['email' => $datos['email']],
                [
                    'name' => $datos['nombre'].' '.$datos['apellido'],
                    'password' => Hash::make('Password123!'),
                    'carrera_id' => $datos['carrera_id'],
                    'sede_id' => $datos['sede_id'],
                ]
            );

            $usuario->syncRoles(['estudiante']);

            Estudiante::firstOrCreate(
                ['user_id' => $usuario->id],
                [
                    'carrera_id' => $datos['carrera_id'],
                    'sede_id' => $datos['sede_id'],
                    'cedula' => $datos['cedula'],
                    'nombre' => $datos['nombre'],
                    'apellido' => $datos['apellido'],
                    'correo_institucional' => $datos['correo_institucional'],
                    'activo' => $datos['activo'],
                ]
            );
        }
    }
}