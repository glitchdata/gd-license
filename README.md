# GD Login (Laravel)

A Laravel 11 application that delivers an email/password login portal with registration, a session-backed dashboard, and demo data seeded via migrations. It recreates the previous aesthetic while adopting Laravel's authentication stack (guards, middleware, CSRF protection).

## Features

- Guest-only routes for `/login` and `/register`, plus authenticated `/dashboard` (`web` guard) and `/logout` POST endpoint.
- Controllers dedicated to login, registration, and dashboard rendering with session regeneration to prevent fixation.
- Dashboard now highlights account details plus a license inventory table seeded with demo data.
- Admin console for CRUD management of licenses (protected by an `is_admin` flag on users).
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
6. Open `http://127.0.0.1:8000/login` to exercise the flow.

### Admin access

- The seeded `demo@example.com` user ships with `is_admin=true`, so it can reach `/admin/licenses`.
- To promote another user, set `is_admin` to `1` in the `users` table or run a quick tinker command:
	```bash
	php artisan tinker --execute="App\\Models\\User::where('email','you@example.com')->update(['is_admin' => true]);"
	```


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
