# GD Login (Laravel)

A Laravel 11 application that delivers an email/password login portal with registration, a session-backed dashboard, and demo data seeded via migrations. It recreates the previous aesthetic while adopting Laravel's authentication stack (guards, middleware, CSRF protection).

## Features

- Guest-only routes for `/login` and `/register`, plus authenticated `/dashboard` (`web` guard) and `/logout` POST endpoint.
- Controllers dedicated to login, registration, and dashboard rendering with session regeneration to prevent fixation.
- Dashboard now highlights account details plus a license inventory table seeded with demo data.
- Dashboard offers a self-serve purchase form with per-seat pricing, product-defined license durations, and a mock credit card checkout so users can add new licenses tied to their account in seconds.
- Licenses now maintain allow-listed domains via `license_domains` records so you can restrict API validations or audits to specific tenants.
- Admin console for CRUD management of the product catalog, license seat allocations, optional user ownership assignments, and user onboarding/offboarding (all protected by an `is_admin` flag).
- Public-facing homepage (`/`) spotlights the platform, links to the external shop (`/shop`), and surfaces an API Lab (`/api-lab`) so prospects can validate licenses without signing in.
- Lightweight API endpoint for validating licenses by product code (`POST /api/licenses/validate`).
- Admin tools include an in-browser tester for the validation API (`/admin/tools/license-validation`).
- Eloquent-powered `users` table migrations and a seeded demo account (`demo@example.com` / `password`).
- Blade layout + views that provide the polished UI without requiring a frontend build step (Tailwind/Vite can be added later).

## Requirements

- PHP 8.2+
- Composer 2.x
- MySQL 8+ (or MariaDB equivalent)
- Node.js 18+ (optional, only if you plan to run the default Vite dev server)

## Setup

1. **Install PHP dependencies**
	```bash
	composer install
	```
2. **Install frontend dependencies (optional)**
	```bash
	npm install
	```
3. **Create your environment file**
	```bash
	cp .env.example .env
	php artisan key:generate
	```
	Adjust `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` to match your MySQL instance (defaults target `gd_login_php`).
4. **Prepare the database**
	```bash
	mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS gd_login_php CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
	php artisan migrate --seed
	```
	The seeder creates the demo user mentioned above.
5. **Run the dev servers**
	```bash
	php artisan serve
	# Optional: npm run dev
	```
	The document root has been renamed to `public_html`, so point your web server (or `php -S` command) at that directory if you are not using `php artisan serve`.
6. Open `http://127.0.0.1:8000/` to preview the marketing homepage, then continue to `/shop`, `/api-lab`, or `/login` as needed.

### Admin access

- The seeded `demo@example.com` user ships with `is_admin=true`, so it can reach `/admin/products`, `/admin/licenses`, and `/admin/users`.
- API tester lives at `/admin/tools/license-validation` (admin-only) and fires requests straight to `/api/licenses/validate` for quick manual checks.
- To promote another user, set `is_admin` to `1` in the `users` table or run a quick tinker command:
	```bash
	php artisan tinker --execute="App\\Models\\User::where('email','you@example.com')->update(['is_admin' => true]);"
	```

### Self-serve checkout

- Every product now carries a `price` (stored as a decimal), representing the per-seat cost in USD.
- Products also store a `duration_months`, which determines how long a purchased license stays active before renewal.
- From `/dashboard`, authenticated users can select a product, choose the number of seats, and enter mock credit card details.
- Buyers can optionally provide a primary domain during checkout; admins can edit domain allow-lists later from the license management screen.
- The checkout flow computes the total (`price × seats`), sets the expiration date by adding `duration_months` to the current time, and calls a simple in-app gateway (`App\Services\FakePaymentGateway`) that simulates a charge and returns a transaction ID for the success banner.
- Real processors would replace this service; for now, any 12+ digit card number works unless the sandbox intentionally rejects the amount as part of the demo logic.

## License validation API

`POST /api/licenses/validate`

Request body:

```json
{
	"product_code": "LIC-ANL-01",
	"seats_requested": 3
}
```

Example response:

```json
{
	"valid": true,
	"reason": null,
	"seats_requested": 3,
	"seats_available": 7,
	"expires_at": "2026-04-01",
	"license": {
		"id": 1,
		"seats_total": 25,
		"seats_used": 18
	},
	"product": {
		"id": 7,
		"name": "Analytics Pro",
		"product_code": "LIC-ANL-01",
		"vendor": "Glitchdata",
		"category": "Analytics"
	}
}
```

Failures return `valid: false` plus a `reason` string (e.g., `License not found.`, `License expired.`, or `Insufficient seats.`). Add API authentication (token header, gateway, etc.) before exposing the endpoint publicly.


## Key credentials

- Email: `demo@example.com`
- Password: `password`

## Project highlights

```
app/
├── Http/Controllers/Auth/LoginController.php      # login + logout handler
├── Http/Controllers/Auth/RegisterController.php   # registration logic
├── Http/Controllers/DashboardController.php       # protected page
resources/views/
├── layouts/app.blade.php                          # shared layout + inline styles
├── auth/login.blade.php                           # login form
├── auth/register.blade.php                        # registration form
└── dashboard.blade.php                            # session-backed dashboard
routes/web.php                                     # route definitions/middleware
```

## Next steps

- Replace the inline styling with Tailwind via Vite if you want utility-first workflows.
- Add password reset, email verification, or social login using Laravel Breeze or Fortify.
- Containerize the stack (Sail) or deploy to Forge/Vapor for production.

## License

This project is based on the [Laravel](https://laravel.com) framework and inherits its [MIT License](LICENSE.md).
