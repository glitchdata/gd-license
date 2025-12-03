# License Server Architecture

## Goals
- Validate and meter license keys for paid products.
- Support activation tracking per domain/instance with configurable limits.
- Provide lightweight admin issuance endpoint and CLI utility for shared hosting.
- Keep the codebase dependency-free for SiteGround's default PHP stack.

## Technology Stack
- PHP 8.1+ using PDO for MySQL access (compatible with SiteGround's managed PHP builds).
- MySQL 5.7/8.0 with InnoDB tables and timezone-safe timestamps.
- Single public entry point (`public_html/index.php`) to simplify hosting under `public_html`.
- Environment-driven configuration via `config/config.php` that reads SiteGround "PHP Variables" or `.env` values.

## Database Schema (summary)
1. `products`
   - `id` (PK), `code` (unique), `name`, `max_activations` (default limit), timestamps.
2. `licenses`
   - `id` (PK), `product_id` (FK), `license_key` (unique), `status`, `max_activations`, `expires_at`, `notes`, timestamps.
3. `license_activations`
   - `id` (PK), `license_id` (FK), `instance_id`, `domain`, `ip_address`, `activated_at`, `last_validated_at`.

## API Surface
- `POST /api/licenses/issue` (admin token required)
  - Issues or regenerates license keys for a product, optional expiration and activation ceiling.
- `POST /api/licenses/activate`
  - Records/refreshes an activation for a device/domain pair.
- `POST /api/licenses/validate`
  - Lightweight heartbeat to check validity, expiration, and activation allocation.
- `POST /api/licenses/deactivate`
  - Frees an activation slot when a user unregisters a device/domain.

All responses are JSON with `success`, `data`, and `error` envelopes plus HTTP status codes.

## Operational Notes for SiteGround
- Database credentials are managed via SiteGround Site Tools → MySQL. These values map to `LICENSE_DB_*` env vars in `config/config.php`.
- Secrets (admin token) should be stored via SiteGround's `php.ini` editor or `.env` outside `public_html`.
- Cron jobs (optional) can call `scripts/prune_inactive.php` (future) to remove stale activations.
