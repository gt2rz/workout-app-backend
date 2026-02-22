# CRUD de Periodización y Sesiones de Entrenamiento — 2026-02-20

## Tabla de Contenidos

- [Resumen](#resumen)
- [URLs Implementadas](#urls-implementadas)
- [Archivos Creados y Modificados](#archivos-creados-y-modificados)
- [Patrones Aplicados](#patrones-aplicados)
- [Bugs Críticos Encontrados y Resueltos](#bugs-críticos-encontrados-y-resueltos)
- [Cobertura de Tests](#cobertura-de-tests)

---

## Resumen

Se implementaron dos features completas siguiendo la arquitectura feature-based del proyecto:

- **Feature `Periodization`**: CRUD completo para la jerarquía `macrociclos → mesociclos → microciclos` con recursos anidados y scoped route model binding
- **Feature `Workout`**: CRUD de sesiones de entrenamiento
- **Autorización**: Políticas Eloquent por cada modelo (`MacrocyclePolicy`, `MesocyclePolicy`, `MicrocyclePolicy`, `WorkoutSessionPolicy`)
- **Resultado**: 75 tests pasando, 1 skipped

---

## URLs Implementadas

### Periodización

| Método | URL |
|--------|-----|
| GET | `/api/v1/periodization/macrocycles` |
| POST | `/api/v1/periodization/macrocycles` |
| GET | `/api/v1/periodization/macrocycles/{macrocycle}` |
| PUT | `/api/v1/periodization/macrocycles/{macrocycle}` |
| DELETE | `/api/v1/periodization/macrocycles/{macrocycle}` |
| GET | `/api/v1/periodization/macrocycles/{macrocycle}/mesocycles` |
| POST | `/api/v1/periodization/macrocycles/{macrocycle}/mesocycles` |
| GET | `/api/v1/periodization/macrocycles/{macrocycle}/mesocycles/{mesocycle}` |
| PUT | `/api/v1/periodization/macrocycles/{macrocycle}/mesocycles/{mesocycle}` |
| DELETE | `/api/v1/periodization/macrocycles/{macrocycle}/mesocycles/{mesocycle}` |
| GET | `...mesocycles/{mesocycle}/microcycles` |
| POST | `...mesocycles/{mesocycle}/microcycles` |
| GET | `...mesocycles/{mesocycle}/microcycles/{microcycle}` |
| PUT | `...mesocycles/{mesocycle}/microcycles/{microcycle}` |
| DELETE | `...mesocycles/{mesocycle}/microcycles/{microcycle}` |

### Sesiones de Entrenamiento

| Método | URL |
|--------|-----|
| GET | `/api/v1/workouts/sessions` |
| POST | `/api/v1/workouts/sessions` |
| GET | `/api/v1/workouts/sessions/{session}` |
| PUT | `/api/v1/workouts/sessions/{session}` |
| DELETE | `/api/v1/workouts/sessions/{session}` |

---

## Archivos Creados y Modificados

### Modelos (`app/Models/`)

| Archivo | Cambio |
|---------|--------|
| `Macrocycle.php` | Fillable, casts, relaciones (`user`, `mesocycles`), scopes `forUser`, `active` |
| `Mesocycle.php` | Fillable, casts (`deload_weeks: array`), relaciones (`macrocycle`, `mesocycleType`, `splitType`, `microcycles`) |
| `Microcycle.php` | Fillable, casts, relaciones (`mesocycle`, `workoutSessions`), scopes |
| `User.php` | Agregado `hasMany(Macrocycle)` |

### Factories (`database/factories/`)

| Archivo | States disponibles |
|---------|-------------------|
| `MacrocycleFactory.php` | `active()`, `planned()`, `completed()` |
| `MesocycleFactory.php` | — |
| `MicrocycleFactory.php` | `deload()`, `active()` |

### Excepciones (`app/Exceptions/Periodization/`)

- `MacrocycleNotFoundException.php` — HTTP 404, code: `PERIODIZATION.MACROCYCLE_NOT_FOUND`
- `MesocycleNotFoundException.php` — HTTP 404, code: `PERIODIZATION.MESOCYCLE_NOT_FOUND`
- `MicrocycleNotFoundException.php` — HTTP 404, code: `PERIODIZATION.MICROCYCLE_NOT_FOUND`

### Políticas (`app/Policies/`)

- `MacrocyclePolicy.php` — verifica `$user->id == $macrocycle->user_id`
- `MesocyclePolicy.php` — verifica ownership via macrocycle padre
- `MicrocyclePolicy.php` — verifica ownership via mesocycle → macrocycle
- `WorkoutSessionPolicy.php` — verifica `$user->id == $session->user_id`

### Form Requests

| Feature | Archivo |
|---------|---------|
| Periodization | `Requests/StoreMacrocycleRequest.php`, `UpdateMacrocycleRequest.php` |
| Periodization | `Requests/StoreMesocycleRequest.php`, `UpdateMesocycleRequest.php` |
| Periodization | `Requests/StoreMicrocycleRequest.php`, `UpdateMicrocycleRequest.php` |
| Workout | `Requests/StoreWorkoutSessionRequest.php`, `UpdateWorkoutSessionRequest.php` |

Todos los mensajes de validación en español.

### Eloquent Resources

| Feature | Archivo |
|---------|---------|
| Periodization | `Resources/MacrocycleResource.php` |
| Periodization | `Resources/MesocycleResource.php` |
| Periodization | `Resources/MicrocycleResource.php` |
| Workout | `Resources/WorkoutSessionResource.php` |

### Controllers

| Feature | Archivo |
|---------|---------|
| Periodization | `Controllers/MacrocycleController.php` |
| Periodization | `Controllers/MesocycleController.php` |
| Periodization | `Controllers/MicrocycleController.php` |
| Workout | `Controllers/WorkoutSessionController.php` |

### Rutas

- `app/Features/Periodization/routes.php`
- `app/Features/Workout/routes.php`

### Tests

- `tests/Feature/Periodization/MacrocycleTest.php` (7 tests)
- `tests/Feature/Periodization/MesocycleTest.php` (7 tests)
- `tests/Feature/Periodization/MicrocycleTest.php` (7 tests)
- `tests/Feature/Workout/WorkoutSessionTest.php` (7 tests)

### Infraestructura

- `bootstrap/app.php` — agregado `SubstituteBindings::class` al middleware stack de `api/v1` (ver Bug 1)

---

## Patrones Aplicados

### Feature-Based Architecture

Cada feature es un módulo autocontenido:

```
app/Features/Periodization/
├── Controllers/
│   ├── MacrocycleController.php
│   ├── MesocycleController.php
│   └── MicrocycleController.php
├── Requests/
│   ├── StoreMacrocycleRequest.php
│   └── ...
├── Resources/
│   ├── MacrocycleResource.php
│   └── ...
└── routes.php
```

### Nested Resources con Scoped Binding

```php
Route::prefix('periodization')->group(function () {
    Route::apiResource('macrocycles', MacrocycleController::class);

    Route::apiResource('macrocycles.mesocycles', MesocycleController::class)
        ->scoped();

    Route::apiResource('macrocycles.mesocycles.microcycles', MicrocycleController::class)
        ->scoped();
});
```

`->scoped()` sin argumentos resuelve el hijo por PK scoped al padre via la relación Eloquent definida en el modelo padre.

### Autorización en Controllers

```php
public function show(Request $request, Macrocycle $macrocycle): MacrocycleResource
{
    abort_if($request->user()->cannot('view', $macrocycle), 403);

    return (new MacrocycleResource($macrocycle->loadCount('mesocycles')))
        ->additional(['status' => 'success']);
}
```

### Factories con States

```php
// Uso en tests:
Macrocycle::factory()->active()->create(['user_id' => $user->id]);
Microcycle::factory()->deload()->create(['mesocycle_id' => $mesocycle->id]);
```

---

## Bugs Críticos Encontrados y Resueltos

### Bug 1 — `SubstituteBindings` ausente en el stack de middleware `api/v1`

**Síntoma:** Los endpoints `show`, `update` y `destroy` retornaban HTTP 403 incluso para el dueño del recurso. La policy funcionaba correctamente en tinker (`$user->can('view', $mac)` = CAN), pero fallaba en requests HTTP reales.

**Proceso de diagnóstico:**

1. Confirmado en tinker: `Gate::forUser($user)->inspect('view', $mac)` → CAN
2. Confirmado fuera del request: variable `gate_can_outside_request = true`
3. Cambiado `cannot()` por comparación directa `$user->id != $macrocycle->user_id` → SIGUE en 403
4. Agregado logging al controller → hallazgo clave:

```
macrocycle_injected_id: null
route_param_type: "string"   ← el parámetro nunca fue resuelto a modelo Eloquent
route_param_value: "1"
```

El IoC container inyectaba una instancia fresca `new Macrocycle()` con `id = null` en lugar del modelo recuperado de la base de datos. Al comparar `null != 1` → siempre verdadero → 403.

**Causa raíz:** El grupo de rutas `api/v1` en `bootstrap/app.php` usa un middleware stack personalizado (no el grupo `api` default de Laravel). Ese stack no incluía `SubstituteBindings::class`, el middleware responsable de interceptar los parámetros de ruta y resolverlos a modelos Eloquent antes de que lleguen al controller.

**Fix:**

```php
// bootstrap/app.php
use Illuminate\Routing\Middleware\SubstituteBindings;

Route::prefix('api/v1')->middleware([
    EnsureApiKeyIsValid::class,
    ForceHttps::class,
    SecurityHeaders::class,
    RequestLogger::class,
    IdempotencyMiddleware::class,
    ConditionalResponse::class,
    'throttle:api',
    SubstituteBindings::class,  // AGREGADO
])
```

**Lección:** Cualquier grupo de rutas personalizado que use route model binding DEBE incluir `SubstituteBindings::class`. El grupo `api` default de Laravel ya lo incluye; los grupos personalizados no.

---

### Bug 2 — `->scoped(['param' => 'field'])` con semántica incorrecta

**Síntoma:** Tras corregir Bug 1, los endpoints `show`, `update` y `destroy` de mesociclos y microciclos retornaban HTTP 404.

**Causa raíz:** Se había definido la ruta con:

```php
Route::apiResource('macrocycles.mesocycles', MesocycleController::class)
    ->scoped(['mesocycle' => 'macrocycle_id']);
```

El array en `->scoped(['param' => 'field'])` le dice a Laravel: *"para resolver el parámetro `{mesocycle}`, busca en la tabla haciendo `WHERE field = valor_url`"*. En este caso, para la URL `/macrocycles/1/mesocycles/2`, la query resultante era:

```sql
-- Buscaba mesocycles WHERE macrocycle_id = 2  (el ID del mesocycle en la URL)
-- Scoped al padre WHERE macrocycle_id = 1
-- Resultado: WHERE macrocycle_id = 1 AND macrocycle_id = 2 → imposible → 404
```

**Fix:**

```php
// INCORRECTO — busca el {mesocycle} por el campo macrocycle_id:
->scoped(['mesocycle' => 'macrocycle_id'])

// CORRECTO — busca por PK, scoped al padre via relación Eloquent:
->scoped()
```

Con `->scoped()` sin argumentos, Laravel usa la PK del hijo con scope al padre a través de la relación definida en el modelo (`$macrocycle->mesocycles()->where('id', 2)->first()`).

**Regla:** Solo usar `->scoped(['param' => 'field'])` para lookups por campo no-PK (slugs, UUIDs custom, etc.). Para scoping estándar por PK, usar `->scoped()` sin argumentos.

---

## Cobertura de Tests

### Resultado final

```
75 tests pasando, 1 skipped
```

### Escenarios por feature (7 tests cada uno)

| # | Escenario |
|---|-----------|
| 1 | `index` retorna solo los recursos del usuario autenticado |
| 2 | `store` crea el recurso y retorna HTTP 201 |
| 3 | `store` retorna HTTP 422 con datos inválidos |
| 4 | `show` retorna el recurso correcto |
| 5 | `update` modifica el recurso |
| 6 | `destroy` elimina el recurso y retorna HTTP 204 |
| 7 | Otro usuario recibe HTTP 403 (o 404 por scope en recursos anidados) |

### Comando para ejecutar los tests de periodización

```bash
vendor/bin/sail artisan test --compact --filter=MacrocycleTest
vendor/bin/sail artisan test --compact --filter=MesocycleTest
vendor/bin/sail artisan test --compact --filter=MicrocycleTest
vendor/bin/sail artisan test --compact --filter=WorkoutSessionTest
```
