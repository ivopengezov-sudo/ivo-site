# Laravel Appointment Booking System

A small, self-contained Laravel demo showing how I structure a
booking-and-payment flow — the pattern behind most "let customers book a
service and pay online" jobs (salons, clinics, consultants, rentals,
transport).

## What it does

- Public visitors browse active **services** (name, duration, price).
- Picking a service shows the free **time slots** for a chosen day —
  generated from business hours and filtered against existing bookings,
  so double-booking the same slot is impossible even under concurrent
  requests (the overlap check runs as a real SQL `WHERE`, not in PHP).
- Submitting the booking form creates a `pending` **booking** and redirects
  the customer to a **Stripe Checkout** session for payment.
- A **Stripe webhook** (`checkout.session.completed`) marks the booking
  `paid` once Stripe confirms the charge — the booking is never trusted as
  paid just because the browser redirected back successfully.
- A staff-only **admin dashboard** (behind `auth` + an `admin` middleware)
  lists all bookings with filtering by status, and lets staff mark a
  booking `paid` / `cancelled` / `completed` manually (walk-ins, refunds,
  no-shows).

## Structure

```
app/Models/Service.php                       Service catalogue (price, duration)
app/Models/Booking.php                       Booking + the overlap-detection query scope
app/Services/StripeCheckoutService.php        Turns a Booking into a Stripe Checkout Session
app/Http/Controllers/BookingController.php    Public booking flow (browse → slot → pay)
app/Http/Controllers/StripeWebhookController.php  Confirms payment asynchronously
app/Http/Controllers/Admin/DashboardController.php Staff bookings overview
app/Http/Middleware/EnsureUserIsAdmin.php     Gate for the admin routes
database/migrations/..._create_services_table.php
database/migrations/..._create_bookings_table.php
routes/web.php
```

## Tech stack

- **Laravel** (Eloquent ORM, migrations, route model binding, validation)
- **Stripe Checkout** via `stripe/stripe-php`, plus signed webhook handling
- MySQL/SQLite (any Laravel-supported database — the migrations use plain
  Blueprint calls, no vendor-specific SQL)

## Running it

This folder contains the application-specific code, not a full Laravel
skeleton. To run it inside a fresh install:

```bash
composer create-project laravel/laravel booking-app
cd booking-app
composer require stripe/stripe-php

# copy this folder's app/, database/migrations/, and routes/web.php
# over the equivalent paths in the new project

php artisan migrate
php artisan db:seed   # optional: seed a couple of sample services
```

Add to `.env`:

```
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

and forward Stripe events during local development with the Stripe CLI:

```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```

## Notes

This is a portfolio code sample focused on the booking/payment logic —
the part clients actually care about getting right (no double bookings,
no "paid" status trusted from the browser redirect alone). Blade views,
the `User.is_admin` column/migration, registering the `admin` middleware
alias (`bootstrap/app.php` → `$middleware->alias(['admin' => EnsureUserIsAdmin::class])`),
and auth scaffolding are left out for brevity; they're standard Laravel
and would take minutes to wire up with `laravel/breeze` for a real
project.
