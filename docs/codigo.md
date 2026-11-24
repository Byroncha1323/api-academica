# Documentación del código

Esta guía explica cómo se conectan los archivos del proyecto. El código incluye comentarios en español en sus clases, métodos, reglas de autorización y solicitudes de Bruno. Los comentarios describen la implementación actual; no cambian los permisos ni añaden operaciones.

Para ejecutar el proyecto, empieza por el [README](../README.md). Para relacionarlo con el trabajo evaluado, consulta las [actividades y rúbrica](actividades-y-rubrica.md).

## Recorrido de una solicitud

Ejemplo: un profesor intenta modificar un estudiante.

1. Bruno envía `PUT /api/estudiantes/{id}` con un token Bearer y un cuerpo JSON.
2. [public/index.php](../public/index.php) carga Composer y arranca la aplicación de [bootstrap/app.php](../bootstrap/app.php).
3. [routes/api.php](../routes/api.php) relaciona método y URL con una acción. `auth:sanctum` identifica al usuario. Sin identidad válida, la respuesta es `401`.
4. Laravel busca el estudiante indicado en la ruta mediante el enlace de modelos. Si no existe, devuelve `404`.
5. [EstudianteController](../app/Http/Controllers/Api/EstudianteController.php) ejecuta `Gate::authorize('update', $estudiante)`.
6. Laravel consulta [EstudiantePolicy::update](../app/Policies/EstudiantePolicy.php). Se necesita `estudiantes.editar` y, para profesor/coordinador, carrera y sede coincidentes y no nulas. Una denegación produce `403`.
7. Si se autoriza, el controlador valida los datos recibidos. Los datos inválidos generan `422`.
8. [Estudiante](../app/Models/Estudiante.php) actualiza solo los campos validados y se devuelve el registro en JSON con `200`.

El orden evita guardar datos de una operación denegada. La Policy compara los atributos existentes antes de la actualización; las reglas de negocio adicionales para cambiar de carrera o sede no forman parte de este taller.

## Controladores

### AuthController

[app/Http/Controllers/Api/AuthController.php](../app/Http/Controllers/Api/AuthController.php)

| Método | Entrada y comportamiento | Salida |
|---|---|---|
| `register` | Nombre, correo único, contraseña de ocho caracteres como mínimo y confirmación. Crea una cuenta sin rol y un token. Ignora privilegios enviados por el cliente. | `201`, `message`, `user`, `token` |
| `login` | Busca por email y comprueba la contraseña con `Hash::check`. Genera otro token si las credenciales son correctas. | `200`, `message`, `user`, `token`; credenciales inválidas: `422` |
| `me` | Obtiene la cuenta del token y carga carrera, sede, roles y permisos directos. | `200`, `user` |
| `logout` | Elimina `currentAccessToken()`, sin eliminar la cuenta ni sus demás tokens. | `200`, `message` |

`Hash::make` y el cast `hashed` evitan almacenar una contraseña en texto plano. Sanctum guarda un hash del token y entrega `plainTextToken` al cliente al crearlo. El cliente lo presenta en `Authorization: Bearer <token>`.

La relación `permissions` del perfil contiene asignaciones directas. Los permisos de un rol se heredan; se consultan con `getAllPermissions()`. Tener esa relación vacía no significa necesariamente carecer de permisos.

### EstudianteController

[app/Http/Controllers/Api/EstudianteController.php](../app/Http/Controllers/Api/EstudianteController.php)

| Método | Autorización | Operación |
|---|---|---|
| `index` | `viewAny`, con la clase `Estudiante` | Lista diez registros por página, con carrera, sede y usuario. |
| `show` | `view`, con el registro | Carga sus relaciones y devuelve un detalle. |
| `store` | `create`, con la clase | Valida relaciones, formatos y unicidad; crea y devuelve `201`. |
| `update` | `update`, con el registro | Valida únicamente los campos presentes y guarda; devuelve `200`. |
| `destroy` | `delete`, con el registro | Elimina el registro académico y devuelve mensaje `200`. |

`with` carga relaciones al construir la consulta; `load` las incorpora a un modelo ya obtenido. Evitan que serializar relaciones requiera una consulta separada para cada fila.

`user_id` es obligatorio y único en el alta, pero no se admite entre los campos de actualización. El cliente no cambia la propiedad del registro con este endpoint. `sometimes` permite una modificación parcial. `Rule::unique(...)->ignore($estudiante->id)` permite conservar un valor único que ya pertenece a ese mismo registro.

[Controller.php](../app/Http/Controllers/Controller.php) es la base común. Se mantiene sin helpers adicionales porque los controladores llaman a `Gate` explícitamente.

## Gates y Policy

### Acciones generales

