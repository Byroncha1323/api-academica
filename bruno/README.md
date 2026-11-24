# Colección Bruno — API Académica

La colección contiene 53 solicitudes y 55 tests. Las carpetas y sus solicitudes tienen un número de secuencia para ejecutarse en orden; no usar ejecución paralela.

## Desde la aplicación

1. Seleccionar **Open Collection** y elegir esta carpeta, que contiene `bruno.json`.
2. Elegir el entorno **Local** (`environments/Local.bru`).
3. Con Laravel funcionando en `http://127.0.0.1:8001`, ejecutar toda la colección con **Run Collection**.
4. Confirmar que las 53 solicitudes y los 55 tests pasen.

## Desde la terminal

En una terminal, desde la raíz del proyecto:

```bash
php artisan serve --host=127.0.0.1 --port=8001
```

En otra terminal, desde la raíz:

```bash
cd bruno
npx --yes @usebruno/cli@4.3.0 run --env Local -r
```

Resultado esperado: `Status: PASS`, `Requests: 53 (53 Passed)` y `Tests: 55/55`. El apartado `Assertions: 0/0` es normal: todas las comprobaciones están escritas como tests JavaScript.

## Organización

| Carpeta | Qué comprueba |
|---|---|
| `01-Autenticacion` | Token obligatorio, registro, inicio de sesión, perfil e IDs reales |
| `02-Gates` | Dashboards con administrador, profesor, estudiante y coordinador |
| `03-Policies` | Consulta según propiedad, carrera y estado activo/inactivo |
| `04-ABAC` | Las cuatro combinaciones de carrera y sede para modificar |
| `05-RBAC` | Denegación de creación y eliminación según los permisos |
| `06-CRUD` | Restricción sobre inactivos ajenos y ciclo de creación, modificación y eliminación |
| `07-Sesion` | Cuenta sin privilegios, cierre de sesión y token revocado |

Los tokens e IDs se guardan con `bru.setVar()` durante la ejecución. El entorno versionado solo contiene `base_url`; no contiene tokens. La colección necesita los seeders de la actividad y modifica datos de práctica: crea una cuenta sin rol, crea y elimina un registro académico temporal, modifica el nombre de Ana y restaura el estado activo de María.

Puedes cambiar el puerto en `environments/Local.bru` o sobrescribirlo al ejecutar:

```bash
npx --yes @usebruno/cli@4.3.0 run --env Local -r --env-var "base_url=http://127.0.0.1:8002/api"
```

Consulta la [guía completa del proyecto](../README.md) y el [reporte de ejecución](../docs/evidencias/bruno-cli.txt).

Cada solicitud incluye documentación en su bloque `docs`, visible desde la aplicación Bruno. Para seguir las decisiones de autorización del servidor, consulta la [documentación del código](../docs/codigo.md).
