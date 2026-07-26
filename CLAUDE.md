# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A SIRH (HRIS) product built on Laravel 12 + Livewire 3, intended to be deployed as **one dedicated instance per client** (not a shared multi-tenant SaaS — there is no `tenant_id` isolation anywhere in the schema). The product is organized as independent modules mirroring a typical HR suite: Organisation & employés, Temps & présences, Congés & absences, Paie, Documents & signatures, Rapports & pilotage, Administration, Mon espace. Only **Organisation & employés** is implemented so far; it is the foundation module other modules will depend on (employees, sites, departments, positions).

## Commands

```bash
# Setup (fresh clone)
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm install && npm run build

# Local dev (serves PHP, queue listener, log tailer, and Vite together)
composer run dev

# Tests
php artisan test                                  # full suite
php artisan test tests/Feature/Organisation/EmployeeManagementTest.php   # one file
php artisan test --filter=test_admin_can_create_a_site                  # one test

# Reset DB with roles/permissions + demo admin
php artisan migrate:fresh --seed

# Code style (Laravel Pint, no custom pint.json — defaults apply)
vendor/bin/pint
```

Tests run against an in-memory SQLite DB (`phpunit.xml`), independent of the `database/database.sqlite` file used for local dev.

Demo login after seeding: `admin@sirh.test` / `password` (role `super-admin`).

## Architecture

**Stack**: Laravel 12, Livewire 3 (class-based components, not Volt) for app features, Volt only for the Breeze-generated auth pages (`resources/views/livewire/pages/auth/*`, `resources/views/livewire/profile/*`). Tailwind for styling. SQLite by default.

**Authorization**: `spatie/laravel-permission`. Roles: `super-admin`, `rh-admin`, `manager`, `employe` (seeded in `database/seeders/RolesAndPermissionsSeeder.php`, called from `DatabaseSeeder`). Permission middleware aliases (`permission:`, `role:`, `role_or_permission:`) are registered in `bootstrap/app.php`. Routes are gated with `Route::middleware('permission:xxx')` groups rather than per-controller checks; Livewire components that need finer-grained checks (e.g. RGPD erasure) call `auth()->user()->can(...)` inside the action method itself, not just in the view.

**Module layout** (`app/Livewire/<Module>/<Entity>/...`): each entity that needs CRUD gets an `Index` component doing list+create+edit+delete in one page, using a shared `<x-modal>` for the create/edit form. Modals are opened via `$this->dispatch('open-modal', 'name')` / `close-modal` from the Livewire method (not from Alpine directly), so the component can populate its public properties *before* the modal shows for edit. Routes for these pages live in `routes/web.php` under `Route::prefix('organisation')->name('organisation.')`.

**Employees** (`app/Livewire/Organisation/Employees/`) is the more complex sub-module:
- `Index` / `Create` / `Edit` / `Show` are full pages (routed).
- `ContractsPanel` and `DocumentsPanel` are nested Livewire components embedded inside `Show` via tabs (`<livewire:organisation.employees.contracts-panel :employee="$employee" />`), each owning its own CRUD/upload logic scoped to that employee.
- `Employee::nextEmployeeNumber()` generates the matricule (`EMP-00001`) from `withTrashed()->max('id')`.

**File storage**: contract documents and employee documents are stored on the `local` (private) disk, never `public` — they contain sensitive PII (ID scans, salary info) and must not be reachable by URL. Downloads go through permission-gated single-action controllers (`app/Http/Controllers/Organisation/ContractDownloadController.php`, `EmployeeDocumentDownloadController.php`) that stream via `Storage::disk('local')->download(...)`. When adding new uploadable documents anywhere in the app, follow this pattern — do not switch to the `public` disk for convenience.

**Mass assignment vs. internal state**: `Employee::$fillable` deliberately excludes `is_anonymized` and `anonymized_at` so they can never be set through the Create/Edit forms. `Employee::anonymize()` therefore uses `forceFill()->save()`, not `update()` — if you add other model methods that set guarded/internal columns, use `forceFill()` there too, not `update()`/`fill()`, or the write will silently no-op.

**RGPD/GDPR erasure**: `Employee::anonymize()` (in `app/Models/Employee.php`) blanks personal fields (name→placeholder, DOB, national ID, contact info, address) and deletes all `EmployeeDocument` records for that employee (which also deletes the underlying files via a `deleting` model event in `EmployeeDocument`/`Contract`), while preserving `Contract` records and dates for legal/payroll retention requirements. It's gated behind the `employees.anonymize` permission and triggered from the employee `Show` page. Any future feature touching employee PII should be aware this method exists and may need to interact with it.

**Migration ordering**: file timestamps in `database/migrations/` were manually adjusted (not auto-generated order) so that `sites` → `departments` → `positions` → `employees` → `contracts` → `employee_documents` run in FK-dependency order. `departments.manager_id` references `employees.id`, but `employees` must be created after `departments` (which employees FK into) — this circular-ish dependency is resolved by creating `departments.manager_id` as a plain indexed column with no FK constraint at creation time, then adding the FK constraint via `Schema::table('departments', ...)` inside the `create_employees_table` migration's `up()`, once the `employees` table exists. Keep this pattern if you add more cross-referencing entities.

**Self-referencing employees**: `manager_id` on `Employee` is a nullable self-FK (`employees.manager_id -> employees.id`), giving a manager/subordinate tree via `Employee::manager()` / `Employee::subordinates()`.
