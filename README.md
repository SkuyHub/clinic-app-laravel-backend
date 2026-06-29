# Clinic App — Laravel Backend

Clinic management system API built with Laravel 13. JWT authentication with three user types (admin, doctor, patient), service-oriented architecture, and config-driven CRUD.

## Quick Start

```bash
composer run setup
```

This installs dependencies, copies `.env.example`, generates `APP_KEY`, runs migrations, installs npm deps, and builds the frontend.

**Important**: Add `JWT_SECRET` to `.env` manually — it is not auto-generated. `JWT_SECRET=` is now included in `.env.example` as a reminder.

## Database

Supports both **MySQL** (default) and **PostgreSQL**. To switch to PostgreSQL, see [Database Setup](#database-setup) below.

## Commands

| Command | What it does |
|---------|-------------|
| `composer run setup` | Fresh install: deps, .env, key, migrate, npm install + build |
| `composer run dev` | `artisan serve` + `queue:listen` + `vite` concurrently |
| `composer run test` | Clears config, runs `php artisan test` |
| `php artisan test --filter=TestName` | Single test |
| `vendor/bin/pint` | Lint (Laravel Pint) |
| `php artisan migrate:fresh --seed` | Reset DB + seed all tables |

## Architecture

### Service-oriented routing

All named API routes are defined in `config/service.php` and auto-registered in `routes/api.php:9-22`. Each entry has `name`, `class`, `type` (HTTP method), `end_point`, and `guard` (`null` = public; `api`/`doctor`/`patient`).

The `AppServiceProvider` binds short service names to their classes.

### Service pattern

Every service extends `App\CoreService\CoreService` and implements `prepare($input)` + `process($input, $originalData)`. Input is auto-validated via `validation()`. Throw `App\CoreService\CoreException(message, statusCode, errorList)` for errors. Services with `$transaction = true` are wrapped in DB transactions.

### CallService entry points

| Method | Use case | Permission check | JSON wrapping |
|--------|----------|-----------------|---------------|
| `CallService::run(name, input)` | HTTP route handlers | No | Returns JSON |
| `CallService::execute(name, input)` | Permission-gated handlers | Yes (`$object->task`) | Returns JSON |
| `CallService::call(name, input)` | Internal service calls | No | Raw, throws on error |

### CRUD pattern

Generic CRUD routes at `routes/api.php:24-31` (all behind `setguard:api` + `auth.rest`):

```
GET  /{model}/list
GET  /{model}/dataset       (same backend as list)
GET  /{model}/{id}/show
POST /{model}/create
PUT  /{model}/update
DELETE /{model}/delete
```

`CrudController` delegates to `App\Services\Crud\*` services (Get, Find, Add, Edit, Delete) using DB::table() queries with manual timestamps. Only fields declared in `FIELD_ADD`/`FIELD_EDIT` are persisted.

### Model conventions

Models define schema via class constants: `TABLE`, `FILEROOT`, `IS_ADD`, `IS_EDIT`, `IS_LIST`, `IS_DELETE`, `FIELD_LIST`, `FIELD_ADD`, `FIELD_EDIT`, `FIELD_VIEW`, `FIELD_READONLY`, `FIELD_FILTERABLE`, `FIELD_SEARCHABLE`, `FIELD_SORTABLE`, `FIELD_TYPE`, `FIELD_RELATION`, `FIELD_VALIDATION`, `FIELD_UNIQUE`, `FIELD_UPLOAD`, `FIELD_DEFAULT_VALUE`.

`BaseModel` defines static lifecycle hooks: `beforeInsert`, `afterInsert`, `beforeUpdate`, `afterUpdate`, `beforeDelete`, `afterDelete`, `beforeList`, `afterDetil`.

Authenticatable models (`Users`, `Doctors`, `Patients`) extend `Authenticatable` (not `BaseModel`) but follow the same constant/hook conventions and implement `JWTSubject`. Use `Hash::make()` for passwords.

### Auth guards

Three JWT guards: `api` (admin/staff), `doctor` (doctors), `patient` (patients). `SetGuard` middleware switches `auth.defaults.guard` before `AuthApiMiddleware` validates the JWT. Login endpoints use `guard: null` and return a JWT.

`hasPermission()` checks `role_task` join table; role_id=1 is super-admin (always returns true).

### File uploads

`POST /upload-tmp` (or `/doctor/upload-tmp`, `/patient/upload-tmp`) stores to `storage/app/documents/temp/`. CRUD services move temp files to `storage/app/documents/uploads/{table}/` via `HandlesFileUploads` trait. Final paths served at `/file/{path}`. Upload endpoints are guard-aware — the frontend detects the active portal and routes to the correct endpoint.

### Security

- **Rate limiting**: Login endpoints (`/login`, `/doctor/login`, `/patient/login`) are throttled at 5 requests per minute.
- **CORS**: Origin is configurable via `CORS_ALLOWED_ORIGINS` env variable. Allowed methods restricted to `GET, POST, PUT, DELETE`. Allowed headers restricted to `Content-Type, Authorization, X-Requested-With, Accept`.
- **Passwords**: Hashed with `Hash::make()` in `beforeInsert`/`beforeUpdate`. All models exclude `password` from `FIELD_LIST`/`FIELD_VIEW` and use the `$hidden` array.
- **Email normalization**: Login search and `beforeInsert` hooks lowercase emails. On PostgreSQL this ensures case-insensitive matching; on MySQL it standardizes stored data.

## Services

### Auth

| Service | Method | Endpoint | Guard |
|---------|--------|----------|-------|
| `DoUnifiedLogin` | POST | `/login` | null |
| `Me` | GET | `/me` | api |
| `DoLogout` | POST | `/logout` | api |
| `UpdateAdminProfile` | PUT | `/profile` | api |
| `UpdateDoctorProfile` | PUT | `/doctor/profile` | doctor |
| `UpdatePatientProfile` | PUT | `/patient/profile` | patient |
| `PatientBookAppointment` | POST | `/patient/book` | patient |
| `ListAvailableDoctors` | GET | `/doctors/available` | null |

### Scoped CRUD

| Service | Method | Endpoint | Guard | Scope |
|---------|--------|----------|-------|-------|
| `DoctorAppointments` | GET | `/doctor/appointments` | doctor | `WHERE doctor_id = auth.id` |
| `DoctorMedicalRecords` | GET | `/doctor/medicalrecords` | doctor | `WHERE doctor_id = auth.id` |
| `PatientAppointments` | GET | `/patient/appointments` | patient | `WHERE patient_id = auth.id` |
| `PatientMedicalRecords` | GET | `/patient/medicalrecords` | patient | `WHERE patient_id = auth.id` |

## Database

### Tables

| Table | Model |
|-------|-------|
| `users` | `Users` (admin/staff, JWT) |
| `doctors` | `Doctors` (JWT) |
| `patients` | `Patients` (JWT) |
| `roles` | `Roles` |
| `tasks` | `Tasks` |
| `role_task` | `RoleTask` |
| `rooms` | `Rooms` |
| `appointments` | `Appointments` |
| `medicalrecords` | `MedicalRecords` |

### Database Setup

**MySQL (default):**

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=clinic_laravel
DB_USERNAME=root
DB_PASSWORD=
```

**PostgreSQL:**

```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=clinic_laravel
DB_USERNAME=postgres
DB_PASSWORD=yourpassword
```

Ensure the `pdo_pgsql` and `pgsql` PHP extensions are enabled in `php.ini`. Migrations use portable syntax — `enum` columns replaced with `string` + CHECK constraints, `unsigned` modifiers removed, and `->after()` column ordering removed.

If migrating existing data to PostgreSQL, lowercase all stored emails first:
```sql
UPDATE users SET email = LOWER(email);
UPDATE doctors SET email = LOWER(email);
UPDATE patients SET email = LOWER(email);
```

### Indexes

Explicit indexes added on:
- `appointments.status`
- `appointments.appointment_date`
- `doctors.available`

### Seeded data

`php artisan migrate:fresh --seed` populates:

| Table | Count |
|-------|-------|
| roles | 2 (super-admin, receptionist) |
| tasks | 45 (9 modules × 5 verbs) |
| role_task | 16 mappings |
| users | 2 (admin, receptionist) |
| rooms | 6 |
| doctors | 10 |
| patients | 25 |
| appointments | 32 |
| medicalrecords | 11 |

## Testing

SQLite in-memory (`phpunit.xml` sets `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`). Migrations run automatically. Env overrides: cache=array, queue=sync, session=array.

## Environment Quirks

- `DB_CONNECTION` supports `mysql` and `pgsql` — tests override to `sqlite`
- `QUEUE_CONNECTION` is `database` — queue worker must run separately
- `SESSION_DRIVER` is `database` — ensure migrations are run before using the app
- `JWT_SECRET` must be added manually to `.env`
- `.npmrc` sets `ignore-scripts=true` — npm post-install scripts don't run
- `CORS_ALLOWED_ORIGINS` should be set in `.env` for non-localhost deployments

## Key Dependencies

- **tymon/jwt-auth** — JWT authentication (3 guards)
- **Tailwind CSS 4** via Vite plugin
- **Laravel Pint** — code style

## Frontend

The Vue 3 admin panel + doctor/patient portals live at [clinic-app-laravel-frontend](https://github.com/SkuyHub/clinic-app-laravel-frontend).