[AppServiceProvider](../app/Providers/AppServiceProvider.php) define los Gates en `boot`. `register` es el punto de registro de servicios y no necesita configuraciones adicionales en el taller.

| Gate | Regla | Ruta |
|---|---|---|
| `access-admin-dashboard` | Rol administrador | `GET /api/admin/dashboard` |
| `access-professor-dashboard` | Rol profesor | `GET /api/profesor/dashboard` |

No existe una excepción global para administrador en el segundo Gate. Cada ruta llama a `Gate::authorize` después de autenticar.

### Acciones sobre estudiantes

[EstudiantePolicy](../app/Policies/EstudiantePolicy.php) se descubre automáticamente por nombre y ubicación. `Gate::authorize` también puede ejecutar una Policy cuando recibe la clase o instancia del modelo.

| Método | Regla implementada |
|---|---|
| `viewAny` | Requiere `estudiantes.ver`. Lista la colección general; no filtra por propietario. |
| `view` | Administrador ve cualquier detalle. Estudiante solo el propio, activo o inactivo. Profesor/coordinador requieren ver y misma carrera. No se exige sede para consultar. |
| `create` | Requiere `estudiantes.crear`. |
| `update` | Primero exige `estudiantes.editar`. Administrador tiene alcance general. Profesor/coordinador requieren misma carrera y misma sede, con atributos del usuario no nulos. Otros casos se deniegan. |
| `delete` | Requiere `estudiantes.eliminar`. |

RBAC se expresa con roles y permisos. ABAC utiliza los atributos del usuario y del recurso. En modificación, permiso, carrera y sede deben cumplirse a la vez: `&&` exige todas las condiciones. `===` compara tipo y valor de los IDs. `!== null` evita aceptar una adscripción académica ausente.

Solo los roles sembrados reciben las capacidades de la tabla del README. Los métodos `create` y `delete` consultan permisos, no un nombre de rol escrito directamente. Una cuenta pública recién registrada no tiene rol ni permiso de listado.

## Modelos y relaciones

| Modelo | Campos y finalidad | Relaciones |
|---|---|---|
| [User](../app/Models/User.php) | Cuenta para autenticarse; nombre, email, contraseña, carrera y sede. `HasApiTokens` aporta tokens; `HasRoles` roles/permisos; `HasFactory` datos de prueba; `Notifiable` notificaciones del framework. | Pertenece a carrera y sede; tiene un registro académico. |
| [Estudiante](../app/Models/Estudiante.php) | Registro académico, propietario, carrera, sede, cédula, nombres, correo institucional y estado. `activo` se convierte a booleano. | Pertenece a usuario, carrera y sede. |
| [Carrera](../app/Models/Carrera.php) | Catálogo con nombre y código. | Tiene muchos usuarios y estudiantes. |
| [Sede](../app/Models/Sede.php) | Catálogo institucional con nombre y código. | Tiene muchos usuarios y estudiantes. |

`$fillable` enumera campos asignables por Eloquent, pero no sustituye la validación ni la autorización. `$hidden` de User excluye contraseña y token de recuerdo del JSON. Los casts convierten tipos y procesan la contraseña. La cuenta y su registro académico son entidades distintas: puede existir un usuario sin registro de estudiante.

## Migraciones

Las migraciones de [database/migrations](../database/migrations/) definen `up` para aplicar cambios y `down` para revertirlos. La reversión puede eliminar datos; no se usa para comprobar la API habitual.

| Archivo o sufijo | Responsabilidad |
|---|---|
| `create_users_table` | Cuentas con email único, tokens de recuperación y sesiones del framework. |
| `create_cache_table` | Valores de caché y bloqueos con expiración. |
| `create_jobs_table` | Trabajos, lotes y errores de colas; no hay jobs propios de la actividad. |
| `create_personal_access_tokens_table` | Tokens de Sanctum con propietario polimórfico, hash único, abilities, uso y expiración. |
| `create_permission_tables` | Roles, permisos, asignaciones directas a modelos y permisos heredados de roles. Usa los nombres de `config/permission.php`. |
| `create_carreras_table` | Carreras con nombre y código únicos. |
| `create_sedes_table` | Sedes con nombre y código únicos. |
| `add_carrera_and_sede_to_users_table` | Relaciones opcionales del usuario; eliminar el catálogo deja esos atributos nulos. |
| `create_estudiantes_table` | Registro por usuario, carrera, sede, campos únicos y estado activo por defecto. |

En estudiantes, `user_id` único permite como máximo un registro académico por cuenta. La clave con `cascadeOnDelete` elimina el registro si se borra al usuario. Carrera y sede utilizan `restrictOnDelete` para impedir borrar catálogos referenciados por estudiantes. `sede_id` admite nulo en la tabla, mientras que el alta por API lo exige: esquema y validación cumplen responsabilidades diferentes.

