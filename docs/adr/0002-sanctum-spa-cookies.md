# ADR-0002 — Migración de `localStorage` a cookies HttpOnly con Sanctum SPA

## Status
Accepted — 2026-05-07

## Context
La autenticación previa guardaba el token Bearer en `localStorage` y el frontend lo reenviaba en `Authorization`.
Ese enfoque funcionaba, pero dejaba la credencial accesible desde JavaScript, aumentando el impacto de cualquier XSS.

Además, el proyecto ya usaba `auth:sanctum`, por lo que tenía sentido adoptar el modo oficial de SPA authentication de Sanctum con sesión Laravel real, cookie HttpOnly y protección CSRF.

## Decision
Se migra la autenticación del SPA al modo stateful de Sanctum:

- La autenticación principal viaja en cookie de sesión HttpOnly.
- El frontend pide `GET /sanctum/csrf-cookie` antes del login y envía `X-XSRF-TOKEN` en peticiones mutantes.
- Se habilita `statefulApi()` en `bootstrap/app.php`.
- Se ajustan `cors.php`, `session.php` y `sanctum.php` para permitir credenciales y dominios stateful.
- `LoginController` crea sesión web con `Auth::guard('web')->login(...)` y regenera la sesión.
- `LogoutController` invalida sesión y regenera el CSRF token.

La UI state no credencial se separa de la autenticación:

- `backoffice_acting_user` pasa a `sessionStorage`.
- `tpv-active-user-v1` pasa a `sessionStorage`.

El token legacy se sigue generando internamente en `LoginUser` para no romper la abstracción de dominio y conservar una salida futura para clientes no-cookie, pero ya no se expone en el body HTTP del login.

## Consequences
### Positivas
- La credencial principal ya no es legible por JavaScript.
- Logout revoca tokens existentes y destruye la sesión server-side.
- El frontend deja de inyectar `Authorization` manualmente.
- La autenticación queda alineada con el flujo soportado por Laravel Sanctum.

### Costes
- El login requiere una request previa a `/sanctum/csrf-cookie`.
- CORS debe configurarse con `supports_credentials = true` y orígenes explícitos.
- Los guards del frontend pasan a depender de `me()` y, por tanto, son asíncronos.

## Alternatives considered
- Mantener Bearer en `localStorage`: descartado por exposición a XSS.
- JWT/cookie custom: descartado por duplicar responsabilidades ya resueltas por Sanctum.
- Mover también el UI state a cookie HttpOnly: descartado porque la UI necesita leer ese estado desde JS.
- Eliminar `LaravelTokenGenerator`: descartado por ahora; mantenerlo cuesta poco y deja abierta la opción de clientes nativos o integraciones no basadas en cookie sin rehacer el dominio.

## Operational notes
- Antes de una demo en entorno real, conviene validar el flujo bajo HTTPS en staging o producción: si frontend y API usan dominios distintos, `SESSION_SECURE_COOKIE=true` y `SESSION_DOMAIN` correcto son obligatorios para que la cookie de sesión funcione como se espera.
- En local con HTTP, la combinación actual puede parecer más permisiva por ir sin `Secure` y con `SameSite=Lax`, así que ese comportamiento no debe darse por válido sin probarlo antes en un entorno HTTPS real.
- `LaravelTokenGenerator` debe retirarse junto con la dependencia operativa de `personal_access_tokens` cuando ocurra cualquiera de estos triggers: (a) se decida explícitamente que la API solo servirá a clientes navegador de forma permanente, o (b) pasen 6 meses sin aparecer ningún cliente que necesite tokens Bearer. Lo que ocurra antes.
- Operacional pendiente: cada login sigue generando una fila en `personal_access_tokens`. Si `LaravelTokenGenerator` permanece activo, conviene programar una limpieza periódica de tokens antiguos con un comando Artisan; si se retira, puede eliminarse también esa carga histórica según la estrategia de migración acordada.
