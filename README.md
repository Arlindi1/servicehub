# ServiceHub — Client Portal & Project Delivery Platform

ServiceHub is a polished client portal built with **Laravel + Inertia + Vue 3 + Vite**. It supports lightweight multi-tenancy (scoped by `organization_id`), role-based access (Owner/Staff/Client), project delivery workflows, invoices with PDF export, in-app notifications, and an activity log.

## Stack
- Laravel (session auth + email verification)
- Inertia.js + Vue 3 + Vite
- SQLite for local development (`database/database.sqlite`)
- Roles/permissions: `spatie/laravel-permission`
- Invoice PDFs: `barryvdh/laravel-dompdf`

## Install (Local)
1) Install PHP deps: `composer install`
2) Create env: `copy .env.example .env` (Windows) / `cp .env.example .env` (macOS/Linux)
3) App key: `php artisan key:generate`
4) Create SQLite file: `php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"`
5) Migrate + seed demo data: `php artisan migrate:fresh --seed`
6) Install JS deps: `npm install`
7) Start Vite: `npm run dev`
8) Start Laravel: `php artisan serve`

Optional shortcuts:
- One-shot setup: `composer run setup`
- Run server + Vite together: `composer run dev`

## Deploy to Render (Free)
This repo includes Docker + a `render.yaml` Blueprint for Render.

1) Create a **Postgres (Free)** database (or use the Blueprint).
2) Create a **Web Service (Free)** with **Environment = Docker** and point it at this repo.
3) Set env vars (Render dashboard → Environment):
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_KEY=...` (generate with `php artisan key:generate --show`)
   - `DB_CONNECTION=pgsql`
   - `DATABASE_URL=...` (from your Render Postgres “Internal Database URL”)
   - `APP_URL=https://<your-service>.onrender.com`
   - `ASSET_URL=https://<your-service>.onrender.com`
4) Set Mailgun SMTP env vars (Render blocks ports 25/465/587; use 2525):
   - `MAIL_MAILER=smtp`
   - `MAIL_HOST=smtp.mailgun.org`
   - `MAIL_PORT=2525`
   - `MAIL_USERNAME=postmaster@YOUR_DOMAIN`
   - `MAIL_PASSWORD=YOUR_MAILGUN_SMTP_PASSWORD`
   - `MAIL_ENCRYPTION=tls`
   - `MAIL_FROM_ADDRESS=no-reply@YOUR_DOMAIN`
   - `MAIL_FROM_NAME=ServiceHub`
5) Deploy. On startup, the container runs:
   - `composer install --no-dev`
   - `php artisan config:cache`
   - `php artisan route:cache`
   - `php artisan migrate --force`

Notes:
- Render Free Postgres databases expire after ~30 days; free web services may sleep after inactivity.
- Render Free has an ephemeral filesystem; uploads stored on `local` disk are not durable. Use S3/R2/etc for real production uploads.

## Demo Credentials
All demo accounts use password: `password`

- **Owner**: `owner@acme.test` (full access, including `/app/activity`)
- **Staff**: `staff1@acme.test`, `staff2@acme.test` (projects/tasks/files/comments/invoices; no team/settings/activity)
- **Client**: `client1@acme.test`, `client2@acme.test`, `client3@acme.test` (portal-only; can comment + upload “Client Upload” files; invoices read-only)

## Feature List
- Organizations (single org per user for MVP) + org-scoped data access
- Clients module (Owner/Staff)
- Projects module + staff assignment + project tabs (Tasks, Files, Discussion)
- Tasks with status + assignee + due date
- Secure project file uploads/downloads (no public links)
- Project discussion thread (staff/client)
- Invoices + invoice items with server-side totals + PDF download
- In-app notifications (comment/file/invoice sent) for Owner/Staff
- Activity log (Owner only) with filters + pagination

## Screenshots (Placeholders)
- Dashboard (`/app/dashboard`)
- Project details (`/app/projects/{id}`)
- Invoices (`/app/invoices/{id}`)
- Client portal dashboard (`/portal/dashboard`)

## Troubleshooting (Windows)
- **OneDrive performance**: if dev feels slow, move the repo out of OneDrive and into a local path (e.g. `C:\\dev\\ServiceHub`).
- **SQLite file missing**: ensure `database/database.sqlite` exists and `.env` has `DB_CONNECTION=sqlite`.
- **Port already in use**: stop the existing process or run `php artisan serve --port=8001`.
- **Stale config**: run `php artisan config:clear` after changing `.env`.

## Production Email (Mailgun SMTP)
ServiceHub uses Laravel SMTP mail in production. Configure these env vars:

- `MAIL_MAILER=smtp`
- `MAIL_HOST=smtp.mailgun.org` (or `smtp.eu.mailgun.org`)
- `MAIL_PORT=2525`
- `MAIL_ENCRYPTION=tls` (or `MAIL_SCHEME=tls`)
- `MAIL_USERNAME=postmaster@YOUR_DOMAIN`
- `MAIL_PASSWORD=YOUR_MAILGUN_SMTP_PASSWORD`
