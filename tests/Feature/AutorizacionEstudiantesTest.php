<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Verificación de autenticación, Gates, Policies, RBAC y ABAC a través de la API.
 * RefreshDatabase utiliza la conexión SQLite en memoria definida en phpunit.xml.
 */
class AutorizacionEstudiantesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara una base aislada y carga los roles y datos antes de cada caso.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Obtiene una cuenta de laboratorio por el prefijo de su correo, sin asumir su ID.
     */
    private function usuario(string $correo): User
    {
        return User::where('email', $correo.'@academica.test')->firstOrFail();
    }

    /**
     * Genera un token Sanctum real y lo envía como Bearer en las solicitudes siguientes.
     */
    private function autenticar(string $correo): static
    {
        return $this->withToken($this->usuario($correo)->createToken('prueba')->plainTextToken);
    }

    /**
     * Obtiene el registro académico mediante su usuario, sin asumir IDs de estudiantes.
     */
    private function registro(int $numero): Estudiante
    {
        return $this->usuario('estudiante'.$numero)->estudiante;
    }

    /**
     * Casos de entrada a ambos Gates: cada fila indica usuario, dashboard y estado esperado.
     */
    public static function dashboards(): array
    {
        return [
            ['admin', 'admin', 200], ['profesor.iti', 'admin', 403],
            ['estudiante1', 'admin', 403], ['coordinador.iti', 'admin', 403],
            ['admin', 'profesor', 403], ['profesor.iti', 'profesor', 200],
            ['estudiante1', 'profesor', 403], ['coordinador.iti', 'profesor', 403],
        ];
    }

    /**
     * Comprueba que solo el rol del dashboard correspondiente recibe HTTP 200.
     */
    #[DataProvider('dashboards')]
    public function test_gates(string $correo, string $dashboard, int $estado): void
    {
        $this->autenticar($correo)->getJson('/api/'.$dashboard.'/dashboard')->assertStatus($estado);
    }

    /**
     * Rechaza las rutas de lectura y escritura sin token y el perfil con token inválido.
     */
    public function test_rutas_protegidas_requieren_token(): void
    {
        foreach (['/me', '/admin/dashboard', '/profesor/dashboard', '/estudiantes', '/estudiantes/1'] as $ruta) {
            $this->getJson('/api'.$ruta)->assertUnauthorized();
        }
        $this->postJson('/api/estudiantes', [])->assertUnauthorized();
        $this->putJson('/api/estudiantes/1', [])->assertUnauthorized();
        $this->deleteJson('/api/estudiantes/1')->assertUnauthorized();
        $this->postJson('/api/logout')->assertUnauthorized();
        $this->withToken('token-invalido')->getJson('/api/me')->assertUnauthorized();
    }

    /**
     * Envía campos de privilegios al registro y verifica que no se asignan.
     * Comprueba el hash de contraseña y que el token generado permite acceder al perfil.
     */
    public function test_registro_no_permite_autoasignar_privilegios(): void
    {
        $respuesta = $this->postJson('/api/register', [
            'name' => 'Nuevo usuario', 'email' => 'nuevo@test.test',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
            'role' => 'administrador', 'roles' => ['administrador'],
            'permissions' => ['estudiantes.crear'], 'carrera_id' => 1, 'sede_id' => 1,
        ])->assertCreated()->assertJsonStructure(['user', 'token']);
        $user = User::where('email', 'nuevo@test.test')->firstOrFail();
        $this->assertCount(0, $user->roles);
        $this->assertCount(0, $user->permissions);
        $this->assertNull($user->carrera_id);
        $this->assertNull($user->sede_id);
        $this->assertTrue(password_verify('Password123!', $user->password));
        $this->withToken($respuesta->json('token'))->getJson('/api/me')->assertOk();
    }

    /**
     * Comprueba login y relaciones del perfil; revoca un token y conserva otro de la cuenta.
     */
    public function test_login_perfil_y_revocacion_del_token_actual(): void
    {
        $respuesta = $this->postJson('/api/login', [
            'email' => 'profesor.iti@academica.test', 'password' => 'Password123!',
        ])->assertOk();
        $token = $respuesta->json('token');
        $otroToken = $this->usuario('profesor.iti')->createToken('otro')->plainTextToken;
        $this->withToken($token)->getJson('/api/me')->assertOk()
            ->assertJsonPath('user.roles.0.name', 'profesor')
            ->assertJsonPath('user.carrera.codigo', 'ITI')
            ->assertJsonPath('user.sede.codigo', 'SC');
        $this->withToken($token)->postJson('/api/logout')->assertOk();
        // El guard debe resolver de nuevo el token en la siguiente solicitud.
        app('auth')->forgetGuards();
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
        $this->withToken($otroToken)->getJson('/api/me')->assertOk();
    }

    /**
     * Verifica 422 por entradas incompletas, correo duplicado, contraseña y login inválidos.
     */
    public function test_validacion_de_registro_y_credenciales(): void
    {
        $this->postJson('/api/register', [])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
        $this->postJson('/api/register', [
            'name' => 'Prueba', 'email' => 'admin@academica.test',
            'password' => 'corta', 'password_confirmation' => 'distinta',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->postJson('/api/login', ['email' => 'admin@academica.test', 'password' => 'incorrecta'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    /**
     * Casos de consulta de detalles según rol, propietario y carrera, incluidos inactivos.
     */
    public static function consultas(): array
    {
        return [
            ['admin', 1, 200], ['estudiante1', 1, 200], ['estudiante1', 2, 403],
            ['profesor.iti', 1, 200], ['profesor.iti', 2, 403],
            ['coordinador.iti', 1, 200], ['coordinador.iti', 2, 403],
            ['estudiante1', 3, 403], ['estudiante1', 4, 403], ['estudiante4', 4, 200],
        ];
    }

    /**
     * Ejecuta cada combinación de consulta y compara su estado HTTP esperado.
     */
    #[DataProvider('consultas')]
    public function test_consulta_segun_propiedad_y_carrera(string $correo, int $numero, int $estado): void
    {
        $registro = $this->registro($numero);
        $this->autenticar($correo)->getJson('/api/estudiantes/'.$registro->id)->assertStatus($estado);
    }

    /**
     * Inactiva un registro ajeno de la misma carrera y otro propio: deniega el ajeno
     * y permite el propio, demostrando que la propiedad prevalece sobre el estado.
     */
    public function test_estudiante_no_consulta_registro_inactivo_ajeno_de_su_carrera(): void
    {
        $registro = $this->registro(3);
        $registro->update(['activo' => false]);
        $this->autenticar('estudiante1')->getJson('/api/estudiantes/'.$registro->id)->assertForbidden();
        $propio = $this->registro(1);
        $propio->update(['activo' => false]);
        $this->getJson('/api/estudiantes/'.$propio->id)->assertOk();
    }

    /**
     * Combina profesor y coordinador con las cuatro parejas de carrera/sede.
     * Añade la denegación al estudiante y el acceso general del administrador.
     */
    public static function modificaciones(): array
    {
        $casos = [];
        foreach (['profesor.iti', 'coordinador.iti'] as $rol) {
            foreach ([1 => 200, 3 => 403, 4 => 403, 2 => 403] as $numero => $estado) {
                $casos[] = [$rol, $numero, $estado];
            }
        }
        $casos[] = ['estudiante1', 1, 403];
        $casos[] = ['admin', 2, 200];

        return $casos;
    }

    /**
     * Verifica el estado HTTP y el valor persistido: una denegación no debe modificar datos.
     */
    #[DataProvider('modificaciones')]
    public function test_abac_carrera_y_sede(string $correo, int $numero, int $estado): void
    {
        $registro = $this->registro($numero);
        $this->autenticar($correo)->putJson('/api/estudiantes/'.$registro->id, ['nombre' => 'Actualizado'])->assertStatus($estado);
        $this->assertSame($estado === 200 ? 'Actualizado' : $registro->nombre, $registro->fresh()->nombre);
    }

    /**
     * Quita editar y luego la sede: ninguno de esos escenarios debe permitir la modificación.
     */
    public function test_permiso_editar_y_atributos_no_nulos_son_necesarios(): void
    {
        $registro = $this->registro(1);
        $profesor = $this->usuario('profesor.iti');
        $profesor->roles->first()->revokePermissionTo('estudiantes.editar');
        $this->autenticar('profesor.iti')->patchJson('/api/estudiantes/'.$registro->id, ['nombre' => 'Cambio'])->assertForbidden();
        $profesor->roles->first()->givePermissionTo('estudiantes.editar');
        $profesor->update(['sede_id' => null]);
        app('auth')->forgetGuards();
        $this->patchJson('/api/estudiantes/'.$registro->id, ['nombre' => 'Cambio'])->assertForbidden();
    }

    /**
     * Recorre el CRUD completo y prueba unicidad, validación y propiedad inmutable.
     * Comprueba la eliminación en la base y el 404 posterior.
     */
    public function test_crud_administrador_y_validaciones(): void
    {
        $user = User::factory()->create();
        $base = $this->registro(1);
        $datos = [
            'user_id' => $user->id, 'carrera_id' => $base->carrera_id, 'sede_id' => $base->sede_id,
            'cedula' => '9-9999-9999', 'nombre' => 'Carlos', 'apellido' => 'Ejemplo',
            'correo_institucional' => 'carlos@estudiantes.test', 'activo' => true,
        ];
        $this->autenticar('admin')->postJson('/api/estudiantes', [])->assertUnprocessable();
        $id = $this->postJson('/api/estudiantes', $datos)->assertCreated()->json('id');
        $this->postJson('/api/estudiantes', $datos)->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'cedula', 'correo_institucional']);
        $this->patchJson('/api/estudiantes/'.$id, ['nombre' => 'Carlos María', 'user_id' => $base->user_id])->assertOk();
        $this->assertDatabaseHas('estudiantes', ['id' => $id, 'user_id' => $user->id, 'nombre' => 'Carlos María']);
        $this->patchJson('/api/estudiantes/'.$id, ['cedula' => $base->cedula])->assertUnprocessable();
        $this->getJson('/api/estudiantes/'.$id)->assertOk();
        $this->deleteJson('/api/estudiantes/'.$id)->assertOk();
        $this->assertDatabaseMissing('estudiantes', ['id' => $id]);
        $this->getJson('/api/estudiantes/'.$id)->assertNotFound();
    }

    /**
     * Roles del taller que no reciben permisos de creación ni eliminación.
     */
    public static function rolesSinCreacion(): array
    {
        return [['profesor.iti'], ['estudiante1'], ['coordinador.iti']];
    }

    /**
     * Comprueba 403 antes de validar datos de alta y al intentar eliminar un registro.
     */
    #[DataProvider('rolesSinCreacion')]
    public function test_rbac_deniega_creacion_y_eliminacion(string $correo): void
    {
        $this->autenticar($correo)->postJson('/api/estudiantes', [])->assertForbidden();
        $this->deleteJson('/api/estudiantes/'.$this->registro(1)->id)->assertForbidden();
    }

    /**
     * Los cuatro roles con ver reciben el listado general de los cuatro registros.
     */
    public function test_listado_paginado_para_roles_con_permiso(): void
    {
        foreach (['admin', 'profesor.iti', 'estudiante1', 'coordinador.iti'] as $correo) {
            app('auth')->forgetGuards();
            $this->autenticar($correo)->getJson('/api/estudiantes')->assertOk()->assertJsonCount(4, 'data');
        }
    }

    /**
     * Repite los seeders sin duplicados y comprueba los permisos heredados del coordinador.
     */
    public function test_seeders_repetibles_y_permisos_coordinador(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('users', 8);
        $this->assertDatabaseCount('estudiantes', 4);
        $this->assertDatabaseCount('roles', 4);
        $this->assertDatabaseCount('permissions', 4);
        $this->assertEqualsCanonicalizing(['estudiantes.ver', 'estudiantes.editar'],
            $this->usuario('coordinador.iti')->getAllPermissions()->pluck('name')->all());
    }
}
