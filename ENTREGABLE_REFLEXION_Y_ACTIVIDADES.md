# Actividades y revisión de la rúbrica

Documento de apoyo para la entrega del taller de mecanismos de autorización en Laravel 13. Las respuestas de reflexión deben ser revisadas por quien realiza la entrega individual.

## Revisión de cada criterio

| Criterio del taller | Peso | Situación encontrada | Implementación y verificación |
|---|---:|---|---|
| Creación y configuración de Laravel 13 | 10% | Dependencias declaradas, sin instalar; ruta API con sintaxis inválida y BOM; ejemplo de entorno dependiente de MySQL local | Ruta corregida; SQLite en `.env.example`; Laravel 13.43.0 instalado desde el lock; migraciones ejecutadas |
| Autenticación Sanctum | 10% | Trait y migración existentes; sin controlador ni rutas de autenticación | `AuthController`: register/login/me/logout; tokens reales en Feature y HTTP; registro ignora roles/permisos; logout revoca token actual |
| Gates | 15% | Ambos Gates definidos, sin rutas para utilizarlos | Rutas `/admin/dashboard` y `/profesor/dashboard`; pruebas con administrador, profesor, estudiante y coordinador |
| Policies | 20% | CRUD y Policy parciales; se impedía consultar el propio registro inactivo | Policy utilizada en las cinco acciones; propiedad sin acceso a registros ajenos; pruebas de activos/inactivos, CRUD, validación e identidad propietaria |
| RBAC con Spatie | 15% | Trait, configuración y migración existentes; sin roles ni permisos sembrados | `RolesYPermisosSeeder`, cuatro permisos y cuatro roles; middleware de creación; pruebas de permisos y denegaciones |
| ABAC | 15% | Carrera y sede presentes en modelos, migraciones y Policy; sin datos ni pruebas | Fixtures de cuatro combinaciones; actualización limitada por permiso, carrera y sede para profesor/coordinador; pruebas por HTTP y Feature |
| Postman y pruebas automatizadas | 10% | Únicamente las pruebas predeterminadas; ninguna colección real | Colección de 53 solicitudes y suite `AutorizacionEstudiantesTest`; evidencias en `evidencias/` |
| Reflexión y explicación técnica | 5% | README anunciaba documentos inexistentes | Respuestas de actividades, explicación de decisiones y guía de uso en README |

Esta matriz vincula los criterios con código y pruebas; no asigna una nota, que corresponde a la evaluación docente.

## Actividad 1 — Gates

En `app/Providers/AppServiceProvider.php`:

- `access-admin-dashboard`: exige rol `administrador`.
- `access-professor-dashboard`: exige rol `profesor`; el administrador no obtiene excepción global.

Las rutas de `routes/api.php` ejecutan `Gate::authorize` dentro de `auth:sanctum`.

| Usuario | Dashboard administrativo | Dashboard profesor |
|---|---:|---:|
| Administrador | 200 | 403 |
| Profesor ITI | 403 | 200 |
| Estudiante Ana | 403 | 403 |
| Coordinador ITI | 403 | 403 |
| Sin token | 401 | 401 |

Evidencia: casos `test_gates`, `test_rutas_protegidas_requieren_token` y solicitudes Gate de Postman.

## Actividad 2 — Policies

`EstudiantePolicy::view` exige que `estudiante.user_id` coincida con el ID del usuario para el rol estudiante. Esa condición se aplica a registros activos e inactivos y evita que compartir carrera conceda acceso a registros ajenos. El taller prohíbe consultar registros inactivos de otros estudiantes; no pide bloquear el registro propio inactivo.

| Solicitante | Registro | Resultado |
|---|---|---:|
| Ana | Propio activo | 200 |
| Ana | Propio inactivo | 200 |
| Ana | María activa, misma carrera | 403 |
| Ana | María inactiva, misma carrera | 403 |
| Ana | Luis, otra carrera | 403 |
| Pedro | Propio inactivo | 200 |
| Profesor ITI | Ana, misma carrera | 200 |
| Profesor ITI | Luis, otra carrera | 403 |
| Administrador | Cualquier registro existente | 200 |

Evidencia: `test_consulta_segun_propiedad_y_carrera` y `test_estudiante_no_consulta_registro_inactivo_ajeno_de_su_carrera`. La colección inactiva temporalmente a María para comprobar el caso de misma carrera y restaura su estado al terminar.

## Actividad 3 — RBAC

`RolesYPermisosSeeder` crea o reutiliza los permisos con guard `web`, sincroniza los roles y limpia la caché de Spatie. Sanctum autentica al usuario y Spatie verifica los permisos del modelo User.

| Rol | ver | crear | editar | eliminar |
|---|---|---|---|---|
| administrador | Sí | Sí | Sí | Sí |
| profesor | Sí | No | Sí | No |
| coordinador | Sí | No | Sí | No |
| estudiante | Sí | No | No | No |

El coordinador consulta detalles de su carrera y modifica registros de su carrera y sede. Se conserva la decisión ABAC que ya tenía la Policy del repositorio, porque la actividad pide definir esas restricciones y no fija un acceso global para este rol.

Evidencia: `test_seeders_repetibles_y_permisos_coordinador`, `test_rbac_deniega_creacion_y_eliminacion`, consultas y modificaciones del coordinador, y petición `/me` de la colección. Los permisos heredados se consultan con `getAllPermissions()`; la relación `permissions` muestra solo asignaciones directas.

## Actividad 4 — ABAC

