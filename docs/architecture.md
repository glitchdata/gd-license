# License Server Architecture

## Goals
- Validate and meter license keys for paid products.
- Support activation tracking per domain/instance with configurable limits.
- Provide lightweight admin issuance endpoint/GUI plus an end-user portal for reviewing assigned licenses.
- Keep the codebase dependency-free for SiteGround's default PHP stack.

## Technology Stack
- PHP 8.1+ using PDO for MySQL access (compatible with SiteGround's managed PHP builds).
- MySQL 5.7/8.0 with InnoDB tables and timezone-safe timestamps.
- Single public entry point (`public_html/index.php`) that doubles as the landing portal (HTML) and serves API traffic, plus static operator (`/admin/`) and customer (`/user/`) GUIs.
- Environment-driven configuration via `config/config.php` that reads SiteGround "PHP Variables" or `.env` values.

## Database Schema (summary)
1. `products`
   - `id` (PK), `code` (unique), `name`, `max_activations` (default limit), timestamps.
2. `licenses`
   - `id` (PK), `product_id` (FK), `license_key` (unique), `status`, `max_activations`, `expires_at`, `notes`, timestamps.
3. `license_activations`
  - `id` (PK), `license_id` (FK), `instance_id`, `domain`, `ip_address`, `activated_at`, `last_validated_at`.
4. `users`
  - `id` (PK), `email` (unique), `full_name`, `password_hash`, timestamps, `last_login_at`.
5. `user_licenses`
  - `id` (PK), `user_id` (FK), `license_id` (FK), `assigned_at`, unique pair to avoid duplicates.

## API Surface
- `POST /api/licenses/issue` (admin token required)
  - Issues or regenerates license keys for a product, optional expiration and activation ceiling.
- `POST /api/licenses/activate`
  - Records/refreshes an activation for a device/domain pair.
- `POST /api/licenses/validate`
  - Lightweight heartbeat to check validity, expiration, and activation allocation.
- `POST /api/licenses/deactivate`
  - Frees an activation slot when a user unregisters a device/domain.
- `POST /api/users/login`
  - Validates email/password, starts a PHP session, and returns the profile + assigned licenses.
- `GET /api/users/me`
  - Returns the current session's profile plus license list (uses SameSite Strict cookie).
- `GET /api/users/licenses`
  - License list only, handy for polling dashboards.
- `POST /api/users/logout`
  - Destroys the session.

All responses are JSON with `success`, `data`, and `error` envelopes plus HTTP status codes.

## Operational Notes for SiteGround
- Database credentials are managed via SiteGround Site Tools → MySQL. These values map to `LICENSE_DB_*` env vars in `config/config.php`.
- Secrets (admin token) should be stored via SiteGround's `php.ini` editor or `.env` outside `public_html`.
- Customer logins rely on PHP sessions, so enforce HTTPS and, ideally, SiteGround's password-protected directories or WAF rules around `/user/`.
- Cron jobs (optional) can call `scripts/prune_inactive.php` (future) to remove stale activations.