## Seeders y factory

| Archivo | Qué hace |
|---|---|
| [DatabaseSeeder](../database/seeders/DatabaseSeeder.php) | Ejecuta roles/permisos antes de crear cuentas que los necesitan. |
| [RolesYPermisosSeeder](../database/seeders/RolesYPermisosSeeder.php) | Crea o reutiliza cuatro permisos y cuatro roles; sincroniza sus asignaciones y limpia caché antes/después. |
| [DatosPruebaSeeder](../database/seeders/DatosPruebaSeeder.php) | Carga dos carreras, dos sedes, ocho cuentas y cuatro registros que cubren las combinaciones ABAC. |
| [UserFactory](../database/factories/UserFactory.php) | Genera cuentas de pruebas con email único y contraseña con hash. Permite el estado `unverified`. No asigna roles. |

`firstOrCreate` devuelve un registro existente o lo crea si falta. No actualiza la contraseña de una cuenta ya creada. `syncRoles` y `syncPermissions` establecen las asignaciones previstas. Las claves se toman de modelos reales en lugar de escribir IDs fijos.

Los roles/permisos se crean con `guard_name=web`, que es el ámbito de Spatie para estos usuarios. Eso no cambia `auth:sanctum` de las rutas ni significa que el cliente deba autenticarse con una sesión web.

## Rutas y arranque

| Archivo | Qué configura |
|---|---|
| [public/index.php](../public/index.php) | Entrada HTTP, mantenimiento, autoload de Composer y atención de la solicitud. |
| [artisan](../artisan) | Entrada de comandos; inicia Laravel y devuelve el estado del comando. |
| [bootstrap/app.php](../bootstrap/app.php) | Rutas web, API, consola, salud `/up`, alias de Spatie y manejo de excepciones del framework. |
| [bootstrap/providers.php](../bootstrap/providers.php) | Lista los proveedores propios, incluido el que define Gates. |
| [routes/api.php](../routes/api.php) | Dos rutas públicas y el grupo con Sanctum. La creación añade `permission:estudiantes.crear`; el controlador también llama a su Policy. |
| [routes/web.php](../routes/web.php) | Bienvenida del framework; no implementa una interfaz de gestión académica. |
| [routes/console.php](../routes/console.php) | Conserva `inspire`, ejemplo de consola sin relación con la autorización. |

El contrato completo de endpoints, métodos y códigos esperados está en el README. `{estudiante}` coincide con el parámetro tipado del controlador para activar el enlace de modelos.

## Configuración

Cada archivo de [config](../config/) devuelve opciones; los comentarios iniciales explican su papel en este taller. No se necesitan credenciales externas para usar SQLite y tokens personales.

| Archivo | Uso |
|---|---|
| `app.php` | Nombre, entorno, idioma y clave de aplicación. |
| `auth.php` | Guards, modelo proveedor y opciones de recuperación de contraseña. |
| `sanctum.php` | Guards, expiración y configuración de cookies/sesiones del paquete. El taller utiliza Bearer. |
| `permission.php` | Modelos/tablas de roles, permisos, asignaciones, opciones y caché de Spatie. |
| `database.php` | Conexiones y repositorio de migraciones. `.env.example` selecciona SQLite. |
| `cache.php` | Almacenamiento de caché; en local se utiliza database y en pruebas array. |
| `session.php` | Sesiones del framework; no reemplaza el token de las solicitudes de Bruno. |
| `logging.php` | Canales y niveles de logs. |
| `filesystems.php` | Discos; la actividad no añade carga de archivos. |
| `queue.php` | Conexiones para trabajos diferidos; la actividad no añade jobs. |
| `mail.php` | Transportes de correo; la actividad no envía mensajes. |
| `services.php` | Opciones de integraciones externas del esqueleto, no utilizadas por el taller. |

`.env.example` es una plantilla sin secretos reales. `.env` contiene la configuración local, no se versiona. `APP_URL` no inicia el servidor: el puerto se elige con `php artisan serve --port=8001` y debe coincidir con el entorno de Bruno.

## Pruebas de Laravel

[AutorizacionEstudiantesTest](../tests/Feature/AutorizacionEstudiantesTest.php) contiene comentarios sobre cada caso, sus auxiliares y proveedores de datos.