Las migraciones existentes crean `sedes`, agregan `sede_id` a usuarios y lo relacionan también con cada registro de estudiante. `User`, `Estudiante` y `Sede` ya contienen las relaciones necesarias. Se conservaron esos archivos y se agregaron los datos y pruebas que faltaban.

Para modificar, el administrador requiere `estudiantes.editar`. Un profesor o coordinador requiere ese permiso y coincidencia no nula de carrera y sede con el registro. Un estudiante no puede modificar ni siquiera su registro propio.

Profesor/coordinador ITI en San Carlos:

| Registro | Carrera | Sede | Escenario | PUT/PATCH |
|---|---|---|---|---:|
| Ana | ITI | SC | Misma carrera y misma sede | 200 |
| María | ITI | CEN | Misma carrera y diferente sede | 403 |
| Pedro | ISW | SC | Diferente carrera y misma sede | 403 |
| Luis | ISW | CEN | Diferente carrera y diferente sede | 403 |

Evidencia: `test_abac_carrera_y_sede` verifica ambas funciones profesionales y las cuatro combinaciones. También comprueba persistencia después del intento. `test_permiso_editar_y_atributos_no_nulos_son_necesarios` demuestra que los atributos por sí solos no conceden acceso. Las solicitudes ABAC de Postman prueban las cuatro combinaciones con ambos roles.

## Discusión del apartado 19.2

1. Si el profesor cambia de carrera, las siguientes autorizaciones usan su nueva carrera: pierde acceso a los registros que ya no coinciden y puede acceder a los que coincidan, sujeto a sede y permisos.
2. Si el estudiante cambia de carrera, la autorización sobre ese registro se evalúa con la nueva carrera. Su propietario sigue siendo el mismo `user_id`; carrera no transfiere propiedad.
3. El rol profesor describe una responsabilidad general, pero no indica qué registros concretos puede modificar. Se necesitan permiso, carrera y sede.
4. Otros atributos posibles son período académico, estado de matrícula y departamento. No se implementan porque el taller pide agregar sede.

## Actividad 5 — Reflexión de seguridad

1. **Autenticación y autorización.** Autenticar comprueba quién realiza la solicitud, por ejemplo con credenciales y un token Sanctum. Autorizar decide si ese usuario puede realizar una acción sobre un registro concreto.
2. **Uso de Gates.** Son adecuados para capacidades generales sin un recurso particular, como entrar al dashboard administrativo o al de profesores.
3. **Policies para CRUD.** Agrupan las decisiones de listar, consultar, crear, actualizar y eliminar de un modelo. Reciben el usuario y, cuando corresponde, el registro, de modo que sus atributos pueden participar en la decisión.
4. **RBAC y ABAC.** RBAC concede capacidades mediante roles y permisos. ABAC evalúa atributos del usuario, del recurso o del contexto. Aquí el permiso de editar se combina con carrera y sede.
5. **Límite de `estudiantes.editar`.** El permiso habilita el tipo de operación; no define cuáles estudiantes pertenecen al ámbito del profesor. Dar acceso general permitiría modificar estudiantes de otras carreras o sedes.
6. **Rol en el registro público.** Si el backend confiara en un rol enviado por el cliente, una persona podría autoasignarse `administrador`. El controlador crea el usuario únicamente con nombre, correo y contraseña; ignora rol, permisos, carrera y sede enviados como campos adicionales.
7. **Autorización en backend.** Una persona puede cambiar el cliente o enviar solicitudes directamente con Postman. Ocultar botones no impide llamar a una ruta. El servidor verifica token, permisos y atributos antes de leer o modificar el recurso.
8. **HTTP 401 y 403.** `401` indica que falta una autenticación válida, por ejemplo un token ausente, inválido o revocado. `403` indica que el usuario autenticado no tiene autorización para esa operación. Además, el contrato del taller devuelve `422` para credenciales incorrectas mediante `ValidationException`, errores de validación también `422`, y recurso inexistente `404`.

## Aclaraciones del documento aplicadas

- El escenario dice que cualquier autenticado consulta el listado, mientras que la implementación del taller usa el permiso `estudiantes.ver`. Se sigue esa implementación: todos los roles de laboratorio tienen el permiso y una cuenta pública recién creada no tiene rol ni permisos todavía.
- La consulta del detalle es distinta del listado general. Se conserva la paginación general del ejemplo y la restricción de propiedad en el detalle.
- La actividad de sede extiende la creación de estudiantes con `sede_id`, ya requerido por el controlador existente. La colección obtiene IDs reales desde el listado en lugar de suponer `user_id: 6`.
- Se conservaron los modelos, migraciones, controladores y Gates que ya resolvían los requisitos; no se agregó interfaz web ni funcionalidad ajena al taller.

## Entregables y evidencias

1. Proyecto Laravel 13: código fuente y dependencias fijadas en `composer.lock`.
2. Modelos, migraciones, controladores, Gates y Policies: directorios `app/`, `database/migrations/` y `routes/`.
3. Roles, permisos y datos: `database/seeders/` con llamadas en `DatabaseSeeder`.
4. Colección: `postman/API-Academica.postman_collection.json`.
5. Evidencias: resultados de ejecución en `evidencias/`.
6. Pruebas Feature: `tests/Feature/AutorizacionEstudiantesTest.php`.
7. Reflexión: este documento.

No incluir `vendor`, `.env`, la base de datos local ni tokens personales al entregar. La colección guarda tokens solo durante su ejecución: no exportar sus variables después de probarla.
