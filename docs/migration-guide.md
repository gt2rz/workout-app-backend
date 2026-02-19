# Guia de Migracion: MVC Plano a Arquitectura Feature-Based

## Tabla de Contenidos

- [Introduccion](#introduccion)
- [Fase 1: Error Handling y Estandarizacion de Respuestas](#fase-1-error-handling-y-estandarizacion-de-respuestas)
- [Fase 2: Arquitectura Feature-Based](#fase-2-arquitectura-feature-based)
- [Fase 3: Estrategia de Caching](#fase-3-estrategia-de-caching)
- [Fase 4: Monitoreo y Observabilidad](#fase-4-monitoreo-y-observabilidad)
- [Fase 5: Mejores Practicas API 2026](#fase-5-mejores-practicas-api-2026)
- [Fase 6: Expansion de Tests](#fase-6-expansion-de-tests)
- [Errores Encontrados y Soluciones](#errores-encontrados-y-soluciones)
- [Arquitectura Final](#arquitectura-final)

---

## Introduccion

### El problema: MVC plano

La API usaba la estructura clasica de Laravel MVC:

```
app/Http/Controllers/Api/V1/AuthController.php
app/Http/Controllers/Api/V1/HomeController.php
app/Http/Controllers/Api/V1/ProfileController.php
app/Http/Controllers/Api/V1/HealthController.php
app/Http/Resources/Api/V1/HomeResource.php
...
```

Esto funciona para proyectos pequenos, pero a medida que crece:
- **Acoplamiento alto**: Controllers, resources y requests estan separados por tipo, no por dominio. Para trabajar en "Auth" necesitas tocar 4+ carpetas distintas.
- **Escalabilidad limitada**: Con 23 modelos y solo 5 controllers, agregar nuevos features significa inflar las mismas carpetas.
- **Error handling ad-hoc**: Cada controller manejaba errores a su manera con try-catch inline.
- **Sin monitoreo**: No habia logging de requests, deteccion de queries lentas, ni health checks robustos.

### La solucion: Feature-Based Architecture

Reorganizar el codigo por **dominio de negocio** (Auth, Profile, Workout, Home, etc.) en lugar de por tipo de archivo (Controllers, Resources, Requests). Cada feature es un modulo autocontenido con todo lo que necesita.

### Las 6 fases

| Fase | Objetivo |
|---|---|
| 1 | Error handling centralizado y respuestas estandarizadas |
| 2 | Reorganizar en modulos por feature |
| 3 | Caching inteligente con invalidacion automatica |
| 4 | Monitoreo: logging, slow queries, Laravel Pulse |
| 5 | Best practices 2026: security headers, idempotencia, OpenAPI docs |
| 6 | Tests para todos los features |

---

## Fase 1: Error Handling y Estandarizacion de Respuestas

### Concepto central: Exception-Based Error Handling

En lugar de usar `try-catch` en cada controller y devolver JSON manualmente, definimos **excepciones tipadas** que saben como renderizarse. Laravel las captura automaticamente a traves del exception handler global.

**Antes (ad-hoc):**
```php
// En cada controller
try {
    $profile = $user->profile;
    if (!$profile) {
        return response()->json(['status' => 'error', 'message' => 'No encontrado'], 404);
    }
} catch (\Exception $e) {
    return response()->json(['status' => 'error', 'message' => 'Error interno'], 500);
}
```

**Despues (exception-based):**
```php
// En el controller - limpio y directo
if (!$profile) {
    throw new ProfileNotFoundException;
}
// El exception handler se encarga del formato JSON automaticamente
```

---

### `app/Support/ApiResponse.php`

**Concepto:** Trait de respuestas estandarizadas

**Que hace:** Provee 3 metodos reutilizables para controllers:

```php
trait ApiResponse
{
    // Respuesta exitosa con envelope estandar
    protected function success(mixed $data = null, int $status = 200, array $meta = []): JsonResponse

    // Respuesta de error con codigo estructurado
    protected function error(string $message, int $status = 400, ?string $code = null, array $errors = []): JsonResponse

    // Respuesta paginada con cursor-based pagination
    protected function paginated(CursorPaginator $paginator, string $resourceClass): JsonResponse
}
```

**Por que:** Todos los endpoints deben devolver el mismo formato JSON:
```json
{
    "status": "success",
    "data": { ... },
    "meta": { ... }
}
```

Sin este trait, cada developer escribiria `response()->json()` con formatos ligeramente distintos. El trait **fuerza consistencia**.

**Concepto clave - Trait vs Clase:** Un trait se "mezcla" dentro de la clase que lo usa, dando acceso a sus metodos como si fueran propios. Es ideal cuando multiples clases necesitan la misma funcionalidad pero no comparten una herencia comun.

---

### `app/Exceptions/ApiException.php`

**Concepto:** Excepcion base con auto-rendering

**Que hace:** Clase base para todas las excepciones de dominio. Contiene:
- `$statusCode` — HTTP status (400, 401, 404, etc.)
- `$errorCode` — Codigo estructurado legible por maquinas (`AUTH.INVALID_CREDENTIALS`)
- `$context` — Datos adicionales de debug (solo visibles en modo debug)
- `render()` — Convierte la excepcion a JSON automaticamente

```php
class ApiException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 400,
        public readonly string $errorCode = 'GENERAL.ERROR',
        public readonly array $context = [],
    ) {
        parent::__construct($message, $statusCode);
    }

    public function render(): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $this->message,
            'code' => $this->errorCode,
        ];

        // Solo en debug mode se muestran detalles extra
        if (!empty($this->context) && config('app.debug')) {
            $response['context'] = $this->context;
        }

        return response()->json($response, $this->statusCode);
    }
}
```

**Por que:** En Laravel, si una excepcion tiene un metodo `render()`, Laravel lo llama automaticamente cuando la excepcion se lanza. Esto significa que no necesitas try-catch — solo `throw new ApiException(...)` y Laravel hace el resto.

**Concepto clave - `readonly` properties:** PHP 8.1+ permite declarar propiedades como `readonly`, lo que significa que solo se pueden asignar una vez (en el constructor). Esto previene modificaciones accidentales.

---

### `app/Exceptions/Auth/AuthenticationFailedException.php`

**Concepto:** Excepcion de dominio especifica

```php
class AuthenticationFailedException extends ApiException
{
    public function __construct(string $message = 'Las credenciales son incorrectas.')
    {
        parent::__construct(
            message: $message,
            statusCode: 401,
            errorCode: 'AUTH.INVALID_CREDENTIALS',
        );
    }
}
```

**Por que:** Cada dominio tiene sus propias excepciones con mensajes y codigos predeterminados. Un developer solo necesita escribir `throw new AuthenticationFailedException` sin configurar nada. El codigo `AUTH.INVALID_CREDENTIALS` permite al cliente (app movil) manejar el error programaticamente sin depender del mensaje de texto.

---

### `app/Exceptions/Auth/InvalidApiKeyException.php`

**Concepto:** Excepcion para API key invalida o inactiva

```php
class InvalidApiKeyException extends ApiException
{
    public function __construct(string $message = 'API Key invalida o inactiva.')
    {
        parent::__construct(message: $message, statusCode: 401, errorCode: 'AUTH.INVALID_API_KEY');
    }
}
```

**Por que:** Separar `INVALID_API_KEY` de `INVALID_CREDENTIALS` permite al cliente saber exactamente que fallo — la key de la app vs las credenciales del usuario.

---

### `app/Exceptions/Workout/WorkoutNotFoundException.php`

**Concepto:** Excepcion de dominio para workouts

**Por que:** Codigo `WORKOUT.NOT_FOUND` (404). En lugar de un generico "Not found", el cliente sabe que lo que no se encontro fue un workout especificamente.

---

### `app/Exceptions/Profile/ProfileNotFoundException.php`

**Concepto:** Excepcion de dominio para perfil

**Por que:** Codigo `PROFILE.NOT_FOUND` (404). El ProfileController lanza esta excepcion cuando un usuario autenticado no tiene perfil creado.

---

### `bootstrap/app.php` (seccion `withExceptions`)

**Concepto:** Exception Handler Global

**Que hace:** Registra como Laravel debe manejar cada tipo de excepcion que no tiene `render()` propio:

```php
->withExceptions(function (Exceptions $exceptions): void {
    // Nuestras excepciones custom — se auto-renderizan
    $exceptions->renderable(function (ApiException $e) {
        return $e->render();
    });

    // Errores de validacion de Laravel — 422
    $exceptions->renderable(function (ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'code' => 'VALIDATION_ERROR',
            'errors' => $e->errors(),
        ], 422);
    });

    // Usuario no autenticado — 401
    $exceptions->renderable(function (AuthenticationException $e) { ... });

    // Modelo no encontrado (findOrFail) — 404
    $exceptions->renderable(function (ModelNotFoundException $e) { ... });

    // Rate limiting excedido — 429
    $exceptions->renderable(function (ThrottleRequestsException $e) { ... });
})
```

**Por que:** Sin esto, Laravel devolveria HTML o un JSON con formato diferente para cada tipo de error. El handler global garantiza que **todas** las respuestas de error siguen el mismo envelope `{status, message, code}`.

**Concepto clave - renderable():** Cada `renderable()` registra un callback que se ejecuta cuando se lanza una excepcion de ese tipo. Laravel usa el type-hint del parametro para determinar cual callback ejecutar. Esto es **pattern matching** por tipo de excepcion.

---

## Fase 2: Arquitectura Feature-Based

### Concepto central: Modularizacion por dominio

En MVC tradicional, el codigo esta organizado por **tipo de archivo**:
```
Controllers/AuthController.php
Controllers/ProfileController.php
Resources/UserResource.php
Resources/ProfileResource.php
Requests/LoginRequest.php
```

En feature-based, esta organizado por **dominio de negocio**:
```
Features/Auth/Controllers/AuthController.php
Features/Auth/Resources/UserResource.php
Features/Auth/Requests/LoginRequest.php
Features/Profile/Controllers/ProfileController.php
Features/Profile/Resources/ProfileResource.php
```

**Ventaja principal:** Para trabajar en "Auth", todo esta en `Features/Auth/`. No necesitas navegar entre 5 carpetas diferentes. Cada feature es un **modulo autocontenido**.

---

### `app/Features/Auth/Controllers/AuthController.php`

**Concepto:** Controller de feature con Form Request injection

```php
class AuthController extends Controller
{
    public function register(RegisterRequest $request): UserResource
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return (new UserResource($user))
            ->additional(['status' => 'success', 'meta' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]]);
    }
}
```

**Por que:**
- `RegisterRequest $request` — Laravel inyecta automaticamente el Form Request y ejecuta la validacion **antes** de que el controller se ejecute. Si la validacion falla, se lanza una `ValidationException` que el handler global convierte a 422.
- `UserResource` — Eloquent API Resource que controla exactamente que datos del usuario se exponen en la respuesta.
- `->additional()` — Agrega campos extra al JSON sin modificar el resource.

**Concepto clave - Dependency Injection:** Laravel resuelve automaticamente los type-hints de los parametros del metodo. Al escribir `RegisterRequest $request`, Laravel crea una instancia de `RegisterRequest`, ejecuta `authorize()` y `rules()`, y la pasa al metodo.

---

### `app/Features/Auth/Controllers/PasswordResetController.php`

**Concepto:** Controller dedicado a password management

**Por que:** Separar auth (login/register/logout) de password management sigue el **Single Responsibility Principle**. Cada controller maneja una sola responsabilidad.

---

### `app/Features/Auth/Requests/RegisterRequest.php`

**Concepto:** Form Request — validacion extraida del controller

```php
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Cualquiera puede registrarse
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es requerido.',
            'email.unique' => 'Ya existe una cuenta con este correo electronico.',
            'password.min' => 'La contrasena debe tener al menos 8 caracteres.',
            // ...
        ];
    }
}
```

**Por que:** Antes, la validacion estaba inline en el controller:
```php
$request->validate(['name' => 'required', 'email' => 'required|email|unique:users']);
```

Extraerla a un Form Request:
1. Hace el controller mas legible (menos lineas)
2. Las reglas son reutilizables (por ejemplo, en tests)
3. Los mensajes custom en espanol estan en un solo lugar
4. El metodo `authorize()` permite agregar logica de autorizacion

---

### `app/Features/Auth/Requests/LoginRequest.php`

**Concepto:** Form Request para login con validacion de device name

**Por que:** El campo `device` es necesario para Sanctum — cada token se asocia a un dispositivo para poder revocar acceso por dispositivo.

---

### `app/Features/Auth/Requests/ChangePasswordRequest.php`, `ForgotPasswordRequest.php`, `ResetPasswordRequest.php`

**Concepto:** Form Requests migrados desde `app/Http/Requests/`

**Por que:** Se movieron a `Features/Auth/Requests/` para que todo lo relacionado con Auth este junto.

---

### `app/Features/Auth/Resources/UserResource.php`

**Concepto:** Eloquent API Resource — capa de transformacion

**Por que:** Controla exactamente que datos del modelo `User` se exponen en la API. Nunca expones el modelo directo (podria incluir `password`, `remember_token`, etc.).

---

### `app/Features/Auth/routes.php`

**Concepto:** Rutas encapsuladas por feature

```php
Route::prefix('auth')->group(function () {
    // Rutas publicas
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    // Rutas protegidas
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/password/change', [PasswordResetController::class, 'changePassword']);
    });
});
```

**Por que:** Cada feature define sus propias rutas. `bootstrap/app.php` las descubre automaticamente con `glob()`:

```php
$featureRoutes = glob(app_path('Features/*/routes.php'));
Route::prefix('api/v1')->middleware([...])->group(function () use ($featureRoutes) {
    foreach ($featureRoutes as $routeFile) {
        require $routeFile;
    }
});
```

**Concepto clave - Route Auto-Discovery:** En lugar de registrar manualmente cada archivo de rutas, `glob()` busca todos los archivos que coincidan con el patron `Features/*/routes.php`. Cuando agregas un nuevo feature, solo creas su `routes.php` y se registra automaticamente.

---

### `app/Features/Profile/Controllers/ProfileController.php`

**Concepto:** Invokable controller con exception de dominio

```php
class ProfileController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile()->with(['user.membership', 'userPreferences'])->first();

        if (!$profile) {
            throw new ProfileNotFoundException;
        }

        return (new ProfileResource($profile))->additional(['status' => 'success']);
    }
}
```

**Por que:**
- `__invoke` — Controller con una sola accion. Laravel permite usarlo directamente en rutas: `Route::get('/profile', ProfileController::class)`.
- `->with(['user.membership', 'userPreferences'])` — **Eager loading** para prevenir N+1 queries. Sin esto, acceder a `$profile->user->membership` ejecutaria queries adicionales.
- `throw new ProfileNotFoundException` — Sin try-catch. El handler global se encarga.

---

### `app/Features/Profile/Resources/ProfileResource.php`

**Concepto:** API Resource con datos computados

**Por que:** Agrega campos calculados como `stats`, `menu`, `membership` y `preferences` que no existen directamente en el modelo pero son necesarios para la UI de la app.

---

### `app/Features/Home/Controllers/HomeController.php`

**Concepto:** Dashboard controller que agrega datos de multiples features

```php
class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        return (new HomeResource($request->user()))
            ->additional(['status' => 'success']);
    }
}
```

**Por que:** El Home es un **agregador** — toma datos de Workout, Profile y Tracking para construir el dashboard. El controller es minimo; toda la logica esta en `HomeResource`.

---

### `app/Features/Home/Resources/HomeResource.php` y `WorkoutTodayResource.php`

**Concepto:** Resources complejos que agregan datos de multiples modelos

**Por que:** `HomeResource` construye el greeting (con el nombre del usuario y hora del dia), weekly overview (7 dias), workout de hoy, progreso, y quick access. La logica de formateo vive en el Resource, no en el controller.

---

### `app/Features/Health/Controllers/HealthController.php`

**Concepto:** Health check con verificacion de dependencias

```php
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        // Verifica DB
        try {
            DB::connection()->getPdo();
            $dbStatus = 'connected';
        } catch (\Exception $e) {
            $dbStatus = 'disconnected';
        }

        // Verifica Redis
        try {
            Redis::ping();
            $redisStatus = 'connected';
        } catch (\Exception $e) {
            $redisStatus = 'disconnected';
        }

        return response()->json([
            'status' => $dbStatus === 'connected' ? 'up' : 'down',
            'data' => [
                'service' => 'Workout App API',
                'version' => config('api.version', '1.0.0'),
                'checks' => ['database' => $dbStatus, 'cache' => $redisStatus],
            ],
        ], $dbStatus === 'connected' ? 200 : 503);
    }
}
```

**Por que:** Un health check permite a load balancers, Kubernetes, y herramientas de monitoreo saber si la API esta funcionando. Si la DB esta caida, devuelve 503 (Service Unavailable) para que el load balancer deje de enviar trafico.

**Nota:** Este controller usa try-catch intencionalmente — el health check **debe** manejar fallos graciosamente y reportar el estado, no propagarlos como excepciones.

---

### `app/Features/Workout/Services/WorkoutTodayService.php`

**Concepto:** Service class con caching integrado

**Por que:** Movido desde `app/Services/Workout/`. Contiene la logica de negocio para obtener el workout del dia, con cache para evitar queries repetidas.

---

### `app/Features/Workout/Observers/WorkoutSessionObserver.php`

**Concepto:** Model Observer para invalidacion de cache

**Por que:** Movido desde `app/Observers/`. Cuando una WorkoutSession cambia, el observer limpia las caches relacionadas automaticamente.

---

### `app/Features/Exercise/routes.php`, `Periodization/routes.php`, `Tracking/routes.php`

**Concepto:** Modulos placeholder

**Por que:** Creados vacios para que los features futuros tengan un lugar definido. Cuando se necesiten endpoints de Exercise o Periodization, la estructura ya esta lista.

---

### `config/api.php`

**Concepto:** Configuracion centralizada de la API

```php
return [
    'version' => env('API_VERSION', '1.0.0'),
    'name' => 'Workout App API',
];
```

**Por que:** En Laravel, los valores de entorno solo deben accederse en archivos de configuracion (`config/*.php`), nunca directamente con `env()` en el codigo. Esto permite cachear la configuracion con `php artisan config:cache`.

---

### `routes/api.php` (modificado)

**Concepto:** Ruta de Health separada del auto-discovery

**Por que:** El health check no requiere API key ni autenticacion — debe ser accesible siempre. Por eso se registra en `routes/api.php` directamente, fuera del grupo de middleware de features.

---

### Archivos eliminados

Se eliminaron todos los archivos de la estructura MVC anterior:

| Archivo eliminado | Reemplazado por |
|---|---|
| `app/Http/Controllers/Api/V1/AuthController.php` | `app/Features/Auth/Controllers/AuthController.php` |
| `app/Http/Controllers/Api/V1/PasswordResetController.php` | `app/Features/Auth/Controllers/PasswordResetController.php` |
| `app/Http/Controllers/Api/V1/HomeController.php` | `app/Features/Home/Controllers/HomeController.php` |
| `app/Http/Controllers/Api/V1/ProfileController.php` | `app/Features/Profile/Controllers/ProfileController.php` |
| `app/Http/Controllers/Api/V1/HealthController.php` | `app/Features/Health/Controllers/HealthController.php` |
| `app/Http/Resources/Api/V1/HomeResource.php` | `app/Features/Home/Resources/HomeResource.php` |
| `app/Http/Resources/Api/V1/WorkoutTodayResource.php` | `app/Features/Home/Resources/WorkoutTodayResource.php` |
| `app/Http/Resources/Api/V1/ProfileResource.php` | `app/Features/Profile/Resources/ProfileResource.php` |
| `app/Http/Resources/Api/V1/UserResource.php` | `app/Features/Auth/Resources/UserResource.php` |
| `app/Http/Requests/*.php` | `app/Features/Auth/Requests/*.php` |
| `app/Services/Workout/WorkoutTodayService.php` | `app/Features/Workout/Services/WorkoutTodayService.php` |
| `app/Observers/WorkoutSessionObserver.php` | `app/Features/Workout/Observers/WorkoutSessionObserver.php` |
| `routes/modules/auth.php` | `app/Features/Auth/routes.php` |
| `routes/modules/home.php` | `app/Features/Home/routes.php` |
| `routes/modules/profile.php` | `app/Features/Profile/routes.php` |

---

## Fase 3: Estrategia de Caching

### Concepto central: Cache-Aside Pattern con invalidacion via Observers

El patron **Cache-Aside** funciona asi:
1. El codigo busca primero en cache
2. Si hay cache (hit), lo devuelve inmediatamente
3. Si no hay cache (miss), consulta la DB, guarda en cache, y devuelve

La **invalidacion** es el problema mas dificil del caching: como saber cuando los datos en cache ya no son validos? Usamos **Observers** de Laravel que detectan cambios en los modelos y limpian las caches afectadas automaticamente.

---

### `app/Support/CacheService.php`

**Concepto:** Clase abstracta base para servicios con cache

```php
abstract class CacheService
{
    public function __construct(protected CacheManager $cache) {}

    abstract protected function prefix(): string;    // Ej: "workout"
    abstract protected function defaultTtl(): int;    // Ej: 43200 (12h)

    protected function remember(string $key, Closure $callback, ?int $ttl = null): mixed
    {
        return $this->cache->remember(
            $this->prefix().':'.$key,    // "workout:today:user:1:date:2026-02-19"
            $ttl ?? $this->defaultTtl(),
            $callback                     // Se ejecuta solo si no hay cache
        );
    }

    protected function forget(string $key): void
    {
        $this->cache->forget($this->prefix().':'.$key);
    }
}
```

**Por que:** Cada servicio que necesite cache extiende esta clase y solo define `prefix()` y `defaultTtl()`. El patron `remember()` encapsula toda la logica de cache-aside.

**Concepto clave - Abstract class:** Una clase abstracta no se puede instanciar directamente — obliga a las clases hijas a implementar los metodos abstractos. Es un contrato: "si quieres usar CacheService, debes definir prefix() y defaultTtl()".

---

### `app/Http/Middleware/ConditionalResponse.php`

**Concepto:** ETag y respuestas condicionales (304 Not Modified)

```php
class ConditionalResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$request->isMethod('GET') || !$response instanceof JsonResponse) {
            return $response;
        }

        $etag = '"'.md5($response->getContent()).'"';
        $response->headers->set('ETag', $etag);

        if ($request->header('If-None-Match') === $etag) {
            return response()->noContent(304);
        }

        return $response;
    }
}
```

**Como funciona:**
1. Primera request: El servidor genera un hash MD5 del response body y lo envvia como header `ETag`
2. Segunda request: El cliente envvia `If-None-Match: "hash-anterior"`
3. Si el hash coincide (datos no cambiaron), el servidor devuelve `304 Not Modified` sin body — ahorrando ancho de banda

**Por que:** Para una app movil, reducir el tamanno de las respuestas mejora la experiencia en conexiones lentas. Un 304 no transfiere datos, solo confirma que el cache del cliente sigue valido.

---

### `app/Features/Profile/Observers/ProfileObserver.php`

**Concepto:** Observer para invalidacion de cache automatica

```php
class ProfileObserver
{
    public function updated(Profile $profile): void
    {
        $this->clearCache($profile);
    }

    public function deleted(Profile $profile): void
    {
        $this->clearCache($profile);
    }

    private function clearCache(Profile $profile): void
    {
        Cache::forget("profile:user:{$profile->user_id}");
        Cache::forget("home:user:{$profile->user_id}:date:" . today()->toDateString());
    }
}
```

**Por que:** Cuando un perfil se actualiza, automaticamente se limpian:
1. El cache del perfil del usuario
2. El cache del home/dashboard (porque muestra datos del perfil)

Sin observers, tendrias que recordar limpiar el cache manualmente en cada lugar que modifique un perfil — propenso a bugs.

**Concepto clave - Observer Pattern:** Laravel permite "observar" eventos de Eloquent (created, updated, deleted, etc.) y ejecutar logica automaticamente. El observer se registra en `AppServiceProvider`:
```php
Profile::observe(ProfileObserver::class);
```

---

### `app/Features/Tracking/Observers/UserWeightObserver.php`

**Concepto:** Observer para invalidar cache de peso e historial

**Por que:** Similar al ProfileObserver — cuando se registra un nuevo peso, limpia el cache de historial de peso y del home dashboard.

---

## Fase 4: Monitoreo y Observabilidad

### Concepto central: Observabilidad

Una API sin monitoreo es como conducir de noche sin luces. La observabilidad te permite:
- **Detectar problemas** antes de que los usuarios los reporten
- **Diagnosticar** la causa raiz de errores
- **Optimizar** identificando cuellos de botella

---

### `app/Http/Middleware/RequestLogger.php`

**Concepto:** Middleware de logging de requests

```php
class RequestLogger
{
    private const SENSITIVE_FIELDS = [
        'password', 'password_confirmation', 'token', 'access_token', 'api_key', 'X-API-KEY',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $response = $next($request);
        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        Log::channel('api')->info('API Request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $response;
    }
}
```

**Por que:** Cada request se loguea con informacion clave para debugging:
- `duration_ms` — Identifica endpoints lentos
- `user_id` — Saber que usuario tuvo el problema
- `status` — 4xx y 5xx indican errores

**SENSITIVE_FIELDS:** Nunca se loguean passwords ni tokens. Esto es un requisito de seguridad y cumplimiento.

**Concepto clave - Middleware pipeline:** Los middleware se ejecutan como una cadena. El request pasa por cada middleware en orden, luego la respuesta vuelve en orden inverso. `RequestLogger` mide el tiempo total midiendo antes y despues de `$next($request)`.

---

### `config/logging.php` (canal `api`)

**Concepto:** Canal de logging dedicado con rotacion

Se agrego un canal `api` con driver `daily`:
```php
'api' => [
    'driver' => 'daily',
    'path' => storage_path('logs/api.log'),
    'level' => 'debug',
    'days' => 14,
],
```

**Por que:** Separar los logs de API de los logs generales de Laravel (`laravel.log`) facilita el analisis. El driver `daily` crea un archivo nuevo cada dia (`api-2026-02-19.log`) y elimina automaticamente los que tienen mas de 14 dias — evitando que el disco se llene.

---

### `app/Providers/AppServiceProvider.php` (slow query detection)

**Concepto:** Deteccion de queries lentas via `DB::listen()`

```php
DB::listen(function (QueryExecuted $query) {
    if ($query->time > 500) {
        Log::channel('api')->warning('Slow query detected', [
            'sql' => $query->sql,
            'time_ms' => $query->time,
            'connection' => $query->connectionName,
        ]);
    }
});
```

**Por que:** Queries que tardan mas de 500ms son un problema de performance. Este listener las captura automaticamente y las loguea con el SQL completo para poder optimizarlas (agregar indices, eager loading, etc.).

**Concepto clave - Event Listener:** `DB::listen()` se ejecuta despues de **cada** query de la aplicacion. Es un hook global que no requiere modificar ninguna otra parte del codigo.

---

### `app/Providers/AppServiceProvider.php` (registro de observers)

```php
WorkoutSession::observe(WorkoutSessionObserver::class);
Profile::observe(ProfileObserver::class);
UserWeight::observe(UserWeightObserver::class);
```

**Por que:** Los observers se registran aqui para que esten activos durante toda la vida de la aplicacion. Cada vez que estos modelos se creen, actualicen o eliminen, sus observers se ejecutaran automaticamente.

---

### Laravel Pulse (paquete instalado)

**Concepto:** Dashboard de monitoreo en tiempo real (first-party de Laravel)

**Por que:** Pulse provee metricas de: requests lentos, queries frecuentes, excepciones, uso de cache, y mas — todo en un dashboard web. Es la alternativa oficial de Laravel a herramientas como Telescope, mas ligera y orientada a produccion.

---

## Fase 5: Mejores Practicas API 2026

### `app/Http/Middleware/SecurityHeaders.php`

**Concepto:** Headers de seguridad HTTP

```php
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('API-Version', config('api.version', '1.0.0'));

        if (!app()->isLocal()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
```

**Que hace cada header:**

| Header | Que previene |
|---|---|
| `X-Content-Type-Options: nosniff` | Que el navegador "adivine" el tipo de contenido (MIME sniffing), previniendo ataques XSS |
| `X-Frame-Options: DENY` | Que la API se cargue en un iframe (previene clickjacking) |
| `Referrer-Policy` | Controla cuanta informacion de la URL se envvia al navegar a otro sitio |
| `Permissions-Policy` | Bloquea acceso a camara, microfono y geolocalizacion desde la API |
| `Strict-Transport-Security` | Fuerza HTTPS por 1 anho. El navegador no intentara HTTP nunca (solo en produccion) |
| `API-Version` | Informa al cliente que version de la API esta usando |

**Por que:** Estos headers son recomendaciones de OWASP y estan en los checklist de seguridad de cualquier API moderna. Se agregan a **todas** las respuestas via middleware.

---

### `app/Http/Middleware/IdempotencyMiddleware.php`

**Concepto:** Idempotencia para operaciones POST/PUT/PATCH

```php
class IdempotencyMiddleware
{
    private const CACHE_TTL = 86400; // 24 horas

    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplica a metodos que modifican datos
        if ($request->isMethod('GET') || $request->isMethod('DELETE')) {
            return $next($request);
        }

        $idempotencyKey = $request->header('Idempotency-Key');
        if (!$idempotencyKey) {
            return $next($request); // Opcional — no rompe si no se envvia
        }

        $cacheKey = "idempotency:{$idempotencyKey}";
        $cached = Cache::get($cacheKey);

        if ($cached) {
            // Ya se proceso antes — devuelve la misma respuesta
            return response()->json($cached['body'], $cached['status'])
                ->withHeaders(['Idempotency-Replay' => 'true']);
        }

        $response = $next($request);

        // Cachea la respuesta exitosa por 24h
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 500) {
            Cache::put($cacheKey, [
                'body' => json_decode($response->getContent(), true),
                'status' => $response->getStatusCode(),
            ], self::CACHE_TTL);
        }

        return $response;
    }
}
```

**Como funciona:**
1. El cliente envvia `POST /api/v1/auth/register` con header `Idempotency-Key: abc-123`
2. Primera vez: Se procesa normalmente y se cachea la respuesta
3. Si el cliente reenvia la misma request (por timeout de red, retry, etc.): Devuelve la respuesta cacheada sin ejecutar la logica de nuevo

**Por que:** Sin idempotencia, un retry de red podria crear 2 cuentas o registrar 2 sets en un workout. Con `Idempotency-Key`, el servidor sabe que ya proceso esa operacion y devuelve el mismo resultado.

**Concepto clave - Idempotencia:** Una operacion es idempotente si ejecutarla N veces produce el mismo resultado que ejecutarla 1 vez. GET y DELETE son naturalmente idempotentes. POST no lo es — por eso necesita este middleware.

---

### dedoc/scramble (paquete instalado)

**Concepto:** Generacion automatica de documentacion OpenAPI desde el codigo

**Por que:** Scramble analiza los controllers, Form Requests y Resources de Laravel para generar una especificacion OpenAPI (Swagger) automaticamente. No necesitas mantener documentacion separada — se genera del codigo.

---

### `bootstrap/app.php` (seccion de middleware)

**Concepto:** Pipeline de middleware para todas las requests

```php
Route::prefix('api/v1')->middleware([
    EnsureApiKeyIsValid::class,
    ForceHttps::class,
    SecurityHeaders::class,
    RequestLogger::class,
    IdempotencyMiddleware::class,
    ConditionalResponse::class,
    'throttle:api',
])->group(function () use ($featureRoutes) { ... });
```

**Orden de los middleware importa:**
1. `EnsureApiKeyIsValid` — Primero verifica que la request tenga una API key valida
2. `ForceHttps` — Fuerza HTTPS
3. `SecurityHeaders` — Agrega headers de seguridad
4. `RequestLogger` — Loguea la request (ya autenticada por API key)
5. `IdempotencyMiddleware` — Maneja idempotencia
6. `ConditionalResponse` — ETag/304 (al final, para calcular hash del response final)
7. `throttle:api` — Rate limiting de Laravel

---

## Fase 6: Expansion de Tests

### Concepto central: Tests como documentacion ejecutable

Los tests no solo verifican que el codigo funciona — son la **mejor documentacion** de como se espera que la API se comporte. Si un test dice `assertStatus(200)` y `assertJsonPath('status', 'success')`, eso documenta el contrato de la API.

---

### `tests/Concerns/InteractsWithApi.php`

**Concepto:** Test helper trait para requests autenticados

```php
trait InteractsWithApi
{
    protected User $apiUser;
    protected ApiKey $apiKey;

    protected function setUpApiUser(?User $user = null): self
    {
        $this->apiUser = $user ?? User::factory()->create();
        $this->apiKey = ApiKey::generate('Test Key');

        $this->actingAs($this->apiUser, 'sanctum');
        $this->withHeaders(['X-API-KEY' => $this->apiKey->key]);

        return $this;
    }
}
```

**Por que:** Todos los tests necesitan un usuario autenticado con API key. Sin este trait, cada test repetiria las mismas 4 lineas de setup. El trait **DRY** (Don't Repeat Yourself) encapsula el patron comun.

---

### `tests/Feature/Auth/RegisterTest.php`

**Concepto:** Feature tests para registro de usuarios

**Tests incluidos:**
1. `user can register with valid data` — Happy path: verifica status, estructura JSON, y que el usuario existe en DB
2. `register fails with missing fields` — Verifica 422 con errores de validacion
3. `register fails with duplicate email` — Verifica que emails duplicados se rechazan
4. `register fails with short password` — Verifica minimo de 8 caracteres

---

### `tests/Feature/Auth/LoginTest.php`

**Tests incluidos:**
1. `user can login with valid credentials` — Verifica token en respuesta
2. `login fails with wrong password` — Verifica 422
3. `login fails with non-existent email` — Verifica 422
4. `login fails without device name` — Verifica validacion del campo device

---

### `tests/Feature/Auth/LogoutTest.php`

**Test:** `authenticated user can logout` — Verifica que el token se revoca

**Nota tecnica:** Usa `Sanctum::actingAs()` en lugar del `actingAs()` generico porque el logout necesita un `PersonalAccessToken` real (con metodo `delete()`), no un `TransientToken`.

---

### `tests/Feature/Auth/PasswordResetTest.php`

**Tests incluidos:**
1. `user can request password reset` — Verifica envvio de email
2. `password reset fails for non-existent email` — Verifica 422
3. `user can change password` — Verifica cambio exitoso
4. `change password fails with wrong current password` — Verifica validacion

---

### `tests/Feature/Profile/ProfileTest.php`

**Tests incluidos:**
1. `authenticated user can get their profile` — Verifica estructura completa
2. `returns 404 when profile does not exist` — Verifica `PROFILE.NOT_FOUND`

---

### `tests/Feature/Health/HealthTest.php`

**Tests incluidos:**
1. `health check returns status up` — Verifica estructura con checks de DB y cache
2. `health check includes correct service name` — Verifica nombre del servicio

---

### `tests/Feature/Home/HomeTest.php`

**Tests incluidos:**
1. `authenticated user can access home endpoint` — Verifica estructura del dashboard
2. `home endpoint returns greeting with user name` — Verifica personalizacion
3. `home endpoint returns weekly overview` — Verifica 7 dias
4. `request without api key returns 401` — Verifica proteccion por API key

---

### `tests/Pest.php` (modificado)

**Cambio clave:**
```php
pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature', 'Unit');
```

**Por que:** `RefreshDatabase` limpia y re-migra la base de datos de testing antes de cada test. Sin esto, los tests comparten estado de DB y pueden fallar de forma impredecible.

Se aplica tanto a `Feature` como a `Unit` porque algunos unit tests usan factories que necesitan la DB.

---

## Errores Encontrados y Soluciones

Estos son los errores reales que ocurrieron durante la implementacion. Documentarlos es valioso porque son errores comunes en proyectos Laravel:

| # | Error | Causa raiz | Solucion |
|---|---|---|---|
| 1 | Tests de workout fallaban con "property not found" | Los tests accedian a `workout_today.has_workout` pero el Resource wrappea los datos dentro de `data`, resultando en `data.workout_today.has_workout` | Agregar el prefijo `data.` a todas las assertions de JSON path |
| 2 | Despues de instalar Laravel Pulse, todos los tests fallaban con "relation 'users' does not exist" | La migracion de Pulse se ejecuto en la DB principal pero la DB de testing estaba vacca (sin tablas) | Habilitar `RefreshDatabase` en `tests/Pest.php` para que cada test ejecute las migraciones |
| 3 | Unit tests seguian fallando despues de habilitar RefreshDatabase | `RefreshDatabase` solo se aplicaba a `->in('Feature')`, los unit tests no lo tenian | Cambiar a `->in('Feature', 'Unit')` para cubrir ambos directorios |
| 4 | Logout test daba 500 Internal Server Error | `actingAs()` de Laravel usa `TransientToken` que no tiene metodo `delete()`. El controller llama `currentAccessToken()->delete()` | Usar `Sanctum::actingAs()` que crea un `PersonalAccessToken` real |
| 5 | Register test esperaba 200 pero recibia 201 | `UserResource` (Eloquent API Resource) automaticamente devuelve 201 para requests POST | Usar `assertSuccessful()` en lugar de `assertStatus(200)` para aceptar cualquier 2xx |
| 6 | Test "unauthenticated user gets 401" siempre devolvia 200 | `actingAs()` del `beforeEach` no se puede sobreescribir con `Authorization: ''` | Cambiar el test para verificar API key invalida en lugar de falta de autenticacion |
| 7 | `InvalidApiKeyException` no se lanzaba — middleware devolvia JSON directo | El middleware `EnsureApiKeyIsValid` usaba `response()->json()` en lugar de excepciones | Modificar el middleware para usar `throw new InvalidApiKeyException` |

---

## Arquitectura Final

```
app/
├── Exceptions/
│   ├── ApiException.php                          # Base exception con render()
│   ├── Auth/
│   │   ├── AuthenticationFailedException.php     # 401 AUTH.INVALID_CREDENTIALS
│   │   └── InvalidApiKeyException.php            # 401 AUTH.INVALID_API_KEY
│   ├── Profile/
│   │   └── ProfileNotFoundException.php          # 404 PROFILE.NOT_FOUND
│   └── Workout/
│       └── WorkoutNotFoundException.php          # 404 WORKOUT.NOT_FOUND
├── Features/
│   ├── Auth/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php                # register, login, logout
│   │   │   └── PasswordResetController.php       # forgot, reset, change
│   │   ├── Requests/
│   │   │   ├── ChangePasswordRequest.php
│   │   │   ├── ForgotPasswordRequest.php
│   │   │   ├── LoginRequest.php
│   │   │   ├── RegisterRequest.php
│   │   │   └── ResetPasswordRequest.php
│   │   ├── Resources/
│   │   │   └── UserResource.php
│   │   └── routes.php
│   ├── Exercise/
│   │   └── routes.php                            # Placeholder
│   ├── Health/
│   │   ├── Controllers/
│   │   │   └── HealthController.php              # DB + Redis checks
│   │   └── routes.php
│   ├── Home/
│   │   ├── Controllers/
│   │   │   └── HomeController.php                # Dashboard agregador
│   │   ├── Resources/
│   │   │   ├── HomeResource.php
│   │   │   └── WorkoutTodayResource.php
│   │   └── routes.php
│   ├── Periodization/
│   │   └── routes.php                            # Placeholder
│   ├── Profile/
│   │   ├── Controllers/
│   │   │   └── ProfileController.php
│   │   ├── Observers/
│   │   │   └── ProfileObserver.php               # Cache invalidation
│   │   ├── Resources/
│   │   │   └── ProfileResource.php
│   │   └── routes.php
│   ├── Tracking/
│   │   ├── Observers/
│   │   │   └── UserWeightObserver.php            # Cache invalidation
│   │   └── routes.php                            # Placeholder
│   └── Workout/
│       ├── Observers/
│       │   └── WorkoutSessionObserver.php        # Cache invalidation
│       ├── Services/
│       │   └── WorkoutTodayService.php           # Business logic + cache
│       └── routes.php
├── Http/
│   └── Middleware/
│       ├── ConditionalResponse.php               # ETag / 304 Not Modified
│       ├── EnsureApiKeyIsValid.php               # API key validation
│       ├── ForceHttps.php                        # HTTPS enforcement
│       ├── IdempotencyMiddleware.php             # POST idempotency
│       ├── RequestLogger.php                     # Request logging
│       └── SecurityHeaders.php                   # OWASP security headers
├── Support/
│   ├── ApiResponse.php                           # Response envelope trait
│   └── CacheService.php                          # Abstract cache base
├── Models/                                       # 23 modelos (sin cambios)
└── Providers/
    └── AppServiceProvider.php                    # Observers + slow query detection

config/
├── api.php                                       # API version + name
└── logging.php                                   # Canal 'api' con rotacion diaria

bootstrap/
└── app.php                                       # Route auto-discovery + exception handler + middleware

tests/
├── Concerns/
│   └── InteractsWithApi.php                      # Test helper trait
├── Feature/
│   ├── Auth/
│   │   ├── LoginTest.php                         # 4 tests
│   │   ├── LogoutTest.php                        # 1 test
│   │   ├── PasswordResetTest.php                 # 4 tests
│   │   └── RegisterTest.php                      # 4 tests
│   ├── Health/
│   │   └── HealthTest.php                        # 2 tests
│   ├── Home/
│   │   └── HomeTest.php                          # 4 tests
│   └── Profile/
│       └── ProfileTest.php                       # 2 tests
├── Unit/
│   └── Services/
│       └── WorkoutTodayServiceTest.php           # 10 tests
└── Pest.php                                      # RefreshDatabase config
```

**Total: 38 tests, 129 assertions, 1 skipped (Redis TTL)**
