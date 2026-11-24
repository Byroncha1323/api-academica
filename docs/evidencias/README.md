# Verificación del taller

Fecha local de la sesión: 23 de noviembre de 2026 (Costa Rica).

Flujo comprobado: un cliente registra o autentica un usuario, recibe un token Sanctum, envía una solicitud protegida, pasa por middleware/Gate/Policy, consulta o modifica SQLite y recibe el código HTTP correspondiente.

| Límite comprobado | Resultado | Evidencia |
|---|---|---|
| Configuración y arranque | Laravel Framework 13.43.0; PHP 8.5.12; SQLite; servidor `http://127.0.0.1:8001` | `php artisan --version`, arranque de Artisan y ejecución HTTP |
| Migraciones y datos | Las nueve migraciones se ejecutaron y ambos seeders finalizaron correctamente | Instalación local con `php artisan migrate --seed`; pruebas con `RefreshDatabase` |
| Rutas y cliente | Doce rutas API; colección ejecutable de 53 solicitudes | [rutas API](rutas-api.txt), [ejecución Bruno CLI](bruno-cli.txt) |
| Token → API | Login y registro devuelven token; rutas sin token y token revocado devuelven 401 | Feature y solicitudes de autenticación de Bruno CLI |
| API → autorización | Gates, middleware de creación, Policy de detalle/CRUD y ABAC por carrera/sede | Casos de los cuatro roles y cuatro combinaciones ABAC |
| API → datos → respuesta | Alta 201, consulta y actualización 200, eliminación 200 y posterior consulta 404 | Flujo CRUD de Bruno CLI; aserciones de persistencia en Feature |
| Resultados automatizados | 42 pruebas, 116 aserciones, sin fallos | [pruebas automatizadas](pruebas-automatizadas.txt) |
| Colección nativa Bruno con Bruno CLI 4.3.0 | 53 solicitudes, 55 tests aprobados y cero fallos | [ejecución Bruno CLI](bruno-cli.txt) |
| Formato y dependencias | `vendor/bin/pint --dirty --test` aprobado; `git diff --check` sin errores; `composer validate --no-check-publish` válido | Comandos ejecutados en la sesión |

La funcionalidad solicitada es una API; el cliente utilizado para verificarla es la colección Bruno ejecutada con Bruno CLI. No se agregó interfaz gráfica. La base verificada fue SQLite; no se comprobó MySQL ni PostgreSQL.

## Reproducir

Con las dependencias instaladas, `.env` configurado, clave generada y SQLite creada:

```bash
php artisan migrate --seed
php artisan test
php artisan route:list --path=api
php artisan serve --port=8001
```

En otra terminal:

```bash
cd bruno
npx --yes @usebruno/cli@4.3.0 run --env Local -r
```

También se puede abrir la carpeta `bruno/` con Open Collection en Bruno, elegir el entorno Local y ejecutar toda la colección en orden. No se requiere pegar tokens ni asumir IDs fijos. La colección modifica datos de práctica: crea una cuenta sin rol, crea y elimina su registro académico temporal, cambia el nombre de Ana y restaura el estado de María a activo.

La colección y el informe de Bruno utilizan el entorno Local y el puerto `8001`. El reporte de Bruno se obtiene ejecutando la colección nativa; no contiene cuerpos de respuestas ni tokens.

Los archivos de evidencia muestran resultados y estados HTTP; no contienen tokens, contraseñas reales ni cuerpos completos de respuestas. Las credenciales incluidas en la colección son las cuentas de laboratorio del taller.

Los comandos de Laravel se ejecutan desde la raíz; para el CLI de Bruno se entra primero en `bruno/`. Volver a la [guía del proyecto](../../README.md) o revisar las [actividades y rúbrica](../actividades-y-rubrica.md).
