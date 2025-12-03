# GD License Server

A dependency-free PHP + MySQL license service tailored for SiteGround shared hosting. It tracks license issuance, activation limits, deactivations, and validation heartbeats through a small JSON API.

## Highlights
- MySQL schema for products, licenses, and per-instance activations.
- Single entry point API (`public_html/index.php`) routed via `.htaccess` plus an optional operator GUI in `public_html/admin/`.
- Admin-protected license issuance endpoint plus CLI helper for maintenance windows.
- Configurable CORS and timezone plus `.env`-friendly configuration.
- Works on SiteGround Site Tools (PHP 8.1/8.2) without composer or system packages.

## Requirements
- PHP 8.1+ with PDO MySQL (enabled by default on SiteGround).
- MySQL 5.7/8.0 database and credentials.
- Ability to set environment variables (Site Tools → Devs → PHP Variables or `.env`).

## Quick Start
1. Clone/download this repository locally.
2. Copy `.env.example` to `.env` (or set values via SiteGround PHP Variables) and update credentials + admin token.
3. Create a MySQL database and user in Site Tools → MySQL.
4. Import `sql/schema.sql` via phpMyAdmin or `mysql` CLI to create tables and a sample product.
5. Upload the project so that the repository's `public_html/` directory maps to your hosting `public_html/` (or configure SiteGround to use it directly as the document root). Everything else should sit outside the web root for safety.
6. Hit `https://your-domain/api/licenses/validate` with a JSON payload to verify responses or open `/admin/` in a protected browser session for GUI testing.

## Configuration
`config/config.php` pulls from environment variables so you do not have to edit PHP files in production. Supported variables:

| Variable | Purpose |
| --- | --- |
| `LICENSE_DB_HOST` | Database hostname (often `127.0.0.1` on SiteGround). |
| `LICENSE_DB_PORT` | MySQL port, default `3306`. |
| `LICENSE_DB_NAME` | Database/schema name. |
| `LICENSE_DB_USER` & `LICENSE_DB_PASS` | DB credentials that own the schema. |
| `LICENSE_ADMIN_TOKEN` | Shared secret for the `issue` endpoint. Change immediately. |
| `LICENSE_ALLOWED_ORIGINS` | Comma-separated origins or `*` for CORS. |
| `LICENSE_TZ` | Timezone ID used for timestamps.

SiteGround: go to Site Tools → Devs → PHP Variables to add these safely. Alternatively, create an `.env` file above `public_html` and load it via your hosting control panel.

## Database Setup
Run the schema SQL after creating the database:

```bash
mysql -h your-host -u your-user -p your_db < sql/schema.sql
```

The script seeds an example product (`APP_PRO`). Add more via:

```sql
INSERT INTO products (code, name, max_activations, created_at, updated_at)
VALUES ('PLUGIN_X', 'Plugin X', 5, NOW(), NOW());
```

## Deploying on SiteGround
1. Upload the repo (via Git deploy, SFTP, or SiteGround Git). Keep `config`, `src`, `scripts`, `sql`, and `docs` outside `public_html` when possible.
2. Ensure the repo's `public_html/` directory is deployed as the document root (or symlinked there). The included `.htaccess` routes `/api/*` calls to `index.php`, while static assets like `/admin/` load directly.
3. In Site Tools → Devs → PHP Manager select PHP 8.2 and enable OPcache.
4. In PHP Variables, set the environment variables mentioned above.
5. In Site Tools → MySQL import the schema and verify connectivity using the CLI helper (`php scripts/create_license.php --product=APP_PRO`).

## API Reference
All endpoints accept and return JSON. POST only. Base path: `/api/licenses/*`.

| Endpoint | Auth | Body Fields | Description |
| --- | --- | --- | --- |
| `POST /api/licenses/issue` | `Authorization: Bearer <LICENSE_ADMIN_TOKEN>` | `product_code` (required), `license_key`, `expires_at`, `max_activations`, `notes`, `status` | Issues a license key. |
| `POST /api/licenses/activate` | none | `license_key`, `product_code`, `instance_id`, `domain`, `user_agent` | Reserves/refreshes an activation slot. |
| `POST /api/licenses/validate` | none | `license_key`, `product_code`, `instance_id?` | Checks status/expiry and updates heartbeat for the instance if supplied. |
| `POST /api/licenses/deactivate` | none | `license_key`, `product_code`, `instance_id` | Releases an activation slot. |

### Example Requests
Activate a license:

```bash
curl -X POST https://example.com/api/licenses/activate \
  -H 'Content-Type: application/json' \
  -d '{
    "license_key": "ABCD1-EFGH2-IJKL3-MNOP4",
    "product_code": "APP_PRO",
    "instance_id": "site-123",
    "domain": "client.com",
    "user_agent": "woocommerce/8.0"
  }'
```

Issue a license (admin token required):

```bash
curl -X POST https://example.com/api/licenses/issue \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer <your-admin-token>' \
  -d '{
    "product_code": "APP_PRO",
    "expires_at": "+1 year",
    "max_activations": 3
  }'
```

Responses share the shape:

```json
{
  "success": true,
  "data": {
    "license_key": "ABCD1-EFGH2-IJKL3-MNOP4",
    "product": {
      "code": "APP_PRO",
      "name": "Example Product"
    },
    "status": "active",
    "expires_at": "2026-01-01 00:00:00",
    "max_activations": 3,
    "activations_in_use": 1,
    "activations_remaining": 2
  }
}
```

Errors return HTTP 4xx/5xx with:

```json
{"error": "Activation limit reached."}
```

## CLI Utilities
Use the helper to mint keys during migrations or when HTTP access is inconvenient:

```bash
php scripts/create_license.php --product=APP_PRO --expires="2026-12-31" --activations=5
```

It prints the license summary as JSON. The script reuses the same config/bootstrap stack, so ensure environment variables are available to the CLI (`.env` or exported shell vars).

## Security Tips
- Rotate `LICENSE_ADMIN_TOKEN` regularly and keep it out of version control.
- Serve the API over HTTPS only.
- Use SiteGround IP restrictions or Web Application Firewall rules to narrow down who can call `/api/licenses/issue`.
- Consider moving the admin issuance flow to scheduled CLI scripts if you do not need remote issuance.

## Admin GUI
The `/admin/` directory hosts a lightweight operator console for issuing, validating, and simulating activations without touching curl.

- The GUI is static HTML/CSS/JS; it talks directly to `/api/licenses/*` using the values you supply for base URL and bearer token.
- The admin token is stored in `localStorage` only on the device where you load the page. Clear storage if the workstation is shared.
- Restrict access: place the `/admin/` path behind HTTP auth, an allow-listed IP, or SiteGround password protection. Anyone with browser access and the token can mint licenses.
- Forms cover issuance, validation, and activate/deactivate calls, and the console logs every request+response for quick debugging.

## Additional Notes
- Detailed architectural decisions live in `docs/architecture.md`.
- Future ideas: cronable cleanup of stale activations, audit logging, and a minimal web dashboard.
