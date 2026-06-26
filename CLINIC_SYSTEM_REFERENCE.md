# Clinic Management System — Complete Reference Manual

**Version:** June 2025
**Repositories:** `clinic-app-laravel-backend` (Laravel 13) + `clinic-app-laravel-frontend` (Vue 3)

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Backend: Routing System](#2-backend-routing-system)
3. [Backend: JWT Multi-Guard Authentication](#3-backend-jwt-multi-guard-authentication)
4. [Backend: CoreService Pattern](#4-backend-coreservice-pattern)
5. [Backend: CallService — Entry Points](#5-backend-callservice--entry-points)
6. [Backend: CRUD Engine](#6-backend-crud-engine)
7. [Backend: Models & FIELD Constants](#7-backend-models--field-constants)
8. [Backend: File Upload System](#8-backend-file-upload-system)
9. [Backend: Permissions](#9-backend-permissions)
10. [Backend: Middleware Stack](#10-backend-middleware-stack)
11. [Backend: Scoped Services](#11-backend-scoped-services)
12. [Backend: Auth Services](#12-backend-auth-services)
13. [Backend: Database Schema](#13-backend-database-schema)
14. [Backend: Seeders](#14-backend-seeders)
15. [Frontend: Project Structure](#15-frontend-project-structure)
16. [Frontend: Router & Route Guards](#16-frontend-router--route-guards)
17. [Frontend: HTTP Client & JWT Interceptor](#17-frontend-http-client--jwt-interceptor)
18. [Frontend: Auth Stores](#18-frontend-auth-stores)
19. [Frontend: Storage Utilities](#19-frontend-storage-utilities)
20. [Frontend: Config-Driven CRUD](#20-frontend-config-driven-crud)
21. [Frontend: Entity Configs](#21-frontend-entity-configs)
22. [Frontend: Shared Components](#22-frontend-shared-components)
23. [Frontend: Portal Views](#23-frontend-portal-views)
24. [Frontend: Admin Views](#24-frontend-admin-views)
25. [Frontend: File Upload](#25-frontend-file-upload)
26. [Data Flow Diagrams](#26-data-flow-diagrams)
27. [API Reference](#27-api-reference)
28. [Critical Gotchas](#28-critical-gotchas)

---

## 1. Architecture Overview

### Philosophy
- **Service-oriented backend:** Every business operation is a `CoreService` class with `prepare()` → `process()` pipeline.
- **Config-driven CRUD:** Frontend renders tables and forms from per-entity TypeScript config files without writing boilerplate.
- **Multi-guard JWT:** Three separate auth guards (`api`, `doctor`, `patient`) share a single `/login` endpoint via auto-detection.
- **DB::table() queries:** CRUD services use raw query builder, not Eloquent ORM, for performance and explicit control.

### Repository Map
| Repo | Stack | Path |
|------|-------|------|
| Backend | Laravel 13, PHP 8.3, MySQL, tymon/jwt-auth | `D:\Zka\Documents\projects\clinic-laravel` |
| Frontend | Vue 3, TypeScript, Pinia, Tailwind CSS 4, Vite | `D:\Zka\Documents\projects\clinic-admin` |

### Tech Stack Summary
- **Backend:** Laravel 13, tymon/jwt-auth 2.3, PHP 8.3, MySQL
- **Frontend:** Vue 3.5, TypeScript, Pinia, Vue Router 4, Tailwind CSS 4, Vite 8
- **Auth:** JWT (three guards), `SetGuard` middleware, unified login

---

## 2. Backend: Routing System

### Service Route Registration
All named API routes are defined in `config/service.php` and auto-registered in `routes/api.php:9-22`. Do NOT manually add routes for standard service endpoints.

**`config/service.php`** — Each entry has: `name`, `class`, `type` (HTTP method), `end_point`, `guard` (null = public).

```php
// Example entries from config/service.php
[
    'name' => 'DoLogin',
    'class' => \App\Services\Auth\DoUnifiedLogin::class,
    'type' => 'post',
    'end_point' => '/login',
    'guard' => null,  // public — no auth required
],
[
    'name' => 'Me',
    'class' => \App\Services\Auth\Me::class,
    'type' => 'get',
    'end_point' => '/me',
    'guard' => 'api',
],
[
    'name' => 'DoctorAppointments',
    'class' => \App\Services\Crud\DoctorAppointments::class,
    'type' => 'get',
    'end_point' => '/doctor/appointments',
    'guard' => 'doctor',
],
```

**`routes/api.php`** — Dynamic registration loop:

```php
foreach (config('service.services', []) as $service) {
    if (empty($service['end_point']) || empty($service['type'])) continue;

    $serviceName = $service['name'];
    $middleware = !empty($service['guard'])
        ? ["setguard:{$service['guard']}", 'auth.rest']
        : [];

    Route::match([$service['type']], $service['end_point'], function () use ($serviceName) {
        return CallService::run($serviceName, request()->all());
    })->middleware($middleware);
}
```

Key insight: If `guard` is null, the route gets NO middleware — it's public. Otherwise it gets `setguard:{guard}` first (sets `auth.defaults.guard`), then `auth.rest` (validates JWT).

### CRUD Routes (Generic)
Routes at `routes/api.php:24-31`, all behind `['setguard:api', 'auth.rest']`:

```
GET  /{model}/list      → CrudController::index    → App\Services\Crud\Get
GET  /{model}/dataset    → CrudController::dataset  → App\Services\Crud\Get (same)
GET  /{model}/{id}/show  → CrudController::show     → App\Services\Crud\Find
POST /{model}/create     → CrudController::create   → App\Services\Crud\Add
PUT  /{model}/update     → CrudController::update   → App\Services\Crud\Edit
DELETE /{model}/delete   → CrudController::delete   → App\Services\Crud\Delete
```

### Guarded Upload Routes
Upload is guard-aware — three separate routes exist:

```php
// Admin (api guard)
Route::middleware(['setguard:api', 'auth.rest'])->group(function () {
    Route::post('/upload-tmp', [UploadController::class, 'upload']);
});

// Doctor
Route::middleware(['setguard:doctor', 'auth.rest'])->group(function () {
    Route::post('/doctor/upload-tmp', [UploadController::class, 'upload']);
});

// Patient
Route::middleware(['setguard:patient', 'auth.rest'])->group(function () {
    Route::post('/patient/upload-tmp', [UploadController::class, 'upload']);
});
```

### Doctor/Patient /me Routes
Inline closures in the guard group:

```php
Route::middleware(['setguard:doctor', 'auth.rest'])->group(function () {
    Route::get('/doctor/me', function () {
        $doctor = Auth::user();
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $doctor->id,
                'fullname' => $doctor->fullname,
                'specialization' => $doctor->specialization,
                'email' => $doctor->email,
                'phone' => $doctor->phone,
                'photo' => $doctor->photo,
                'available' => $doctor->available,
            ],
        ]);
    });
});
// Same pattern for /patient/me
```

### File Serving Route
```php
Route::get('/file/{path}', function ($path) {
    if (!Storage::disk('document')->exists($path)) abort(404);
    return Storage::disk('document')->response($path);
})->where('path', '.*')->name('file.read');
```

### Service Binding
`AppServiceProvider` binds short service names to FQCNs:

```php
public function register(): void
{
    foreach (config('service.services', []) as $service) {
        if (!empty($service['name']) && !empty($service['class'])) {
            $this->app->bind($service['name'], $service['class']);
        }
    }
}
```

---

## 3. Backend: JWT Multi-Guard Authentication

### Three Guards
| Guard | Model | Table | JWT Subject |
|-------|-------|-------|-------------|
| `api` | `App\Models\Users` | `users` | Admin/staff users with `role_id` |
| `doctor` | `App\Models\Doctors` | `doctors` | Doctors (no `role_id`) |
| `patient` | `App\Models\Patients` | `patients` | Patients (no `role_id`) |

### SetGuard Middleware (`app/Http/Middleware/SetGuard.php`)
Switches `auth.defaults.guard` BEFORE JWT validation:

```php
public function handle(Request $request, Closure $next, string $guard)
{
    Config::set('auth.defaults.guard', $guard);
    return $next($request);
}
```

### AuthApiMiddleware (`app/Http/Middleware/AuthApiMiddleware.php`)
Validates JWT after guard is set:

```php
public function handle(Request $request, Closure $next)
{
    try {
        JWTAuth::parseToken()->authenticate();
    } catch (TokenExpiredException $e) {
        return response()->json(['success' => false, 'message' => 'Token expired.', 401]);
    } catch (TokenInvalidException $e) {
        return response()->json(['success' => false, 'message' => 'Token invalid.', 401]);
    } catch (JWTException $e) {
        return response()->json(['success' => false, 'message' => 'Token not found.', 401]);
    }
    return $next($request);
}
```

### Middleware Aliases (`bootstrap/app.php`)
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'setguard' => SetGuard::class,
        'auth.rest' => AuthApiMiddleware::class,
    ]);
})
```

### Unified Login (`DoUnifiedLogin.php`)

The critical service — auto-detects user type by trying `Users` (by email OR username), then `Doctors`, then `Patients`:

```php
protected function prepare($input)
{
    // Try admin (Users) — matches by email OR username
    $admin = Users::where('email', $input['email'])
        ->orWhere('username', $input['email'])
        ->first();

    if ($admin && Hash::check($input['password'], $admin->password)) {
        if (!$admin->active) throw new CoreException(__('message.accountInactive'), 403);
        $input['_guard'] = 'api';
        $input['_user'] = $admin;
        $input['_role'] = 'admin';
        $input['_data'] = [
            'id' => $admin->id,
            'fullname' => $admin->fullname,
            'username' => $admin->username,
            'email' => $admin->email,
            'role_id' => $admin->role_id,
            'photo' => $admin->photo,
        ];
        return $input;
    }

    // Try doctor
    $doctor = Doctors::where('email', $input['email'])->first();
    if ($doctor && Hash::check($input['password'], $doctor->password)) { ... }

    // Try patient
    $patient = Patients::where('email', $input['email'])->first();
    if ($patient && Hash::check($input['password'], $patient->password)) { ... }

    throw new CoreException(__('message.invalidCredentials'), 401);
}

protected function process($input, $originalData)
{
    Config::set('auth.defaults.guard', $input['_guard']);
    $token = JWTAuth::fromUser($input['_user']);

    return [
        'success' => true,
        'token' => $token,
        'role' => $input['_role'],
        'data' => $input['_data'],
    ];
}
```

Login response includes `role` field so the frontend knows which portal to route to.

### Individual Login Services
- `DoLoginDoctor.php` — dedicated `/doctor/login` (guard=null in config)
- `DoLoginPatient.php` — dedicated `/patient/login` (guard=null)
- `DoLogout.php` — invalidates current JWT for any guard

### Me Service (`app/Services/Auth/Me.php`)
Returns current admin user profile + permissions list:

```php
protected function process($input, $originalData)
{
    $user = Auth::user();

    if ((int) $user->role_id === 1) {
        // Super admin gets ALL tasks
        $permissions = DB::table('tasks')->where('active', true)->pluck('task_code')->toArray();
    } else {
        $permissions = DB::table('role_task')
            ->join('tasks', 'tasks.id', '=', 'role_task.task_id')
            ->where('role_task.role_id', $user->role_id)
            ->where('role_task.active', true)
            ->where('tasks.active', true)
            ->pluck('tasks.task_code')->toArray();
    }

    return [
        'success' => true,
        'data' => [ ... user fields ... ],
        'permissions' => $permissions,
    ];
}
```

---

## 4. Backend: CoreService Pattern

### Base Class (`app/CoreService/CoreService.php`)

Every service extends this abstract class:

```php
abstract class CoreService implements DefaultService
{
    abstract protected function prepare($input);
    abstract protected function process($input, $originalData);

    public function execute($input)
    {
        $originalInput = $input;
        try {
            $validation = Validator::make($input, $this->validation());
            if ($validation->fails()) throw new CoreException($validation->errors()->first());

            $inputNew = $this->prepare($input);
            $result = $this->process(is_array($inputNew) ? $inputNew : $input, $originalInput);
        } catch (CoreException $ex) {
            throw $ex;
        } catch (Exception $ex) {
            Log::debug($ex->getMessage());
            throw new CoreException($ex->getMessage());
        }
        return $result;
    }

    protected function validation() { return []; }  // override to add rules
}
```

Execution order:
1. `validation()` → Laravel Validator (skip if returns empty array)
2. `prepare($input)` → sanitize, validate business rules, throw `CoreException` on error
3. `process($input, $originalData)` → perform the actual operation, return result array

### CoreException (`app/CoreService/CoreException.php`)
Custom exception with message, statusCode (default 422), and optional errorList:

```php
class CoreException extends Exception
{
    private $errorMessage;
    private $errorList;
    private $statusCode;

    public function __construct($errorMessage = "", $statusCode = 422, $errorList = [])
    {
        $this->errorMessage = $errorMessage;
        $this->errorList = $errorList;
        $this->statusCode = $statusCode;
    }
}
```

### CoreResponse (`app/CoreService/CoreResponse.php`)
Three static methods wrap service output into JSON responses:

```php
class CoreResponse
{
    public static function ok($output, $message = "")
    {
        return response()->json($output, 200);
    }

    public static function fail($ex)  // CoreException → JSON error
    {
        $result = ["success" => false];
        if(!empty($ex->getErrorMessage())) $result["message"] = $ex->getErrorMessage();
        if(!empty($ex->getErrorList())) $result = array_merge($result, $ex->getErrorList());
        return response()->json($result, $ex->getErrorCode());
    }

    public static function error($ex)  // generic Exception → JSON error
    {
        $result["success"] = false;
        if (!empty($ex->getErrorMessage())) $result["message"] = $ex->getErrorMessage();
        $result["error_code"] = $ex->getErrorCode();
        return response()->json($result, $ex->getErrorCode());
    }
}
```

---

## 5. Backend: CallService — Entry Points

### `app/CoreService/CallService.php`

Three methods for calling services:

```php
class CallService
{
    // Used by HTTP route handlers — catches everything, returns JSON
    public static function run(string $serviceName, $input)
    {
        try {
            $result = self::call($serviceName, $input);
            return CoreResponse::ok($result);
        } catch (CoreException $ex) {
            return CoreResponse::fail($ex);
        } catch (\Exception $ex) {
            return CoreResponse::error(new CoreException($ex->getMessage(), 500));
        }
    }

    // Same as run() but checks permission via $object->task
    public static function execute(string $serviceName, $input)
    {
        try {
            $object = app()->make($serviceName);
            if (isset($object->task) && !hasPermission($object->task)) {
                throw new CoreException(__('message.403'), 403);
            }
            $result = self::call($serviceName, $input);
            return CoreResponse::ok($result);
        } catch (CoreException $ex) {
            return CoreResponse::fail($ex);
        } catch (\Exception $ex) {
            return CoreResponse::error(new CoreException($ex->getMessage(), 500));
        }
    }

    // Raw execution — throws on error, no JSON wrapping. Used between services.
    public static function call(string $serviceName, $input)
    {
        $object = app()->make($serviceName);

        // Transaction wrapper — if $object->transaction is true
        if (!empty($object->transaction)) {
            DB::beginTransaction();
            try {
                $result = $object->execute($input);
                DB::commit();
                return $result;
            } catch (\Throwable $ex) {
                DB::rollBack();
                throw $ex;
            }
        }

        return $object->execute($input);
    }
}
```

Usage patterns:
- `CallService::run('Me', ...)` — route handler: auto-returns JSON
- `CallService::execute('SomeService', ...)` — route handler: checks `task` permission first
- `CallService::call('App\Services\Crud\Find', ...)` — internal: used by Add/Edit for post-create/update lookup

---

## 6. Backend: CRUD Engine

### CrudController (`app/Http/Controllers/CrudController.php`)

6 methods, all delegate to CRUD service FQCNs via `CallService::run()`:

```php
public function index(Request $request, string $model)
{
    return CallService::run('App\Services\Crud\Get', array_merge(
        $request->all(), ['model' => $model]
    ));
}
// dataset() — same as index()
// show()    → 'App\Services\Crud\Find'  + ['id' => $id]
// create()  → 'App\Services\Crud\Add'
// update()  → 'App\Services\Crud\Edit'
// delete()  → 'App\Services\Crud\Delete'
```

Model names are resolved via `Str::studly($model)` → `App\Models\{Name}`.

### Get Service (`app/Services/Crud/Get.php`)

The core listing service. Key logic:

```php
protected function prepare($input)
{
    $modelName = Str::studly($input['model']);
    $modelClass = "App\\Models\\{$modelName}";

    if (!class_exists($modelClass)) throw new CoreException("Model {$modelName} not found.", 404);
    if (!$modelClass::IS_LIST) throw new CoreException("Listing is not allowed.", 403);
    if (!hasPermission("view-{$input['model']}")) throw new CoreException(__('message.403'), 403);

    $this->modelClass = $modelClass;
    return $input;
}

protected function process($input, $originalData)
{
    $modelClass = $this->modelClass;
    $table = $modelClass::TABLE;

    // Select from FIELD_LIST with table prefix
    $query = DB::table($table)->select(
        array_map(fn($f) => "{$table}.{$f}", $modelClass::FIELD_LIST)
    );

    // LEFT JOIN relations based on FIELD_RELATION
    foreach ($modelClass::FIELD_RELATION as $field => $relation) {
        $query->leftJoin(
            "{$relation['linkTable']} as {$relation['aliasTable']}",
            "{$table}.{$field}", '=', "{$relation['aliasTable']}.{$relation['linkField']}"
        );
        $query->selectRaw("CONCAT_WS('', " . implode(', ', ...) . ") as {$relation['displayName']}");
    }

    // Search across FIELD_SEARCHABLE
    if (!empty($input['search'])) { ... }

    // Filter based on FIELD_FILTERABLE
    foreach ($modelClass::FIELD_FILTERABLE as $field => $config) {
        if (isset($input[$field]) && $input[$field] !== '') {
            $query->where("{$table}.{$field}", $config['operator'], $input[$field]);
        }
    }

    // Sort — fallback to id asc if field not in FIELD_SORTABLE
    $sortField = $input['sort'] ?? 'id';
    $sortDir = $input['order'] ?? 'asc';
    if (in_array($sortField, $modelClass::FIELD_SORTABLE)) {
        $query->orderBy("{$table}.{$sortField}", $sortDir === 'desc' ? 'desc' : 'asc');
    } else {
        $query->orderBy("{$table}.id", 'asc');
    }

    return [
        'success' => true,
        'data' => $rows,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'last_page' => (int) ceil($total / max($limit, 1)),
    ];
}
```

### Find Service (`app/Services/Crud/Find.php`)
Same pattern as Get but selects from `FIELD_VIEW` and filters by id. Calls `afterDetil()` hook.

### Add Service (`app/Services/Crud/Add.php`)
Key steps:
1. Validates against `FIELD_VALIDATION` rules
2. Checks `FIELD_UNIQUE` constraints
3. Applies `FIELD_DEFAULT_VALUE`
4. Calls `beforeInsert()` lifecycle hook
5. Moves temp files for `FIELD_UPLOAD` fields via `HandlesFileUploads`
6. Inserts with `DB::table()->insertGetId()` (only `FIELD_ADD` fields)
7. Calls `afterInsert()` — passes Eloquent model via `::find($id)`
8. Returns result from `CallService::call('Find', ...)`

### Edit Service (`app/Services/Crud/Edit.php`)
Same steps but:
- Validates only fields present in input (not all FIELD_VALIDATION)
- Uses `FIELD_EDIT` instead of `FIELD_ADD`
- Calls `beforeUpdate()` / `afterUpdate()`
- Deletes old files before moving new ones for upload fields

### Delete Service (`app/Services/Crud/Delete.php`)
1. Checks `IS_DELETE` and permission
2. Finds Eloquent model for lifecycle hooks
3. Calls `beforeDelete()`
4. Deletes with `DB::table()->delete()` wrapped in try/catch for FK violations
5. Calls `afterDelete()`

### HandlesFileUploads Trait (`app/Services/Crud/Concerns/HandlesFileUploads.php`)

```php
trait HandlesFileUploads
{
    protected function moveTempFileToFinalPath(string $tempPath, string $table): string
    {
        $filename = basename($tempPath);
        $finalPath = "uploads/{$table}/{$filename}";
        Storage::disk('document')->move($tempPath, $finalPath);
        return $finalPath;
    }

    protected function deleteFileIfExists(?string $path): void
    {
        if (!empty($path)) Storage::disk('document')->delete($path);
    }

    protected function isTempUpload($value): bool
    {
        return !empty($value) && is_string($value) && str_starts_with($value, 'temp/');
    }
}
```

---

## 7. Backend: Models & FIELD Constants

### BaseModel (`app/Models/BaseModel.php`)

All non-auth models extend this. Defines default constants:

```php
abstract class BaseModel extends Model
{
    public const TABLE = '';
    public const IS_ADD = true;
    public const IS_EDIT = true;
    public const IS_LIST = true;
    public const IS_DELETE = true;
    public const FIELD_LIST = [];
    public const FIELD_ADD = [];
    public const FIELD_EDIT = [];
    public const FIELD_VIEW = [];
    public const FIELD_READONLY = ['id', 'created_at', 'updated_at'];
    public const FIELD_FILTERABLE = [];
    public const FIELD_SEARCHABLE = [];
    public const FIELD_SORTABLE = ['id'];
    public const FIELD_TYPE = [];
    public const FIELD_RELATION = [];
    public const FIELD_VALIDATION = [];
    public const FIELD_UNIQUE = [];
    public const FIELD_UPLOAD = [];
    public const FIELD_ARRAY = [];
    public const FIELD_DEFAULT_VALUE = [];
    public const CHILD_TABLE = [];
    public const CUSTOM_SELECT = '';
    public const CUSTOM_LIST_FILTER = [];
    public const PARENT_CHILD = [];

    protected $guarded = [];

    // Lifecycle hooks (all static)
    public static function beforeInsert(array $input): array { return $input; }
    public static function beforeUpdate(array $input): array { return $input; }
    public static function afterInsert(self $object, array $input): void {}
    public static function afterUpdate(self $object, array $input): array { return []; }
    public static function beforeDelete(self $object, array $input): void {}
    public static function afterDelete(self $object, array $input): void {}
    public static function beforeList(array $input): array { return $input; }
    public static function afterDetil(array $input, object $object): object { return $object; }
}
```

### Authenticatable Models
`Users`, `Doctors`, `Patients` extend `Authenticatable` (not `BaseModel`) but follow the same FIELD_* constant conventions and implement `JWTSubject`. They use `Hash::make()` for passwords (NOT `'hashed'` casts).

### NormalizesAuthFields Trait (`app/Models/Concerns/NormalizesAuthFields.php`)

Shared by `Doctors` and `Patients`:

```php
trait NormalizesAuthFields
{
    public static function normalizePasswordOnUpdate(array $input): array
    {
        if (array_key_exists('password', $input)) {
            if (blank($input['password'])) {
                unset($input['password']);  // empty → don't change
            } else {
                $input['password'] = Hash::make($input['password']);
            }
        }
        return $input;
    }

    public static function normalizeBooleanField(array $input, string $field, ?bool $default = null): array
    {
        if (array_key_exists($field, $input)) {
            $input[$field] = filter_var($input[$field], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        } elseif ($default !== null) {
            $input[$field] = $default;
        }
        return $input;
    }
}
```

### Users Model (`app/Models/Users.php`)
Extends `Authenticatable`, implements `JWTSubject`.

FIELD_SORTABLE: `['id', 'fullname', 'username', 'email', 'role_id', 'active', 'created_at', 'updated_at']`

FIELD_RELATION: `role_id` → `roles` table (joins `role_name` as `rel_role_id`)

Lifecycle: `beforeInsert` hashes password; `beforeUpdate` conditionally hashes password and normalizes `active`.

### Doctors Model (`app/Models/Doctors.php`)
Extends `Authenticatable`, uses `NormalizesAuthFields`.

FIELD_SORTABLE: `['id', 'fullname', 'specialization', 'email', 'phone', 'available', 'created_at', 'updated_at']`

FIELD_LIST: `['id', 'fullname', 'specialization', 'email', 'phone', 'available', 'photo', 'created_at', 'updated_at']`

Lifecycle: `beforeInsert` hashes password + normalizes `available`; `beforeUpdate` normalizes password and `available`.

### Patients Model (`app/Models/Patients.php`)
Extends `Authenticatable`, uses `NormalizesAuthFields`.

FIELD_SORTABLE: `['id', 'fullname', 'email', 'phone', 'gender', 'birthdate', 'created_at', 'updated_at']`

FIELD_LIST: `['id', 'fullname', 'email', 'phone', 'birthdate', 'gender', 'address', 'photo', 'created_at', 'updated_at']`

Lifecycle: `beforeInsert` hashes password; `beforeUpdate` normalizes password.

### Roles Model (`app/Models/Roles.php`)
Extends `BaseModel`. TABLE = `roles`.

FIELD_SORTABLE: `['id', 'role_code', 'role_name', 'description', 'active', 'created_at', 'updated_at']`

FIELD_FILTERABLE: `active` (operator `=`).

Lifecycle: `beforeInsert`/`beforeUpdate` normalize `active`.

### Tasks Model (`app/Models/Tasks.php`)
Extends `BaseModel`. TABLE = `tasks`.

FIELD_SORTABLE: `['id', 'task_code', 'task_name', 'module', 'active', 'created_at', 'updated_at']`

FIELD_FILTERABLE: `module` (=), `active` (=).

### RoleTask Model (`app/Models/RoleTask.php`)
Extends `BaseModel`. TABLE = `role_task`. IS_EDIT = false.

FIELD_SORTABLE: `['id', 'role_id', 'task_id', 'active', 'created_at', 'updated_at']`

FIELD_RELATION: `role_id` → `roles.role_name`, `task_id` → `tasks.task_code, task_name`.

### Appointments Model (`app/Models/Appointments.php`)
Extends `BaseModel`. TABLE = `appointments`.

FIELD_SORTABLE: `['id', 'doctor_id', 'patient_id', 'room_id', 'appointment_date', 'appointment_time', 'status', 'created_at', 'updated_at']`

FIELD_RELATION: `doctor_id` → `doctors.fullname, specialization`, `patient_id` → `patients.fullname`, `room_id` → `rooms.room_code, room_name`.

Lifecycle: `beforeInsert` checks for conflicts (doctor/room already booked at same datetime, doctor availability), defaults status to `scheduled`. `beforeUpdate` checks conflicts on rescheduling, prevents modification of completed appointments.

### MedicalRecords Model (`app/Models/MedicalRecords.php`)
Extends `BaseModel`. TABLE = `medicalrecords`.

FIELD_SORTABLE: `['id', 'doctor_id', 'patient_id', 'diagnosis', 'treatment', 'prescription', 'created_at', 'updated_at']`

FIELD_RELATION: `doctor_id` → doctors, `patient_id` → patients.

FIELD_UNIQUE: `[['appointment_id']]` — one record per appointment.

Lifecycle: `beforeInsert` validates doctor/patient match the appointment's assigned doctor/patient.

### Rooms Model (`app/Models/Rooms.php`)
Extends `BaseModel`. TABLE = `rooms`.

FIELD_SORTABLE: `['id', 'room_code', 'room_name', 'capacity', 'available', 'created_at', 'updated_at']`

Has `appointments()` relationship (hasMany).

---

## 8. Backend: File Upload System

### Flow
1. Frontend uploads file → `POST /upload-tmp` (or `/doctor/upload-tmp`, `/patient/upload-tmp`)
2. File stored in `storage/app/documents/temp/{random}_{originalname}`
3. Path returned to frontend as `field_value`
4. On create/update CRUD operation, `HandlesFileUploads` detects `temp/` prefix and moves to `storage/app/documents/uploads/{table}/{filename}`
5. File served at `GET /file/{path}`

### UploadController (`app/Http/Controllers/UploadController.php`)

```php
public function upload(Request $request)
{
    $request->validate(['file' => 'required|file|max:5120']);  // 5MB max

    $file = $request->file('file');
    $filename = Str::random(20) . '_' . $file->getClientOriginalName();
    $path = $file->storeAs('temp', $filename, 'document');

    return response()->json([
        'success' => true,
        'path' => $path,                        // "temp/xxx_filename.jpg"
        'url' => route('file.read', ['path' => $path]),
        'filename' => $file->getClientOriginalName(),
        'field_value' => $path,                // what the CRUD services expect
    ]);
}
```

### Filesystem Config
Uses `document` disk (configured in `config/filesystems.php`), root at `storage/app/documents/`.

---

## 9. Backend: Permissions

### hasPermission() Helper (`app/helpers/function.php`)

```php
function hasPermission(string $task): bool
{
    $user = Auth::user();
    if (!$user) return false;

    // role_id=1 is super admin — always true
    // Doctors and Patients have no role_id — always true (bypass)
    if (!isset($user->role_id) || (int) $user->role_id === 1) return true;

    return DB::table('role_task')
        ->join('tasks', 'tasks.id', '=', 'role_task.task_id')
        ->where('role_task.role_id', $user->role_id)
        ->where('role_task.active', true)
        ->where('tasks.active', true)
        ->where('tasks.task_code', $task)
        ->exists();
}
```

Critical behavior:
- `!isset($user->role_id)` → Doctors and Patients have no `role_id` column, so they always pass. This is intentional — doctor/patient scoped services have their own authorization via guard middleware.
- `role_id === 1` → Super admin always passes.

### CRUD Permission Format
| Permission | Task Code |
|-----------|-----------|
| View list | `view-{model}` |
| View detail | `show-{model}` |
| Create | `create-{model}` |
| Update | `update-{model}` |
| Delete | `delete-{model}` |

### role_task Table
Junction between `roles` and `tasks` with `active` flag. Unique constraint on `[role_id, task_id]`.

---

## 10. Backend: Middleware Stack

### Execution Order for Protected Routes

```
Request → SetGuard (set auth.defaults.guard) → AuthApiMiddleware (validate JWT) → Route Handler → CallService::run()
```

### Exception Handling
All API exceptions render as JSON (configured in `bootstrap/app.php`):

```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->shouldRenderJsonWhen(
        fn (Request $request) => $request->is('api/*'),
    );
})
```

---

## 11. Backend: Scoped Services

### DoctorAppointments (`app/Services/Crud/DoctorAppointments.php`)
Lists appointments for the authenticated doctor. Scopes by `doctor_id = Auth::id()`. Supports `sort`/`order` params, defaults to `appointment_date ASC, appointment_time ASC`. Supports filters: `status`, `appointment_date`.

### PatientAppointments (`app/Services/Crud/PatientAppointments.php`)
Same pattern, scoped to `patient_id = Auth::id()`.

### DoctorMedicalRecords (`app/Services/Crud/DoctorMedicalRecords.php`)
Scoped to `doctor_id = Auth::id()`. Supports `patient_id` filter. Default sort: `created_at DESC`.

### PatientMedicalRecords (`app/Services/Crud/PatientMedicalRecords.php`)
Scoped to `patient_id = Auth::id()`. Default sort: `created_at DESC`.

### ListAvailableDoctors (`app/Services/Auth/ListAvailableDoctors.php`)
Public endpoint (`guard: null`). Returns all doctors with `available = true`:

```php
protected function process($input, $originalData)
{
    $doctors = DB::table('doctors')
        ->where('available', true)
        ->select('id', 'fullname', 'specialization', 'email')
        ->get();
    return ['success' => true, 'data' => $doctors];
}
```

### PatientBookAppointment (`app/Services/Auth/PatientBookAppointment.php`)
Patient self-booking with validation:
1. Validates doctor exists and is available
2. Finds an available room (not booked at same datetime)
3. Checks doctor isn't double-booked
4. Inserts appointment with `status = 'scheduled'`

### UpdateDoctorProfile / UpdatePatientProfile
Allow doctors/patients to update their own profile fields. Use `HandlesFileUploads` for photo uploads. Validate unique email constraint manually. Return updated profile via `CallService::call('Find', ...)`.

---

## 12. Backend: Auth Services

### Complete Service Listing

| Service | Guard | Endpoint | Purpose |
|---------|-------|----------|---------|
| `DoUnifiedLogin` | null | `/login` | Unified login (admin/doctor/patient) |
| `DoLoginDoctor` | null | `/doctor/login` | Doctor-only login |
| `DoLoginPatient` | null | `/patient/login` | Patient-only login |
| `Me` | `api` | `/me` | Get admin profile + permissions |
| `DoLogout` | `api` | `/logout` | Invalidate JWT |
| `UpdateAdminProfile` | `api` | `/profile` | Admin self-update |
| `UpdateDoctorProfile` | `doctor` | `/doctor/profile` | Doctor self-update |
| `UpdatePatientProfile` | `patient` | `/patient/profile` | Patient self-update |
| `ListAvailableDoctors` | null | `/doctors/available` | Public: list available doctors |
| `PatientBookAppointment` | `patient` | `/patient/book` | Patient self-booking |
| `DoctorAppointments` | `doctor` | `/doctor/appointments` | Doctor's appointment list |
| `DoctorMedicalRecords` | `doctor` | `/doctor/medicalrecords` | Doctor's medical records |
| `PatientAppointments` | `patient` | `/patient/appointments` | Patient's appointment list |
| `PatientMedicalRecords` | `patient` | `/patient/medicalrecords` | Patient's medical records |

---

## 13. Backend: Database Schema

### users
| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | auto |
| fullname | VARCHAR(100) | |
| username | VARCHAR(50) | UNIQUE |
| email | VARCHAR(100) | UNIQUE |
| password | VARCHAR(255) | bcrypt |
| role_id | BIGINT FK | → roles.id, restrict on delete |
| photo | VARCHAR(255) | nullable |
| active | BOOLEAN | default true |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

### roles
| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | auto |
| role_code | VARCHAR(50) | UNIQUE |
| role_name | VARCHAR(100) | |
| description | TEXT | nullable |
| active | BOOLEAN | default true |

### tasks
| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | auto |
| task_code | VARCHAR(100) | UNIQUE |
| task_name | VARCHAR(150) | |
| module | VARCHAR(100) | |
| description | TEXT | nullable |
| active | BOOLEAN | default true |

### role_task
| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | auto |
| role_id | BIGINT FK | → roles.id, CASCADE |
| task_id | BIGINT FK | → tasks.id, CASCADE |
| active | BOOLEAN | default true |
| UNIQUE | (role_id, task_id) | |

### doctors
| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | auto |
| fullname | VARCHAR(100) | |
| specialization | VARCHAR(100) | |
| email | VARCHAR(100) | UNIQUE |
| password | VARCHAR(255) | bcrypt |
| phone | VARCHAR(15) | nullable |
| available | BOOLEAN | default true |
| photo | VARCHAR(255) | nullable |

### patients
| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | auto |
| fullname | VARCHAR(100) | |
| email | VARCHAR(100) | UNIQUE |
| password | VARCHAR(255) | bcrypt |
| phone | VARCHAR(15) | nullable |
| birthdate | DATE | nullable |
| gender | ENUM('male','female') | nullable |
| address | VARCHAR(255) | nullable |
| photo | VARCHAR(255) | nullable (added by migration) |

### rooms
| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | auto |
| room_code | VARCHAR(20) | UNIQUE |
| room_name | VARCHAR(100) | |
| capacity | INT UNSIGNED | default 1 |
| available | BOOLEAN | default true |

### appointments
| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | auto |
| doctor_id | BIGINT FK | → doctors.id, RESTRICT |
| patient_id | BIGINT FK | → patients.id, RESTRICT |
| room_id | BIGINT FK | → rooms.id, RESTRICT |
| appointment_date | DATE | |
| appointment_time | TIME | |
| status | ENUM('scheduled','completed','cancelled') | default 'scheduled' |
| notes | TEXT | nullable |

### medicalrecords
| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | auto |
| appointment_id | BIGINT FK | → appointments.id, RESTRICT |
| doctor_id | BIGINT FK | → doctors.id, RESTRICT |
| patient_id | BIGINT FK | → patients.id, RESTRICT |
| diagnosis | TEXT | required |
| treatment | TEXT | nullable |
| prescription | TEXT | nullable |
| notes | TEXT | nullable |

---

## 14. Backend: Seeders

### DatabaseSeeder runs in order:
```php
$this->call([
    AccessSeeder::class,       // 2 roles (Super Admin, Staff)
    TaskSeeder::class,          // 45 tasks (CRUD permissions for 9 entities)
    RoleTaskSeeder::class,      // role_id=1 gets ALL tasks
    RoomSeeder::class,          // ~5 rooms
    DoctorSeeder::class,        // 10 doctors
    PatientSeeder::class,       // 25 patients
    AppointmentSeeder::class,   // 32 appointments
    MedicalRecordSeeder::class, // 11 medical records
]);
```

---

## 15. Frontend: Project Structure

```
clinic-admin/
├── src/
│   ├── app/
│   │   ├── actions/
│   │   │   ├── CRUD/
│   │   │   │   └── CRUDList.ts
│   │   │   └── Form.ts
│   │   └── configs/
│   │       ├── _defaults.ts           # Shared defaults for tables & forms
│   │       ├── users.ts
│   │       ├── doctors.ts
│   │       ├── patients.ts
│   │       ├── appointments.ts
│   │       ├── medicalrecords.ts
│   │       ├── rooms.ts
│   │       ├── roles.ts
│   │       ├── tasks.ts
│   │       └── role-task.ts
│   ├── components/
│   │   ├── base/
│   │   │   ├── Avatar.vue
│   │   │   ├── Badge.vue
│   │   │   └── Modal.vue
│   │   ├── composites/
│   │   │   ├── CRUDComposite.vue      # Main CRUD orchestrator
│   │   │   ├── CRUD/
│   │   │   │   ├── CRUDList.vue
│   │   │   │   ├── CRUDDetail.vue
│   │   │   │   ├── CRUDCreate.vue
│   │   │   │   └── CRUDUpdate.vue
│   │   │   ├── Table.vue
│   │   │   ├── Form.vue
│   │   │   ├── Pagination.vue
│   │   │   └── SearchBox.vue
│   │   ├── inputs/
│   │   │   └── BaseInput.vue          # Base label + error wrapper
│   │   └── layout/
│   │       └── Sidebar.vue
│   ├── layouts/
│   │   ├── Authenticated.vue          # Admin layout (sidebar + content)
│   │   └── PortalLayout.vue           # Doctor/Patient layout (navbar + RouterView)
│   ├── router/
│   │   └── index.ts
│   ├── stores/
│   │   ├── auth.ts                    # Admin auth (Pinia)
│   │   ├── doctor-auth.ts             # Doctor auth
│   │   ├── patient-auth.ts            # Patient auth
│   │   └── permissions.ts             # Permission check store
│   ├── types/
│   │   └── api.ts                     # TypeScript interfaces
│   ├── utils/
│   │   ├── http.ts                    # Axios instance + JWT interceptor
│   │   ├── upload.ts                  # Guard-aware file upload
│   │   ├── storage.ts                 # localStorage wrapper
│   │   ├── files.ts                   # File URL resolver
│   │   └── services.ts                # CRUD API helper
│   ├── views/
│   │   ├── authenticated/
│   │   │   ├── clinical/              # Auto-generated from menu
│   │   │   ├── access/                # Auto-generated from menu
│   │   │   └── profile.vue
│   │   ├── doctor/
│   │   │   ├── dashboard.vue
│   │   │   ├── appointments.vue
│   │   │   ├── medical-records.vue
│   │   │   └── profile.vue
│   │   ├── patient/
│   │   │   ├── dashboard.vue
│   │   │   ├── appointments.vue
│   │   │   ├── medical-records.vue
│   │   │   └── profile.vue
│   │   └── unauthenticated/
│   │       └── login.vue
│   ├── App.vue
│   ├── main.ts
│   ├── menu.ts                        # Admin sidebar menu definition
│   └── style.css
```

---

## 16. Frontend: Router & Route Guards

### `src/router/index.ts`

Three route groups with their own `beforeEach` guards:

**1. Admin routes** (`/`) — `auth().isAuthenticated` check:
```ts
{
    path: '/',
    component: () => import('@/layouts/Authenticated.vue'),
    redirect: '/clinical/doctors',
    meta: { requiresAuth: true },
    children: authenticatedChildren,  // generated from menu.ts
}
```

**2. Doctor routes** (`/doctor`) — `doctorAuth().isAuthenticated`:
```ts
{
    path: '/doctor',
    component: () => import('@/layouts/PortalLayout.vue'),
    props: { role: 'doctor' },
    meta: { requiresAuth: true, guard: 'doctor' },
    children: [
        { path: 'dashboard', ... },
        { path: 'appointments', ... },
        { path: 'medical-records', ... },
        { path: 'profile', ... },
    ],
}
```

**3. Patient routes** (`/patient`) — `patientAuth().isAuthenticated`:
Same pattern as doctor but with `guard: 'patient'`.

**4. Login** (`/login`) — public, no guard.

**Route Guard Logic:**
```ts
router.beforeEach((to) => {
    const guard = String(to.meta?.guard ?? '')

    if (guard === 'doctor') {
        const authed = doctorAuth().isAuthenticated
        if (to.meta.requiresAuth && !authed) return { name: 'login' }
        if (to.name === 'login' && authed) return { path: '/doctor' }
        return
    }

    if (guard === 'patient') {
        // same pattern
    }

    const authed = auth().isAuthenticated
    if (to.meta.requiresAuth && !authed) return { name: 'login' }
    if (to.name === 'login' && authed) return { path: '/clinical/doctors' }
})
```

### Admin Menu-Based Route Generation
Admin child routes are dynamically generated from `menu.ts`:

```ts
const authenticatedChildren: RouteRecordRaw[] = []
for (const module of menu) {
    for (const submodule of module.routes) {
        authenticatedChildren.push({
            path: `/${module.name}/${submodule.name}`,
            name: submodule.name,
            component: () => import(`@/views/authenticated/${module.name}/${submodule.name}/${submodule.name}.vue`),
        })
    }
}
```

### `src/menu.ts`
```ts
const menu: Modules = [
    {
        name: 'clinical',
        title: 'Clinical Management',
        routes: [
            { name: 'doctors', title: 'Doctors' },
            { name: 'patients', title: 'Patients' },
            { name: 'appointments', title: 'Appointments' },
            { name: 'rooms', title: 'Rooms' },
            { name: 'medicalrecords', title: 'Medical Records' },
        ],
    },
    {
        name: 'access',
        title: 'Access Control',
        routes: [
            { name: 'users', title: 'Users' },
            { name: 'roles', title: 'Roles' },
            { name: 'tasks', title: 'Tasks' },
            { name: 'role-task', title: 'Role-Task' },
        ],
    },
]
```

---

## 17. Frontend: HTTP Client & JWT Interceptor

### `src/utils/http.ts`

Multi-guard aware Axios instance:

```ts
const http = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL,
    headers: { Accept: 'application/json' },
})

function detectGuard(): string | null {
    if (window.location.pathname.startsWith('/doctor')) return 'doctor'
    if (window.location.pathname.startsWith('/patient')) return 'patient'
    return null
}

// Request interceptor — attach correct JWT
http.interceptors.request.use((config) => {
    const guard = detectGuard()
    let token: string | null = null

    if (guard === 'doctor') {
        token = localStorage.getItem('doctor_token')
    } else if (guard === 'patient') {
        token = localStorage.getItem('patient_token')
    } else {
        token = storage.getToken()  // admin token
    }

    if (token) config.headers.Authorization = `Bearer ${token}`
    return config
})

// Response interceptor — 401 → clear storage + redirect to login
http.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            const guard = detectGuard()
            // Clear appropriate storage
            if (guard === 'doctor') {
                localStorage.removeItem('doctor_token')
                localStorage.removeItem('doctor_user')
            } else if (guard === 'patient') {
                localStorage.removeItem('patient_token')
                localStorage.removeItem('patient_user')
            } else {
                storage.clearAll()
            }
            // Redirect to login via router
            import('@/router').then(({ default: router }) => {
                if (router.currentRoute.value.name !== 'login') {
                    router.push({ name: 'login' })
                }
            })
        }
        return Promise.reject(error)
    }
)
```

### `src/utils/services.ts`
CRUD API helper wrapping http:

```ts
const services = {
    list<T>(model: string, params?: ListParams) {
        return http.get<ApiListResponse<T>>(`/${model}/list`, { params }).then(r => r.data)
    },
    show<T>(model: string, id: number | string) {
        return http.get<ApiItemResponse<T>>(`/${model}/${id}/show`).then(r => r.data)
    },
    create<T>(model: string, payload: Record<string, any>) {
        return http.post<ApiItemResponse<T>>(`/${model}/create`, payload).then(r => r.data)
    },
    update<T>(model: string, payload: Record<string, any>) {
        return http.put<ApiItemResponse<T>>(`/${model}/update`, payload).then(r => r.data)
    },
    delete(model: string, id: number | string) {
        return http.delete<ApiMessageResponse>(`/${model}/delete`, { data: { id } }).then(r => r.data)
    },
}
```

---

## 18. Frontend: Auth Stores

### Admin Auth (`src/stores/auth.ts`)
```ts
export const auth = defineStore('auth', () => {
    const user = ref<AuthUser | null>(storage.getUser<AuthUser>())
    const isAuthenticated = computed(() => !!user.value && !!storage.getToken())

    async function login(username: string, password: string): Promise<AuthUser> {
        const { data: body } = await http.post<LoginResponse>('/login', { username, password })
        storage.setToken(body.token)
        storage.setUser(body.data)
        user.value = body.data
        await fetchProfile()  // loads permissions
        return body.data
    }

    async function fetchProfile(): Promise<AuthUser> {
        const { data: body } = await http.get<MeResponse>('/me')
        storage.setUser(body.data)
        storage.setPermissions(body.permissions)
        user.value = body.data
        permissions().build(body.permissions)
        return body.data
    }

    async function logout(): Promise<void> {
        try { await http.post('/logout') } finally {
            storage.clearAll()
            user.value = null
            permissions().clear()
        }
    }

    return { user, isAuthenticated, login, fetchProfile, logout }
})
```

### Doctor Auth (`src/stores/doctor-auth.ts`)
Uses `createStorage('doctor')` for isolated localStorage keys (`doctor_token`, `doctor_user`):

```ts
const storage = createStorage('doctor')

export const doctorAuth = defineStore('doctor-auth', () => {
    const user = ref<DoctorUser | null>(storage.getUser<DoctorUser>())
    const isAuthenticated = computed(() => !!user.value && !!storage.getToken())

    function hydrate(token: string, data: DoctorUser) {
        storage.setToken(token)
        storage.setUser(data)
        user.value = data
    }

    async function fetchProfile(): Promise<DoctorUser> {
        const { data: body } = await http.get<MeResponse>('/doctor/me')
        storage.setUser(body.data)
        user.value = body.data as unknown as DoctorUser
        return body.data as unknown as DoctorUser
    }

    return { user, isAuthenticated, hydrate, fetchProfile, logout }
})
```

### Patient Auth (`src/stores/patient-auth.ts`)
Same pattern as doctor, using `createStorage('patient')`.

### Permissions Store (`src/stores/permissions.ts`)
```ts
export const permissions = defineStore('permissions', () => {
    const value = ref<Set<string>>(new Set(storage.getPermissions()))

    function build(data: string[]): void { value.value = new Set(data) }
    function has(permission?: string | null): boolean {
        if (!permission) return true
        return value.value.has(permission)
    }
    function clear(): void { value.value = new Set() }

    return { value, has, build, clear }
})
```

---

## 19. Frontend: Storage Utilities

### `src/utils/storage.ts`

```ts
export const storage = {
    getToken: () => read<string>(TOKEN_KEY),
    setToken: (token: string) => write(TOKEN_KEY, token),
    clearToken: () => remove(TOKEN_KEY),

    getUser: <T>() => read<T>(USER_KEY),
    setUser: (user: unknown) => write(USER_KEY, user),
    clearUser: () => remove(USER_KEY),

    getPermissions: () => read<string[]>(PERMISSIONS_KEY) ?? [],
    setPermissions: (permissions: string[]) => write(PERMISSIONS_KEY, permissions),
    clearPermissions: () => remove(PERMISSIONS_KEY),

    clearAll(): void { ... }
}

export function createStorage(ns: string) {
    const tokenKey = `${ns}_token`
    const userKey = `${ns}_user`

    return {
        getToken: () => read<string>(tokenKey),
        setToken: (token: string) => write(tokenKey, token),
        getUser: <T>() => read<T>(userKey),
        setUser: (user: unknown) => write(userKey, user),
        clearAll(): void { remove(tokenKey); remove(userKey) },
    }
}
```

### `src/utils/files.ts`
```ts
export function resolveFileUrl(path?: string | null): string | null {
    if (!path) return null
    if (/^https?:\/\//.test(path)) return path
    const base = import.meta.env.VITE_API_BASE_URL.replace(/\/$/, '')
    return `${base}/file/${path}`
}
```

---

## 20. Frontend: Config-Driven CRUD

### CRUDComposite (`src/components/composites/CRUDComposite.vue`)

The main orchestrator. Takes a `config: CRUDCompositeConfig` prop and renders one of four child views based on `route.query.{name}_view`:

```vue
<script setup lang="ts">
const actionsPermission: CRUDPermissions = {
    view: permissions().has(`view-${permissionKey}`),
    detail: permissions().has(`show-${permissionKey}`),
    create: permissions().has(`create-${permissionKey}`),
    update: permissions().has(`update-${permissionKey}`),
    delete: permissions().has(`delete-${permissionKey}`),
}

const currentView = computed(() => {
    const v = route.query[`${props.config.name}_view`]
    return (v as any) || 'list'
})
</script>

<template>
    <CRUDList v-if="currentView === 'list'" ... />
    <CRUDDetail v-else-if="currentView === 'detail'" ... />
    <CRUDCreate v-else-if="currentView === 'create'" ... />
    <CRUDUpdate v-else-if="currentView === 'update'" ... />
</template>
```

### CRUDList — Table + Search + Pagination
Renders `<Table>` with fields from config, `<SearchBox>` for search, and action buttons (View/Edit/Delete) based on permissions.

### CRUDDetail — Read-only record view
Fetches a single record via `services.show()` and renders each field from config with appropriate component (badge, image, avatar-name, datetime, plain text).

### CRUDCreate / CRUDUpdate — Form views
Render `<Form>` with input config from entity config. Create passes no initial data; update fetches existing record first.

### Table Component (`src/components/composites/Table.vue`)

Accepts props: `getAPI`, `endpointUrl`, `fields`, `fieldsAlias`, `fieldsProxy`, `fieldsType`, `searchParameters`, `uid`.

Supports:
- Server-side pagination via `<Pagination>`
- Column sorting (click header to toggle asc/desc/null)
- Field type rendering: `badge`, `avatar-name`, `datetime`, plain text
- Proxy field resolution (e.g., `role_id` → `rel_role_id`)
- Search via `searchParameters` prop (deep-watched)

### Form Component (`src/components/composites/Form.vue`)

Two modes: `create` and `update`. Uses dynamic `<component :is>` based on `componentTypeMap`:

```ts
// componentTypeMap maps config types to Vue components
const componentTypeMap = {
    text: TextInput,
    password: TextInput,
    number: TextInput,
    textarea: TextareaInput,
    select: SelectInput,
    date: TextInput,
    radio: RadioInput,
    image: ImageInput,
}
```

On submit, sends to `services.create()` or `services.update()`. Handles Laravel validation errors by mapping `{ field: ['msg'] }` to `formError`.

### Pagination (`src/components/composites/Pagination.vue`)
Shows "X total · page Y of Z" with Prev/Next buttons.

### SearchBox (`src/components/composites/SearchBox.vue`)
Debounced (350ms) search input with `⌕` icon.

---

## 21. Frontend: Entity Configs

### `src/app/configs/_defaults.ts`

Provides shared utilities:

```ts
export const statusBadge = {  // appointment status
    type: 'badge',
    props: { map: {
        scheduled: { label: 'Scheduled', variant: 'scheduled' },
        completed: { label: 'Completed', variant: 'completed' },
        cancelled: { label: 'Cancelled', variant: 'cancelled' },
    }},
}

export const defaultFieldProxy: Record<string, string> = {
    role_id: 'rel_role_id',
    task_id: 'rel_task_id',
    doctor_id: 'rel_doctor_id',
    patient_id: 'rel_patient_id',
    room_id: 'rel_room_id',
    appointment_id: 'rel_appointment_id',
}

export const defaultTableConfig = { ... }  // default fieldsAlias + fieldsType
export const defaultFormConfig = { ... }    // default fieldsAlias for forms
export const defaultInputConfig = { ... }   // default input configurations

export function booleanBadge(trueLabel, falseLabel, trueVariant, falseVariant): FieldTypeConfig { ... }
export function booleanRadio(trueLabel, falseLabel): InputFieldConfig { ... }
```

### Example Entity Config: `doctors.ts`

```ts
const doctors: CRUDCompositeConfig = {
    name: 'doctors',
    title: 'Doctors',
    view: {
        fields: ['id', 'photo', 'fullname', 'specialization', 'email', 'phone', 'available', 'created_at'],
        fieldsType: {
            available: booleanBadge('Available', 'Occupied'),
            photo: { type: 'image' },
            ...defaultTableConfig.fieldsType,
        },
        list: {
            fields: ['photo', 'specialization', 'email', 'phone', 'available'],
            fieldsAlias: { photo: 'Name' },
            fieldsType: {
                photo: { type: 'avatar-name', props: { nameField: 'fullname', variant: 'green' } },
                available: booleanBadge('Available', 'Occupied'),
            },
        },
    },
    transaction: {
        fields: ['fullname', 'specialization', 'email', 'password', 'phone', 'available', 'photo'],
        inputConfig: {
            fullname: { type: 'text', props: { required: true }, colSpan: 6 },
            specialization: { type: 'text', props: { required: true }, colSpan: 6 },
            email: { type: 'text', props: { required: true }, colSpan: 6 },
            phone: { type: 'text', colSpan: 6 },
            password: { type: 'password', props: { placeholder: 'Leave blank to keep current' }, colSpan: 6 },
            available: { ...booleanRadio('Available', 'Occupied'), colSpan: 6 },
            photo: { type: 'image' },
        },
        create: {
            inputConfig: {
                password: { type: 'password', props: { required: true }, colSpan: 6 },
            },
        },
    },
}
```

### All Entity Configs
| Config | File | modelAPI | Key Features |
|--------|------|----------|--------------|
| Users | `users.ts` | `users` | Avatar+name, role select, active badge |
| Doctors | `doctors.ts` | `doctors` | Avatar+name, available badge, specialization |
| Patients | `patients.ts` | `patients` | Avatar+name, gender select, birthdate date picker |
| Appointments | `appointments.ts` | `appointments` | Doctor/patient/room selects, date, time, status badge |
| Medical Records | `medicalrecords.ts` | `medicalrecords` | Appointment select (create only), doctor/patient selects, textareas |
| Rooms | `rooms.ts` | `rooms` | Room code/name, capacity number, available badge |
| Roles | `roles.ts` | `roles` | Role code/name, active badge |
| Tasks | `tasks.ts` | `tasks` | Task code/name, module, active badge |
| Role-Task | `role-task.ts` | `role_task` | Role/task selects, active badge, IS_EDIT=false |

---

## 22. Frontend: Shared Components

### BaseInput (`src/components/inputs/BaseInput.vue`)
Reusable label + error wrapper:
```vue
<script setup lang="ts">
defineProps<{ label?: string; required?: boolean; error?: string }>()
</script>
<template>
  <label class="flex flex-col gap-1">
    <span v-if="label" class="text-sm font-medium text-gray-700">
      {{ label }}<span v-if="required" class="text-danger ml-0.5">*</span>
    </span>
    <slot />
    <p v-if="error" class="text-xs text-danger mt-0.5">{{ error }}</p>
  </label>
</template>
```

### Avatar (`src/components/base/Avatar.vue`)
Shows image if `photoUrl` is set, otherwise colored circle with initials.
- Sizes: `sm` (h-8), `md` (h-[38px]), `lg` (h-14)
- Variants: green, teal, olive, sage, moss, fern (all gradient backgrounds)
- Strips "Dr." prefix from name for initials

### Badge (`src/components/base/Badge.vue`)
Colored pill with dot indicator. Variants:
- `available` — clinic green
- `occupied` — warning (amber)
- `scheduled` — info (blue)
- `completed` — success (green)
- `cancelled` — danger (red)

### Modal (`src/components/base/Modal.vue`)
Teleported to body, backdrop overlay, close on backdrop click or X button.
Uses `defineModel<boolean>()` for v-model support.

### SearchBox (`src/components/composites/SearchBox.vue`)
Debounced (350ms) search input. Uses `defineModel<string>()`.

### Pagination (`src/components/composites/Pagination.vue`)
Shows "X total · page Y of Z" with Prev/Next buttons. Disabled at boundaries.

---

## 23. Frontend: Portal Views

### PortalLayout (`src/layouts/PortalLayout.vue`)
Shared layout for doctor and patient portals:

```vue
<script setup lang="ts">
const props = defineProps<{ role: 'doctor' | 'patient' }>()
const auth = computed(() => props.role === 'doctor' ? doctorAuth() : patientAuth())
const user = computed(() => auth.value.user)

onMounted(() => { auth.value.fetchProfile().catch(() => {}) })
</script>
```

Features:
- Sticky navbar with clinic logo, role badge, navigation links, user avatar, logout button
- Navigation links: Dashboard, Appointments, Medical Records, Profile
- Active link highlighting
- Uses `<RouterView />` to render child routes (NOT `<slot />`)

### Unified Login (`src/views/unauthenticated/login.vue`)
Single login page for all three roles:

```vue
<script setup lang="ts">
async function onSubmit() {
    const { data: body } = await http.post('/login', { email, password })
    const { token, role, data } = body

    if (role === 'admin') {
        localStorage.setItem('token', token)
        localStorage.setItem('user', JSON.stringify(data))
        auth().user = data as any
        await auth().fetchProfile()
        router.push({ path: '/clinical/doctors' })
    } else if (role === 'doctor') {
        doctorAuth().hydrate(token, data as any)
        router.push({ path: '/doctor' })
    } else {
        patientAuth().hydrate(token, data as any)
        router.push({ path: '/patient' })
    }
}
</script>
```

Key behavior: The `role` field in the response determines which store to hydrate and which portal to navigate to.

### Doctor Dashboard (`src/views/doctor/dashboard.vue`)
- Welcome message with doctor name and current date
- Stats cards: Today's Appointments, Completed Today, Pending
- Today's appointments list with patient avatar, time, room, status badge
- Uses `reactive user` from store (computed for reactivity)

### Doctor Appointments (`src/views/doctor/appointments.vue`)
- `<Table>` with search, fields: date, time, patient, room, status, notes
- Status column rendered as `<Badge>`
- Row actions: "View" opens detail modal; status dropdown (scheduled/completed/cancelled) with inline update
- Detail modal shows appointment info + patient's medical records

### Doctor Medical Records (`src/views/doctor/medical-records.vue`)
- `<Table>` with fields: diagnosis, treatment, prescription, patient, date
- Detail modal for each record

### Doctor Profile (`src/views/doctor/profile.vue`)
- Avatar with photo upload (guard-aware `uploadTmp`)
- Info display: name, specialization, email, phone
- Availability toggle button

### Patient Dashboard (`src/views/patient/dashboard.vue`)
- Welcome with patient ID
- Stats: Upcoming Appointments, Medical Records
- Upcoming appointments list with doctor, date, time, room, status
- **Booking form**: doctor select (from `/doctors/available`), date, time, notes
- Booking submits to `/patient/book`

### Patient Appointments / Medical Records
Similar pattern to doctor views — `<Table>` with search, detail modals.

### Patient Profile
Same as doctor profile but shows: email, phone, gender, birthdate, address.

---

## 24. Frontend: Admin Views

### Authenticated Layout (`src/layouts/Authenticated.vue`)
Sidebar + main content area:
```vue
<template>
  <div class="flex min-h-screen bg-gray-50">
    <Sidebar />
    <main class="ml-[252px] min-h-screen flex-1 p-8">
      <RouterView />
    </main>
  </div>
</template>
```

### CRUD Views (Auto-Generated)
Each admin entity page (e.g., `src/views/authenticated/clinical/doctors/doctors.vue`) is a thin wrapper:
```vue
<script setup lang="ts">
import config from '@/app/configs/doctors'
</script>
<template>
  <CRUDComposite :config="config" />
</template>
```

### App.vue
```vue
<script setup lang="ts">
import { Toaster } from 'vue-sonner'
onMounted(async () => {
    if (authStore.isAuthenticated) {
        try { await authStore.fetchProfile() } catch {}
    }
})
</script>
<template>
    <RouterView />
    <Toaster position="top-right" :duration="3500" />
</template>
```

### main.ts
```ts
const app = createApp(App)
app.use(createPinia())
app.use(router)
app.component('CRUDComposite', CRUDComposite)  // globally registered
app.mount('#app')
```

---

## 25. Frontend: File Upload

### `src/utils/upload.ts`

Guard-aware upload — detects which portal the user is in and routes to the correct endpoint:

```ts
function getUploadEndpoint(): string {
    if (window.location.pathname.startsWith('/doctor')) return '/doctor/upload-tmp'
    if (window.location.pathname.startsWith('/patient')) return '/patient/upload-tmp'
    return '/upload-tmp'
}

export async function uploadTmp(file: File): Promise<UploadResponse> {
    const form = new FormData()
    form.append('file', file)
    const { data } = await http.post<UploadResponse>(getUploadEndpoint(), form, {
        headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data
}
```

The returned `field_value` is the temp path (e.g., `temp/abc123_photo.jpg`). When the CRUD service processes a create/update, `HandlesFileUploads::isTempUpload()` detects the `temp/` prefix and moves the file to its final location.

---

## 26. Data Flow Diagrams

### Login Flow
```
Frontend                 Backend                       Database
   |                        |                             |
   |-- POST /login -------->|                             |
   |  {email, password}     |                             |
   |                        |-- Query Users by email ----->|
   |                        |<-- user record -------------|
   |                        |                             |
   |                        |-- Hash::check(password)     |
   |                        |   → match: return JWT       |
   |                        |                             |
   |                        |-- (if no match: try Doctors)|
   |                        |   → match: return JWT       |
   |                        |                             |
   |                        |-- (if no match: try Patients)|
   |                        |   → match: return JWT       |
   |                        |                             |
   |<-- {token, role, data}-|                             |
   |                        |                             |
   |-- Store token in       |                             |
   |   localStorage         |                             |
   |-- Route to portal      |                             |
   |   based on role        |                             |
```

### CRUD Create Flow
```
Frontend                 Backend                    Storage
   |                        |                          |
   |-- Upload file --------->| /upload-tmp             |
   |<-- {path: "temp/x.jpg"}--|                         |
   |                        |                          |
   |-- POST /{model}/create  |                          |
   |   {field1, photo:       |                          |
   |    "temp/x.jpg"}        |                          |
   |                        |-- Validate rules          |
   |                        |-- Check UNIQUE constraints|
   |                        |-- Apply default values    |
   |                        |-- beforeInsert() hook     |
   |                        |-- Detect temp/ prefix     |
   |                        |-- Move file: temp/ →      |
   |                        |   uploads/{table}/        |
   |                        |-- DB::table()->insert()   |
   |                        |-- afterInsert() hook      |
   |                        |-- Find created record     |
   |<-- {success, data}-----|                          |
```

### File Upload & Serve Flow
```
Upload:    Frontend → POST /upload-tmp → temp/xxx.jpg
Create:    Add service → detects temp/ → move to uploads/{table}/xxx.jpg
Serve:    Frontend → GET /file/uploads/{table}/xxx.jpg → Storage response
```

### Guard Switching Flow
```
Request to /doctor/appointments
  → SetGuard middleware: Config::set('auth.defaults.guard', 'doctor')
  → AuthApiMiddleware: JWTAuth::parseToken()->authenticate()
    → Uses 'doctor' guard → looks up doctors table
  → Route handler: CallService::run('DoctorAppointments', ...)
    → Auth::id() returns doctor's id from doctors table
```

---

## 27. API Reference

### Auth Endpoints
| Method | Endpoint | Guard | Service | Description |
|--------|----------|-------|---------|-------------|
| POST | `/login` | null | DoUnifiedLogin | Unified login (all roles) |
| POST | `/doctor/login` | null | DoLoginDoctor | Doctor-only login |
| POST | `/patient/login` | null | DoLoginPatient | Patient-only login |
| POST | `/logout` | api | DoLogout | Invalidate JWT |
| GET | `/me` | api | Me | Admin profile + permissions |
| PUT | `/profile` | api | UpdateAdminProfile | Admin self-update |
| PUT | `/doctor/profile` | doctor | UpdateDoctorProfile | Doctor self-update |
| PUT | `/patient/profile` | patient | UpdatePatientProfile | Patient self-update |

### CRUD Endpoints (Admin — api guard)
| Method | Endpoint | Service | Permission |
|--------|----------|---------|------------|
| GET | `/{model}/list` | Get | view-{model} |
| GET | `/{model}/dataset` | Get | view-{model} |
| GET | `/{model}/{id}/show` | Find | show-{model} |
| POST | `/{model}/create` | Add | create-{model} |
| PUT | `/{model}/update` | Edit | update-{model} |
| DELETE | `/{model}/delete` | Delete | delete-{model} |

Valid models: `users`, `roles`, `tasks`, `role_task`, `doctors`, `patients`, `rooms`, `appointments`, `medicalrecords`

### Scoped Endpoints
| Method | Endpoint | Guard | Service |
|--------|----------|-------|---------|
| GET | `/doctor/appointments` | doctor | DoctorAppointments |
| GET | `/doctor/medicalrecords` | doctor | DoctorMedicalRecords |
| GET | `/patient/appointments` | patient | PatientAppointments |
| GET | `/patient/medicalrecords` | patient | PatientMedicalRecords |
| GET | `/doctors/available` | null | ListAvailableDoctors |
| POST | `/patient/book` | patient | PatientBookAppointment |

### Profile Endpoints (Inline)
| Method | Endpoint | Guard | Description |
|--------|----------|-------|-------------|
| GET | `/doctor/me` | doctor | Doctor profile |
| GET | `/patient/me` | patient | Patient profile |

### Upload Endpoints
| Method | Endpoint | Guard |
|--------|----------|-------|
| POST | `/upload-tmp` | api |
| POST | `/doctor/upload-tmp` | doctor |
| POST | `/patient/upload-tmp` | patient |

### File Serving
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/file/{path}` | Serve file from document disk |

---

## 28. Critical Gotchas

### JWT_SECRET
**Must be added manually to `.env`**. It is NOT in `.env.example` and is NOT generated by `composer run setup`. All JWT auth will fail without it.

### .npmrc — ignore-scripts=true
The project's `.npmrc` sets `ignore-scripts=true`, meaning `npm install` won't run post-install scripts. This is intentional (matches `composer.json` setup script which passes `--ignore-scripts`). If you need Puppeteer or native modules, you'll need to handle the postinstall step manually.

### FIELD_SORTABLE
If a column is displayed in a table but not listed in `FIELD_SORTABLE`, clicking the column header to sort will silently fall back to sorting by `id`. Always add displayed columns to `FIELD_SORTABLE`.

### Guard-less Upload
The upload endpoint MUST be behind the correct guard. The frontend's `getUploadEndpoint()` detects the portal from the URL path. If you're on the doctor portal but call `/upload-tmp` instead of `/doctor/upload-tmp`, the JWT won't match the `api` guard.

### Password Handling
- Authenticatable models use `Hash::make()` explicitly in `beforeInsert`/`beforeUpdate` — do NOT use `'hashed'` Eloquent casts.
- On update, if password is blank/empty, it's removed from the update payload (password stays unchanged).
- `NormalizesAuthFields::normalizePasswordOnUpdate()` handles this for Doctors and Patients.

### Boolean Fields
- CRUD services use `DB::table()` which returns `1`/`0` for booleans (not Eloquent's `true`/`false`).
- The frontend `Table.vue` `mapKey()` function handles this: `1` → `'true'`, `0` → `'false'`.
- Authenticatable models use `filter_var(..., FILTER_VALIDATE_BOOLEAN)` for incoming data.
- `NormalizesAuthFields::normalizeBooleanField()` standardizes this.

### hasPermission() for Doctors/Patients
The helper returns `true` when `!isset($user->role_id)` — Doctors and Patients don't have a `role_id` column. This is intentional: scoped services enforce their own authorization via the guard middleware.

### Route Registration
- Do NOT manually add routes to `routes/api.php` for standard service endpoints — add entries to `config/service.php` instead.
- CRUD services use FQCN strings (e.g., `'App\Services\Crud\Get'`) and are NOT registered in `config/service.php`.
- The `AppServiceProvider` only binds services from `config/service.php`.

### Profile Photo Upload
- For admin-created doctors/patients: photo upload works via `/upload-tmp` (api guard).
- For doctor/patient self-upload: uses `/doctor/upload-tmp` or `/patient/upload-tmp`.
- All uploads go to the same `UploadController`.
- Final file paths: `uploads/{table}/{filename}` → served at `/file/uploads/{table}/{filename}`.

### Testing
- Tests use SQLite in-memory (`phpunit.xml` overrides `DB_CONNECTION` and `DB_DATABASE`).
- Migrations run automatically on in-memory SQLite.
- Run with: `composer run test` (clears config first) or `php artisan test --filter=TestName`.

### Dev Environment
- `QUEUE_CONNECTION` is `database` — queue worker must run separately.
- `SESSION_DRIVER` is `database` — ensure migrations are run first.
- `composer run dev` starts all three: artisan serve + queue:listen + vite concurrently.

### File Permissions
- Uploaded files go to `storage/app/documents/`
- Directories: `temp/`, `uploads/{table}/`
- Served via the `document` disk (configured in `config/filesystems.php`)
- Temp files are NOT cleaned up automatically — cleanup is manual.

### Frontend Navigation
- `PortalLayout` uses `<RouterView />` NOT `<slot />` for child route rendering.
- Doctor/patient `user` refs must use `computed(() => auth.user)` for reactivity — not direct `ref` assignment.
- 401 response handling clears the correct storage based on URL path detection.

### ESLint & Prettier
Both are configured in the frontend project. Run `npm run lint` or let your editor auto-fix on save.

### CRUD Generator
A `gen-template.js` script is available in the frontend root for generating new entity configs and view templates.

---

## Appendix A: Complete FIELD_SORTABLE Reference

| Model | FIELD_SORTABLE |
|-------|---------------|
| Users | id, fullname, username, email, role_id, active, created_at, updated_at |
| Roles | id, role_code, role_name, description, active, created_at, updated_at |
| Tasks | id, task_code, task_name, module, active, created_at, updated_at |
| RoleTask | id, role_id, task_id, active, created_at, updated_at |
| Doctors | id, fullname, specialization, email, phone, available, created_at, updated_at |
| Patients | id, fullname, email, phone, gender, birthdate, created_at, updated_at |
| Rooms | id, room_code, room_name, capacity, available, created_at, updated_at |
| Appointments | id, doctor_id, patient_id, room_id, appointment_date, appointment_time, status, created_at, updated_at |
| MedicalRecords | id, doctor_id, patient_id, diagnosis, treatment, prescription, created_at, updated_at |

## Appendix B: Seeder Data Summary

| Table | Count | Notes |
|-------|-------|-------|
| roles | 2 | Super Admin (id=1), Staff |
| tasks | 45 | view-/show-/create-/update-/delete- for 9 entities |
| role_task | 45 | role_id=1 gets ALL tasks |
| rooms | 5 | Examination rooms, ICU, etc. |
| doctors | 10 | Various specializations |
| patients | 25 | With demographics |
| appointments | 32 | Mix of scheduled/completed/cancelled |
| medicalrecords | 11 | Linked to completed appointments |

## Appendix C: Environment Variables

```
JWT_SECRET=          # REQUIRED — not in .env.example, must add manually
DB_CONNECTION=mysql
DB_DATABASE=clinic_laravel
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

## Appendix D: Key Dependencies

### Backend (composer.json)
- laravel/framework ^13.8
- tymon/jwt-auth ^2.3
- laravel/sanctum ^4.0

### Frontend (package.json)
- vue ^3.5
- pinia ^2
- vue-router ^4
- axios ^1
- tailwindcss ^4.0
- vite ^8.0

---

*End of Reference Manual*
