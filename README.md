# API Académica - Taller de Mecanismos de Autorización en Laravel 13

**Curso:** Tecnologías y Sistemas Web III (ITI-621)  
**Tema:** Seguridad y control de acceso en servicios web (Sanctum, Gates, Policies, RBAC y ABAC)  
**Tecnologías:** Laravel 13, PHP 8.3+, MySQL (LocalWP) / SQLite, Laravel Sanctum, Spatie Laravel Permission y Bruno.

---

## Estructura de Entregables del Proyecto

- `app/Models/`: Modelos `User` (con `HasApiTokens` y `HasRoles`), `Carrera`, `Sede` y `Estudiante`.
- `app/Http/Controllers/Api/`:
  - `AuthController.php`: Registro público seguro (`register`), inicio de sesión (`login`), perfil con relaciones/roles/permisos (`me`) y revocación de token (`logout`).
  - `EstudianteController.php`: CRUD completo protegido mediante `Gate::authorize()` y validación que impide cambiar `user_id`.
- `app/Providers/AppServiceProvider.php`: Definición de los Gates `access-admin-dashboard` y `access-professor-dashboard`.
- `app/Policies/EstudiantePolicy.php`: Reglas RBAC y ABAC (propiedad del registro, bloqueo de registros inactivos ajenos, validación combinada de `carrera_id` y `sede_id`).
- `bootstrap/app.php`: Registro de los alias de middleware de Spatie (`permission`, `role`, `role_or_permission`).
- `database/migrations/`: Migraciones de `carreras`, `sedes`, llaves foráneas en `users`, `estudiantes`, `personal_access_tokens` y tablas de Spatie Permission.
- `database/seeders/`:
  - `RolesYPermisosSeeder.php`: Creación de permisos (`estudiantes.ver`, `estudiantes.crear`, `estudiantes.editar`, `estudiantes.eliminar`) y roles (`administrador`, `profesor`, `coordinador`, `estudiante`).
  - `DatosPruebaSeeder.php`: Carreras (`ITI`, `ISW`), sedes (`SC`, `CEN`) y usuarios de prueba para todos los escenarios.
- `routes/api.php`: Rutas explícitas bajo `auth:sanctum` y middleware `permission:estudiantes.crear`.
- `tests/Feature/AutorizacionEstudiantesTest.php`: Suite de 8 pruebas automatizadas de Feature verificando Gates, Policies, RBAC y los 4 escenarios ABAC.
- `bruno/`: Colección nativa de Bruno (`.bru`) organizada en 4 carpetas (`1-Auth`, `2-Dashboards-Gates`, `3-Estudiantes-CRUD`, `4-Casos-Error-401-403`) y entornos `LocalWP` / `Dominio-LocalWP`.
- `bruno-collection-api-academica.json`: Archivo JSON exportado de la colección de Bruno.
- `ENTREGABLE_REFLEXION_Y_ACTIVIDADES.md`: Desarrollo de las actividades 1 a 5, tablas de casos de prueba, evidencias de ejecución y respuestas de reflexión de seguridad.

---

## Instalación y Ejecución Rápida

1. Instalar dependencias de Composer:
   ```bash
   composer install
   ```
2. Copiar el archivo de entorno y generar la clave de aplicación:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. Ejecutar migraciones y sembrar roles, permisos y datos de prueba:
   ```bash
   php artisan migrate:fresh --seed
   ```
4. Iniciar el servidor de desarrollo:
   ```bash
   php artisan serve
   ```
5. Ejecutar las pruebas automatizadas:
   ```bash
   php artisan test --filter=AutorizacionEstudiantesTest
   ```

---

## Credenciales de Prueba (Contraseña para todos: `Password123!`)

| Correo | Rol | Carrera | Sede |
|---|---|---|---|
| `admin@academica.test` | `administrador` | N/A | N/A |
| `profesor.iti@academica.test` | `profesor` | `ITI` | `SC` (San Carlos) |
| `profesor.isw@academica.test` | `profesor` | `ISW` | `CEN` (Central) |
| `coordinador.iti@academica.test` | `coordinador` | `ITI` | `SC` (San Carlos) |
| `estudiante1@academica.test` | `estudiante` (Ana - Activa) | `ITI` | `SC` (San Carlos) |
| `estudiante2@academica.test` | `estudiante` (Luis - Activo) | `ISW` | `CEN` (Central) |
| `estudiante3@academica.test` | `estudiante` (María - Activa) | `ITI` | `CEN` (Central) |
| `estudiante4@academica.test` | `estudiante` (Pedro - Inactivo) | `ISW` | `SC` (San Carlos) |