- `setUp` carga un entorno de datos antes de cada caso. `RefreshDatabase` y [phpunit.xml](../phpunit.xml) utilizan SQLite en memoria.
- `usuario`, `registro` y `autenticar` obtienen fixtures y generan un token real para enviar en las solicitudes.
- `dashboards`, `consultas`, `modificaciones` y `rolesSinCreacion` alimentan los casos parametrizados. Cada combinación se ejecuta como prueba independiente.
- Se prueban autenticación, privilegios rechazados en el registro, Gates, propiedad y estado, permisos, ABAC, validación, CRUD y seeders.
- Los tests de cambios comparan también el dato persistido: no basta con recibir un estado si una denegación deja cambios guardados.
- `forgetGuards()` obliga al entorno de pruebas a resolver otra vez la identidad al cambiar de token o revocarlo entre solicitudes de un mismo test.

[tests/TestCase.php](../tests/TestCase.php) inicia la aplicación para las pruebas Feature. Los `ExampleTest` conservados comprueban la bienvenida web y una aserción unitaria básica; no representan autorización. La suite completa verificada cuenta 42 pruebas y 116 aserciones.

## Colección de Bruno

La [guía de Bruno](../bruno/README.md) explica cómo abrir o ejecutar la colección. Cada una de las 53 solicitudes incluye un bloque `docs`, visible como documentación en Bruno, con método, ruta, estado esperado y dependencias.

| Bloque o archivo | Finalidad |
|---|---|
| `bruno.json` | Identifica nombre y formato de la colección. |
| `environments/Local.bru` | Define `base_url`, sin tokens guardados. |
| `folder.bru` | Identifica y ordena las siete carpetas. |
| `meta` | Nombre, tipo HTTP y secuencia de una solicitud. |
| `get`, `post`, `put`, `patch`, `delete` | Método, URL y modos de cuerpo/autenticación. |
| `headers` y `auth:bearer` | Encabezados y token de solicitudes protegidas. |
| `body:json` | Datos enviados y marcadores resueltos durante la ejecución. |
| `script:pre-request` | Genera el correo único de registro antes de enviarlo. |
| `tests` | Comprueba HTTP y los roles; guarda temporalmente tokens e IDs para solicitudes posteriores. |
| `docs` | Explicación de la solicitud; no se ejecuta como JavaScript. |

`bru.setVar` crea variables de ejecución que no se escriben en Local.bru. Las carpetas siguen el orden autenticación, Gates, Policies, ABAC, RBAC, CRUD y sesión. La ejecución debe ser secuencial porque varias solicitudes dependen de las anteriores. Hay 55 tests; `Assertions: 0/0` es correcto porque las comprobaciones se escriben en `tests`.

## Recursos y herramientas de desarrollo

| Archivo | Qué hace |
|---|---|
| [resources/views/welcome.blade.php](../resources/views/welcome.blade.php) | Plantilla inicial de Laravel; conserva sus recursos y enlaces del esqueleto. |
| [resources/js/app.js](../resources/js/app.js) | Entrada de JavaScript; carga bootstrap.js. |
| [resources/js/bootstrap.js](../resources/js/bootstrap.js) | Expone Axios y el encabezado AJAX, sin asignar tokens ni permisos. |
| [resources/css/app.css](../resources/css/app.css) | Tailwind, fuentes y búsqueda de clases. |
| [vite.config.js](../vite.config.js) | Compilación de CSS/JS y recarga; no se necesita para ejecutar la API. |
| [package.json](../package.json) | Scripts `dev`/`build` y dependencias del frontend heredado. El CLI de Bruno se ejecuta con npx según el README. |
| [composer.json](../composer.json) | Dependencias PHP, autoload de clases y scripts de desarrollo del esqueleto. |
| [composer.lock](../composer.lock) | Fija las versiones concretas que instala Composer. No se comenta ni modifica a mano. |

Los scripts `setup` y `dev` de Composer también contemplan frontend, colas y logs del esqueleto. Para esta API sigue los comandos específicos del README, que evitan instalar recursos de frontend innecesarios. `composer test` limpia configuración y ejecuta las pruebas.

Los archivos JSON se explican aquí porque su formato no permite comentarios. `.gitignore` evita versionar dependencias, entorno y otros artefactos locales; `database/.gitignore` excluye SQLite. `.editorconfig` y `.gitattributes` definen convenciones de edición y Git. `public/.htaccess` conserva las reglas de entrada del servidor Apache; `robots.txt` y `favicon.ico` son recursos del esqueleto. Los archivos `.gitignore` de almacenamiento permiten conservar carpetas vacías sin incluir datos generados.

## Comprobar que la documentación conserva el funcionamiento

Desde la raíz:

```bash
php artisan test
vendor/bin/pint --dirty --test
php artisan route:list --path=api
```

Con la API funcionando en otra terminal, desde `bruno/`:

```bash
npx --yes @usebruno/cli@4.3.0 run --env Local -r
```

Resultados esperados: 42 pruebas de Laravel, 12 rutas API, 53 solicitudes y 55 tests de Bruno aprobados. Los reportes de ejecución están en [evidencias](evidencias/).
