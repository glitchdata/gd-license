<?php

declare(strict_types=1);

use LicenseServer\LicenseService;
use LicenseServer\UserService;
use RuntimeException;
use Throwable;

$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '', '/');
$segments = $path === '' ? [] : explode('/', $path);

if (($segments[0] ?? '') !== 'api') {
    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    echo <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GD License Server · Control Room</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/main.css">
</head>
<body>
<div class="bg"></div>
<main class="wrap">
    <header class="masthead">
        <div>
            <p class="eyebrow">Glitchdata · License Ops</p>
            <h1>Log into the control room.</h1>
            <p class="lede">Sign in with your admin credentials right on the front page, then issue licenses, simulate activations, and inspect the JSON API responses without leaving this view.</p>
            <div class="cta-row">
                <a class="cta" href="#authPanel">Go to Admin Login</a>
                <a class="cta ghost" href="/user/">Customer Portal</a>
            </div>
            <ul class="callouts">
                <li>Admins sign in with email + password using the panel on the right.</li>
                <li>Issue / activate / deactivate endpoints require an authenticated admin session.</li>
                <li>All traffic targets <code>/api/licenses/*</code> over HTTPS.</li>
                <li>Keep this console behind HTTP auth or an allow-listed VPN.</li>
                <li>Customers sign in at <a href="/user/">/user/</a> with their email + password.</li>
            </ul>
        </div>
        <div class="panel session-panel" id="authPanel">
            <div class="session-header">
                <div>
                    <p class="eyebrow">Admin Session</p>
                    <h2 id="sessionState">Not signed in</h2>
                    <p class="muted">Issue / activate / deactivate endpoints require a logged-in admin.</p>
                </div>
                <div class="session-actions hidden" id="sessionActions">
                    <button type="button" class="ghost" id="sessionRefresh">Refresh</button>
                    <button type="button" id="sessionLogout">Logout</button>
                </div>
            </div>

            <form id="adminLoginForm" class="stack">
                <label>Email
                    <input type="email" name="email" placeholder="you@glitchdata.com" autocomplete="email" required>
                </label>
                <label>Password
                    <input type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
                </label>
                <button type="submit">Sign In</button>
            </form>

            <label>API Base URL
                <input type="url" id="baseUrl" value="/api/licenses" autocomplete="off">
                <small>E.g. <code>https://license.glitchdata.com/api/licenses</code></small>
            </label>
            <label>Preferred Product Code
                <input type="text" id="defaultProduct" placeholder="APP_PRO" autocomplete="off">
                <small>Pre-fills each form to speed up testing.</small>
            </label>
        </div>
    </header>

curl -X POST "$BASE/issue" \
    <section class="dashboard hidden" id="licenseDashboard">
        <div class="dashboard-head">
            <div>
                <h3>Licenses</h3>
                <p>Search, filter, and edit your fleet of keys the moment you log in.</p>
            </div>
            <div class="filters">
                <input type="search" id="licenseSearch" placeholder="Search by license, product, or customer">
                <select id="licenseStatus">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                    <option value="revoked">Revoked</option>
                </select>
                <button type="button" class="ghost" id="licenseRefreshButton">Refresh</button>
            </div>
        </div>
        <div class="dashboard-body">
            <div class="list-panel">
                <div class="table-wrapper">
                    <table class="license-table">
                        <thead>
                            <tr>
                                <th>License</th>
                                <th>Product</th>
                                <th>Status</th>
                                <th>Expires</th>
                                <th>Usage</th>
                            </tr>
                        </thead>
                        <tbody id="licenseTableBody"></tbody>
                    </table>
                    <div class="empty-state hidden" id="licenseEmpty">
                        <p>No licenses match your filters yet.</p>
                    </div>
                </div>
            </div>
            <div class="detail-panel">
                <article class="card compact">
                    <div class="card-head">
                        <h3>Issue License</h3>
                        <p>Generate a brand-new key or reuse an existing value.</p>
                    </div>
                    <form id="issueForm" class="stack">
                        <label>Product Code
                            <input name="product_code" required>
                        </label>
                        <label>License Key (optional)
                            <input name="license_key" placeholder="Auto-generate when blank">
                        </label>
                        <label>Expires At
                            <input name="expires_at" placeholder="2025-12-31 or +1 year">
                        </label>
                        <label>Max Activations
                            <input name="max_activations" type="number" min="1" placeholder="Default to product allowance">
                        </label>
                        <label>Notes
                            <textarea name="notes" rows="2" placeholder="Internal note"></textarea>
                        </label>
                        <div class="button-row">
                            <button type="submit">Issue License</button>
                        </div>
                    </form>
                </article>

                <article class="card compact">
                    <div class="card-head">
                        <h3>License Details</h3>
                        <p>Pick a license from the table to edit status, limits, or notes.</p>
                    </div>
                    <div id="licenseDetailEmpty" class="muted">Select a license to view its details.</div>
                    <form id="licenseDetailForm" class="stack hidden">
                        <label>License Key
                            <input name="license_key" readonly>
                        </label>
                        <div class="detail-meta">
                            <p id="detailProduct">—</p>
                            <p id="detailUsage">0 / 0 activations</p>
                        </div>
                        <label>Status
                            <select name="status">
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="revoked">Revoked</option>
                            </select>
                        </label>
                        <label>Expires At
                            <input name="expires_at" placeholder="2025-12-31 or blank for none">
                        </label>
                        <label>Max Activations
                            <input name="max_activations" type="number" min="1" placeholder="Inherit from product">
                        </label>
                        <label>Notes
                            <textarea name="notes" rows="3" placeholder="Internal note"></textarea>
                        </label>
                        <div class="button-row split">
                            <button type="submit">Save Changes</button>
                            <button type="button" class="ghost danger" id="deleteLicenseButton">Delete</button>
                        </div>
                    </form>
                </article>
            </div>
        </div>
    </section>

    <section class="card log">
        <div class="card-head">
            <h3>Live Console</h3>
            <p>Every request and response captured for auditing.</p>
        </div>
        <div id="log"></div>
    </section>
</main>

<template id="logEntry">
    <div class="entry">
        <div class="entry-head">
            <span class="pill"></span>
            <strong class="label"></strong>
            <span class="time"></span>
        </div>
        <div class="entry-body"></div>
    </div>
</template>

<script src="/assets/main.js" defer></script>
</body>
</html>
HTML;
    exit;
}

[$config, $database, $licenseService, $userService] = require __DIR__ . '/../src/bootstrap.php';

$allowedOrigins = $config['security']['allowed_origins'] ?? '*';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . $allowedOrigins);
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$resource = $segments[1] ?? '';
$action = $segments[2] ?? '';

try {
    if ($resource === 'licenses') {
        if ($method === 'GET') {
            ensureSession();
            $userId = requireAuthenticatedUser(true);
            requireAdmin($userId);
            $filters = [
                'search' => $_GET['search'] ?? null,
                'status' => $_GET['status'] ?? null,
                'limit' => $_GET['limit'] ?? null,
                'offset' => $_GET['offset'] ?? null,
            ];
            $result = $licenseService->listLicenses($filters);
            respond(200, ['success' => true, 'data' => $result]);
        }

        if ($method !== 'POST') {
            respond(405, ['error' => 'Unsupported method for licenses.']);
        }
        $payload = readJsonPayload();
        $result = handleLicenseAction($licenseService, $action, $payload, $config);
        $status = $action === 'issue' ? 201 : 200;
        respond($status, ['success' => true, 'data' => $result]);
    }

    if ($resource === 'users') {
        ensureSession();
        $result = handleUserAction($userService, $action, $method);
        respond(200, ['success' => true, 'data' => $result]);
    }

    respond(404, ['error' => 'Route not found.']);
} catch (RuntimeException $exception) {
    respond(400, ['error' => $exception->getMessage()]);
} catch (Throwable $exception) {
    respond(500, ['error' => 'Server error: ' . $exception->getMessage()]);
}

function handleLicenseAction(LicenseService $service, string $action, array $payload, array $config): array
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    if (!in_array($action, ['issue', 'activate', 'deactivate', 'validate', 'update', 'delete'], true)) {
        throw new RuntimeException('Unknown license route.');
    }

    if ($action !== 'validate') {
        ensureSession();
        $userId = requireAuthenticatedUser(true);
        requireAdmin($userId);
    }

    return match ($action) {
        'issue' => handleIssue($service, $payload, $config),
        'activate' => $service->activate($payload, $ip),
        'validate' => $service->validate($payload),
        'deactivate' => $service->deactivate($payload),
        'update' => $service->updateLicense($payload),
        'delete' => $service->deleteLicense($payload),
        default => throw new RuntimeException('Unsupported route.'),
    };
}

function handleUserAction(UserService $service, string $action, string $method): array
{
    return match ($action) {
        'login' => handleUserLogin($service, $method),
        'logout' => handleUserLogout($method),
        'me' => handleUserProfile($service),
        'licenses' => handleUserLicenses($service),
        default => throw new RuntimeException('Unknown user route.'),
    };
}

function handleIssue(LicenseService $service, array $payload, array $config): array
{
    return $service->issueLicense($payload);
}

function handleUserLogin(UserService $service, string $method): array
{
    if ($method !== 'POST') {
        throw new RuntimeException('Unsupported method.');
    }

    $payload = readJsonPayload();
    $email = trim((string) ($payload['email'] ?? ''));
    $password = (string) ($payload['password'] ?? '');

    if ($email === '' || $password === '') {
        throw new RuntimeException('Email and password are required.');
    }

    $user = $service->authenticate($email, $password);

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];

    return [
        'profile' => $service->profile($user['id']),
        'licenses' => $service->licenses($user['id']),
    ];
}

function handleUserLogout(string $method): array
{
    if ($method !== 'POST') {
        throw new RuntimeException('Unsupported method.');
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    return ['logged_out' => true];
}

function handleUserProfile(UserService $service): array
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        throw new RuntimeException('Unsupported method.');
    }
    $userId = requireAuthenticatedUser();
    return [
        'profile' => $service->profile($userId),
        'licenses' => $service->licenses($userId),
    ];
}

function handleUserLicenses(UserService $service): array
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        throw new RuntimeException('Unsupported method.');
    }
    $userId = requireAuthenticatedUser();
    return [
        'licenses' => $service->licenses($userId),
    ];
}

function getBearerToken(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? null;
    if (!$header) {
        return null;
    }

    if (stripos($header, 'Bearer ') === 0) {
        return substr($header, 7);
    }

    return null;
}

function ensureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict',
        ]);
    }
}

function requireAuthenticatedUser(bool $throw = false): int
{
    if (!isset($_SESSION['user_id'])) {
        if ($throw) {
            throw new RuntimeException('Authentication required.');
        }
        respond(401, ['error' => 'Authentication required.']);
    }

    return (int) $_SESSION['user_id'];
}

function requireAdmin(int $userId): void
{
    static $cache;
    if ($cache === null) {
        global $userService;
        $profile = $userService->profile($userId);
        $cache = (bool) ($profile['is_admin'] ?? false);
    }

    if (!$cache) {
        throw new RuntimeException('Admin privileges required.');
    }
}

function readJsonPayload(): array
{
    $body = file_get_contents('php://input');
    $payload = json_decode($body ?: '[]', true);
    if (!is_array($payload)) {
        throw new RuntimeException('Invalid JSON body.');
    }
    return $payload;
}

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    exit;
}
