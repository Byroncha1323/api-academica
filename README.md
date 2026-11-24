# API Académica — Actividad 02: autorización en Laravel 13

API REST para gestionar estudiantes y comprobar quién puede consultar, crear, modificar o eliminar sus registros. Implementa autenticación con Sanctum, Gates para los dashboards, permisos por rol con Spatie (RBAC) y restricciones por propiedad, carrera y sede (ABAC).

Al abrir `http://127.0.0.1:8001` en el navegador se muestra la bienvenida de Laravel. **Para comprobar la actividad, ejecuta las pruebas de esta guía.** Las funciones de la API se encuentran bajo `/api`.

## Por dónde empezar

- **Primera vez con el proyecto:** sigue [Instalación](#1-instalación-solo-la-primera-vez).
- **Ya está instalado:** sigue [Iniciar la API](#2-iniciar-la-api) y [Comprobar todo](#3-comprobar-todo-desde-la-terminal).
- **Prefieres Bruno:** sigue [Probar con Bruno](#4-probar-con-bruno-opcional).
- **Aparece un error:** consulta [Problemas frecuentes](#problemas-frecuentes).
- **Quieres entender el código:** abre la [documentación del código](docs/codigo.md), con el recorrido de las solicitudes y la explicación de cada archivo.
- **Quieres revisar la entrega:** abre las [actividades y rúbrica](docs/actividades-y-rubrica.md) y las [evidencias](docs/evidencias/).

## 1. Instalación (solo la primera vez)

Necesitas Git, Composer, PHP compatible con `composer.lock` y las extensiones PDO/SQLite. El proyecto declara PHP 8.3 o superior; Composer comprueba los requisitos de las dependencias concretas. Se verificó con PHP 8.5.12 y Laravel 13.43.0. Para ejecutar la colección por terminal también necesitas Node.js y npm, que incluyen el comando `npx`.

Abre una terminal. Si todavía no has descargado el proyecto:

```bash
git clone https://github.com/Byroncha1323/api-academica.git
cd api-academica
```

Si ya lo tienes descargado, entra a la carpeta donde está el archivo `artisan`. Por ejemplo, en la PC de Jefferson:

```bash
cd /home/feno/Proyectos/api-academica
```

Ejecuta los siguientes comandos, uno por uno:

```bash
composer install
php -r "file_exists('.env') || copy('.env.example', '.env');"
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan config:clear
php artisan migrate --seed
```

Esto instala las dependencias, prepara el entorno, crea la base SQLite y carga roles, permisos y usuarios de prueba. `.env.example` ya incluye `DB_CONNECTION=sqlite`; no necesitas configurar MySQL. Si ya existe un `.env` con otra conexión, revísalo antes de ejecutar las migraciones.

Los comandos para crear `.env` y SQLite conservan los archivos si ya existen. La clave de aplicación se genera durante la instalación; no necesitas regenerarla cada vez que inicies el proyecto. No se necesita `npm install` ni compilar un frontend para usar esta API.

## 2. Iniciar la API

En una terminal ubicada en la carpeta del proyecto, ejecuta:

```bash
php artisan serve --host=127.0.0.1 --port=8001
```

Debes ver un mensaje como:

```text
Server running on [http://127.0.0.1:8001]
```

**Deja esta terminal abierta y el comando ejecutándose.** Abre una segunda terminal para las pruebas y entra también a la carpeta del proyecto. Si detienes el servidor con `Ctrl+C`, tendrás que iniciarlo de nuevo para usar Bruno CLI o Bruno.

La dirección que usa la colección es `http://127.0.0.1:8001/api`.

## 3. Comprobar todo desde la terminal

Son dos comprobaciones complementarias: Laravel prueba el código y Bruno CLI envía las solicitudes de la colección al servidor real.

### A. Ejecutar las pruebas de Laravel

En la segunda terminal, desde la raíz del proyecto:

```bash
php artisan test
```

Resultado esperado de esta versión:

```text
Tests: 42 passed (116 assertions)
```

Estas pruebas usan SQLite en memoria, no modifican la base local y pueden ejecutarse aunque el servidor esté detenido. Para ejecutar solo la suite de autorización:

```bash
php artisan test --filter=AutorizacionEstudiantesTest
```

### B. Ejecutar la colección de solicitudes

Con el servidor del paso 2 funcionando, ejecuta en la segunda terminal:

```bash
cd bruno
npx --yes @usebruno/cli@4.3.0 run --env Local -r
```

La primera ejecución puede necesitar Internet para descargar Bruno CLI. No necesitas abrir Bruno ni copiar tokens: la colección inicia sesión, guarda temporalmente los tokens y obtiene los IDs de los registros automáticamente.

Al finalizar, el resumen debe indicar **53 solicitudes correctas y 55 tests aprobados**, sin errores ni tests fallidos. Los tests son las comprobaciones escritas en los archivos `.bru`. Es normal que el resumen muestre `Assertions: 0/0`: las 55 comprobaciones están en el apartado `Tests`, que debe mostrar `55/55`.

El comando anterior te deja dentro de `bruno/`. Para volver a la raíz antes de ejecutar comandos de Laravel:

```bash
cd ..
```

**La colección pasó si no hay fallos.** Ver `401`, `403` o `404` junto a una solicitud puede ser correcto: varias pruebas esperan precisamente esas respuestas para comprobar las restricciones. Debe aparecer una marca `✓` en la comprobación correspondiente.

La colección trabaja sobre la base local de práctica. Cada ejecución crea una cuenta sin rol que permanece en la base, crea y elimina un registro académico temporal, modifica el nombre de Ana y restaura el estado activo de María. Por eso los IDs pueden cambiar entre ejecuciones; no se deben escribir a mano.

### Qué se comprueba

| Funcionalidad | Resultado que se verifica |
|---|---|
| Registro público | Crea usuario y token; ignora roles y permisos enviados por el cliente |
| Inicio de sesión y perfil | Credenciales válidas generan token; `/me` devuelve usuario y relaciones |
| Cierre de sesión | Revoca el token usado; ese token ya no puede acceder |
| Autenticación obligatoria | Sin token válido se recibe `401` |
| Gate administrativo | Administrador entra; profesor, estudiante y coordinador reciben `403` |
| Gate de profesores | Profesor entra; los demás roles reciben `403` |
| CRUD de estudiantes | Administrador puede crear, consultar, modificar y eliminar |
| Permisos por rol (RBAC) | Profesor, estudiante y coordinador no pueden crear ni eliminar |
| Propiedad del registro | Estudiante consulta su registro, incluso inactivo; los ajenos se deniegan |
| Carrera y sede (ABAC) | Profesor y coordinador modifican únicamente con permiso y misma carrera y sede |
| Validación de datos | Se rechazan campos inválidos y valores únicos duplicados; `user_id` no se modifica |
| Roles y seeders | Cuatro roles y cuatro permisos; coordinador tiene ver/editar; seeders repetibles |

Las pruebas de ABAC cubren las cuatro combinaciones: misma carrera/misma sede, misma carrera/otra sede, otra carrera/misma sede y otra carrera/otra sede. Solo se permite modificar en la primera combinación.

## 4. Probar con Bruno (opcional)

Esta alternativa ejecuta la misma colección con una aplicación gráfica.

1. Abre la aplicación Bruno y selecciona **Open Collection** (abrir colección).
2. Elige la carpeta [bruno/](bruno/) del repositorio; contiene `bruno.json`, que identifica la colección. No debes importar un archivo JSON de solicitudes.
3. Abre la colección **API Académica - Autorización Laravel 13**.
4. Selecciona el entorno **Local**. Su variable `base_url` debe ser `http://127.0.0.1:8001/api`.
5. Usa el ejecutor de la colección (**Run Collection**) para ejecutar las siete carpetas en el orden numerado. Mantén la ejecución secuencial: los casos siguientes usan los tokens e IDs obtenidos por los anteriores.
6. Revisa que las 53 solicitudes y los 55 tests pasen. El servidor debe seguir ejecutándose durante toda la prueba.

Si quieres realizar una solicitud manual, empieza con:

```text
POST http://127.0.0.1:8001/api/login
```

Selecciona cuerpo **JSON** y envía:

```json
{
  "email": "admin@academica.test",
  "password": "Password123!"
}
```

Copia el campo `token` de la respuesta. Crea una solicitud `GET http://127.0.0.1:8001/api/me`, selecciona autenticación **Bearer Token** y pega ese token. Agrega el encabezado `Accept: application/json`. La respuesta debe ser `200` y contener el usuario administrador.

## Cómo interpretar las respuestas

| Código | Significado en esta API |
|---|---|
| `200` | Consulta, modificación, eliminación o cierre de sesión realizados |
| `201` | Usuario o estudiante creado |
| `401` | Falta un token válido, o ya fue revocado |
| `403` | Usuario autenticado, pero sin autorización para esa acción |
| `404` | El registro o la ruta solicitados no existen |
| `422` | Datos inválidos; también credenciales incorrectas, según el contrato del taller |

## Problemas frecuentes

| Mensaje o situación | Qué hacer |
|---|---|
| `ECONNREFUSED 127.0.0.1:8001` | Inicia `php artisan serve --host=127.0.0.1 --port=8001` en otra terminal y déjala abierta. Después repite la colección. |
| Laravel muestra otro puerto | Usa la dirección que aparece en la terminal. Cambia la variable `base_url` en `bruno/environments/Local.bru` o en el entorno Local de Bruno para que coincida. |
| Puerto `8001` ocupado | Si ya hay una instancia de esta API funcionando allí, úsala. Si es otro programa, inicia Laravel con `--port=8002` y adapta `base_url`. |
| Pantalla de bienvenida de Laravel | Es el inicio normal del proyecto. Continúa con las pruebas de Laravel, Bruno CLI o Bruno. |
| `vendor/autoload.php` no existe | Ejecuta `composer install` desde la raíz del proyecto. |
| Falta la clave de aplicación | En la instalación inicial, crea `.env` y ejecuta `php artisan key:generate`. |
| SQLite no existe o faltan tablas | Crea el archivo SQLite y ejecuta `php artisan config:clear` seguido de `php artisan migrate --seed`, como en el paso 1. |
| Los usuarios de prueba no pueden iniciar sesión | Ejecuta `php artisan db:seed` y usa las credenciales de laboratorio de esta guía. El seeder conserva las contraseñas de cuentas que ya existían. |
| `php`, `composer` o `npx` no se reconoce | Instala la herramienta correspondiente y asegúrate de que esté disponible en la terminal. `npx` viene con npm. |

Si eliges otro puerto, edita `base_url` en `bruno/environments/Local.bru`. También puedes sobrescribirlo para una ejecución, desde la carpeta `bruno/`:

```bash
npx --yes @usebruno/cli@4.3.0 run --env Local -r --env-var "base_url=http://127.0.0.1:8002/api"
```

En la aplicación Bruno, cambia esa variable desde el entorno Local.

No uses `migrate:fresh` para resolver errores: elimina las tablas y sus datos. Las instrucciones de esta guía utilizan `migrate --seed`.

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
| `bruno/` | Colección para probar la API |
| [docs/codigo.md](docs/codigo.md) | Recorrido de solicitudes y explicación de clases, métodos y configuración |
| [docs/actividades-y-rubrica.md](docs/actividades-y-rubrica.md) | Actividades, reflexión y revisión de requisitos |
| [docs/evidencias/](docs/evidencias/) | Informe de verificación y resultados de ejecución |

`bootstrap/`, `config/`, `public/`, `resources/` y `storage/` conservan la estructura de Laravel. El punto de entrada para instalar, ejecutar y probar el proyecto es este README.

## Revisar rutas y evidencias

Para listar los endpoints registrados:

```bash
php artisan route:list --path=api
```

Deben aparecer **12 rutas**. Las diferencias de método HTTP (`GET`, `POST`, `PUT`, `PATCH`, `DELETE`) cuentan como rutas distintas.

- [Actividades, reflexión y rúbrica](docs/actividades-y-rubrica.md): explica las decisiones y relaciona cada criterio con el código y sus pruebas.
- [Informe de verificación](docs/evidencias/README.md): resume cómo se comprobó el flujo completo.
- [Resultado de pruebas automatizadas](docs/evidencias/pruebas-automatizadas.txt).
- [Resultado de la colección con Bruno CLI](docs/evidencias/bruno-cli.txt).
- [Migraciones ejecutadas](docs/evidencias/migraciones.txt).
- [Listado de rutas](docs/evidencias/rutas-api.txt).

Los reportes guardados corresponden a la verificación original. Para comprobar tu instalación actual, ejecuta los pasos de esta guía.

## Lista antes de entregar

- [ ] `php artisan test` termina con 42 pruebas aprobadas.
- [ ] Bruno CLI o Bruno terminan con 53 solicitudes, 55 tests y cero fallos.
- [ ] Se revisaron las actividades y las respuestas individuales de reflexión.
- [ ] Se incluyen el código, la colección, la documentación y las evidencias.
- [ ] Se excluyen `vendor/`, `.env`, la base SQLite y tokens personales; Git ya ignora esos archivos locales.

Al terminar las pruebas puedes detener el servidor con `Ctrl+C` en la terminal donde se está ejecutando.

La sintaxis de scripts y las opciones de ejecución siguen la [referencia de JavaScript de Bruno](https://docs.usebruno.com/testing/script/javascript-reference) y la [guía oficial del CLI](https://docs.usebruno.com/bru-cli/run/options).
