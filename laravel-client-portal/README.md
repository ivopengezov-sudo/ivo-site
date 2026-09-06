# Laravel Client Portal (Document Submission & Review)

A small, self-contained Laravel demo of a pattern that comes up constantly
in client work: a customer submits a request together with a set of
documents, staff review and process it, and the finished files come back
through the same portal. Think tender/estimate submissions, design review
requests, agency onboarding, or any "upload → review → deliver" workflow.

## What it does

- Clients sign in, submit a new project with a title, description, and any
  number of supporting documents (PDF, Word, Excel, images, ZIP).
- Every project moves through a status: `submitted` → `in_progress` →
  `needs_info` (staff request more documents) → `completed`.
- Clients can add more files to an existing project at any time — most
  commonly after staff mark it `needs_info`.
- A staff-only admin area lists all projects (filterable by status),
  lets staff change status, leave an internal note, and upload the
  finished deliverables for the client to download.
- **Files are never stored under a public path or served by a guessable
  URL.** Everything lives on a private disk, and every download goes
  through a single controller action that checks the requester actually
  owns the project (or is staff) before streaming the file back.
- Uploads are validated by extension and size before they touch disk
  (`ProjectFileUploadService`), so the same rules apply whether the file
  came from the client-facing form or the staff deliverable upload.

## Structure

```
app/
  Models/
    Project.php               Project + status constants/scopes
    ProjectFile.php            One uploaded file, tagged client/admin
  Services/
    ProjectFileUploadService.php   Validates + stores files on a private disk
  Http/
    Controllers/
      ProjectController.php          Client: list/create/show/upload
      ProjectFileController.php      Authorization-checked file download
      Admin/ProjectController.php    Staff: list/show/status/deliverables
    Middleware/
      EnsureUserIsAdmin.php
database/migrations/
  2026_02_01_000001_create_projects_table.php
  2026_02_01_000002_create_project_files_table.php
routes/web.php
```

## Tech stack

Laravel 11, Eloquent, Laravel's local filesystem disk (private), standard
form-request validation.

## Setup (if you want to run it)

```bash
composer create-project laravel/laravel client-portal
cd client-portal
# copy app/, database/migrations/, and routes/web.php from this repo in,
# merging with the files Laravel already generated
```

Add a private disk in `config/filesystems.php`:

```php
'projects' => [
    'driver' => 'local',
    'root' => storage_path('app/projects'),
    'visibility' => 'private',
],
```

Register the admin middleware alias in `bootstrap/app.php`:

```php
$middleware->alias(['admin' => \App\Http\Middleware\EnsureUserIsAdmin::class]);
```

Then run migrations:

```bash
php artisan migrate
```

## Notes

This is a portfolio code sample focused on the upload/authorization/
workflow logic, not a finished app — Blade views, the `users.is_admin`
migration, and auth scaffolding (`php artisan make:auth` / Breeze) are
intentionally left out for brevity. The interesting part is how uploads
are validated and stored on a private disk, and how every download is
gated by an ownership check rather than relying on an unguessable path.
