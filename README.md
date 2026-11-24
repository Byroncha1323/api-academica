# API Académica — Taller de autorización en Laravel 13

API REST del taller ITI-621 con Sanctum, Gates, Policies, RBAC (Spatie) y ABAC por propiedad, carrera y sede. Incluye las actividades 1 a 5 y los entregables de la rúbrica. No requiere Node.js para ejecutar la API.

## Organización del repositorio

| Carpeta o archivo | Contenido |
|---|---|
| `app/Http/Controllers/Api/` | Autenticación y CRUD de estudiantes |
| `app/Models/` | Usuarios, carreras, sedes y estudiantes |
| `app/Policies/` | Autorización por propiedad, permisos, carrera y sede |
| `app/Providers/AppServiceProvider.php` | Gates de los dashboards |
| `routes/api.php` | Endpoints públicos y protegidos |
| `database/migrations/` | Estructura de las tablas |
| `database/seeders/` | Roles, permisos y datos del laboratorio |
| `tests/Feature/` | Pruebas de integración y autorización |
| `postman/` | Colección para probar la API |
| [docs/actividades-y-rubrica.md](docs/actividades-y-rubrica.md) | Actividades, reflexión y revisión de requisitos |
| [docs/evidencias/](docs/evidencias/) | Informe de verificación y resultados de ejecución |

`bootstrap/`, `config/`, `public/`, `resources/` y `storage/` conservan la estructura de Laravel. El punto de entrada para instalar, ejecutar y probar el proyecto es este README.

## Instalación local

Requiere PHP compatible con `composer.lock` (PHP 8.3 o superior, según las dependencias instaladas), Composer y las extensiones de SQLite. La verificación de este repositorio se realizó con PHP 8.5.12 y Laravel 13.43.0.

```bash
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
php artisan serve --port=8001
```

Base URL: `http://127.0.0.1:8001/api`. `.env.example` utiliza SQLite sin credenciales. `migrate --seed` conserva los datos existentes; los seeders se pueden repetir.

## Usuarios de laboratorio

Contraseña común: `Password123!`. Estas cuentas pertenecen al laboratorio local.

| Correo | Rol | Carrera | Sede |
|---|---|---|---|
| admin@academica.test | administrador | — | — |
| profesor.iti@academica.test | profesor | ITI | SC |
| profesor.isw@academica.test | profesor | ISW | CEN |
| coordinador.iti@academica.test | coordinador | ITI | SC |
| estudiante1@academica.test | estudiante (Ana, activa) | ITI | SC |
| estudiante2@academica.test | estudiante (Luis, activo) | ISW | CEN |
| estudiante3@academica.test | estudiante (María, activa) | ITI | CEN |
| estudiante4@academica.test | estudiante (Pedro, inactivo) | ISW | SC |

## Endpoints

| Método | Ruta bajo `/api` | Control |
|---|---|---|
| POST | /register | Público; cuenta sin rol ni permisos asignados por el cliente |
| POST | /login | Público; devuelve token Sanctum |
| GET | /me | Token; usuario, carrera, sede, roles y permisos directos |
| POST | /logout | Token; revoca únicamente el token actual |
| GET | /admin/dashboard | Gate `access-admin-dashboard` |
| GET | /profesor/dashboard | Gate `access-professor-dashboard` |
| GET | /estudiantes | Policy `viewAny`, permiso `estudiantes.ver`, paginación de 10 |
| POST | /estudiantes | Middleware `permission:estudiantes.crear` y Policy `create` |
| GET | /estudiantes/{estudiante} | Policy `view` |
| PUT/PATCH | /estudiantes/{estudiante} | Policy `update`, permiso y ABAC |
| DELETE | /estudiantes/{estudiante} | Policy `delete` |

Las rutas protegidas requieren `Accept: application/json` y `Authorization: Bearer <token>`. No hay rutas CRUD duplicadas. En `/me`, los permisos heredados pertenecen a los roles; `permissions` contiene los permisos asignados directamente al usuario, como en el ejemplo del taller.

El listado general sigue `viewAny` del documento: los cuatro roles sembrados tienen `estudiantes.ver`. El detalle exige propiedad para estudiantes y misma carrera para profesores/coordinadores. La actualización de profesores y coordinadores exige permiso, misma carrera y misma sede; el administrador tiene acceso general con sus permisos. Un estudiante puede ver su registro propio aunque esté inactivo, y nunca un registro ajeno.

El alta de estudiantes requiere `user_id`, `carrera_id`, `sede_id`, `cedula`, `nombre`, `apellido` y `correo_institucional`; `activo` es opcional. Los IDs deben existir. La actualización no acepta cambios de `user_id`.

## Verificación y entregables

```bash
php artisan test
php artisan route:list --path=api
vendor/bin/pint --dirty --test
```

Las pruebas usan SQLite en memoria y no modifican la base local.

- [Colección Postman](postman/API-Academica.postman_collection.json): importar y ejecutar en orden con Collection Runner. Incluye 53 solicitudes con aserciones, obtiene los IDs y tokens durante la ejecución y cubre 401/403, ambos Gates, CRUD, RBAC y los cuatro escenarios ABAC. `base_url` se puede cambiar en las variables de colección.
- La colección se puede ejecutar por terminal: `npx --yes newman run postman/API-Academica.postman_collection.json`. Necesita el servidor local y los seeders. Crea una cuenta sin rol para probar registro y un estudiante temporal que elimina al terminar; actualiza el nombre de Ana y restaura el estado activo de María. Usar la base de práctica.
- [Actividades, reflexión y revisión de rúbrica](docs/actividades-y-rubrica.md).
- [Evidencias de ejecución](docs/evidencias/): resultados reproducibles de las pruebas y solicitudes, sin tokens personales.

Para entregar, incluir el código y los entregables versionados. Excluir `vendor`, `.env`, la base SQLite y tokens personales; ya están ignorados por Git. No se necesita configurar frontend ni servicios externos.
