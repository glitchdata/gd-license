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
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GD License Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/main.css">
</head>
<body>
<div class="bg"></div>
<main class="wrap">
    <header class="masthead portal-hero" id="portalHero">
        <div class="masthead-copy">
            <p class="eyebrow">Glitchdata · Licensing</p>
            <h1>Welcome to the License Portal.</h1>
            <p class="lede">One landing zone for admins, operators, and customers to manage software entitlements, review activity, and grab API-friendly snippets.</p>
            <div class="cta-row">
                <a class="cta" href="#adminPanel">Admin Login</a>
                <a class="cta ghost" href="/user/">Customer Login</a>
            </div>
            <ul class="callouts">
                <li>Centralized entry point for internal ops, customer success, and engineering.</li>
                <li>Sessions use SameSite=Strict cookies, so keep this page on a secure network.</li>
                <li>Customers still authenticate through <a href="/user/">/user/</a> with their own credentials.</li>
                <li>Quick-start API snippets below auto-update with your preferred base URL.</li>
            </ul>
        </div>
        <div class="panel session-panel" id="adminPanel">
            <div class="session-header">
                <div>
                    <p class="eyebrow">Admin Session</p>
                    <h2 id="sessionState"></h2>
                    <p class="muted" id="sessionSummary">Use your Glitchdata admin credentials to unlock the control room.</p>
                </div>
                <div class="session-actions hidden" id="sessionActions">
                    <button type="button" class="ghost" id="sessionRefresh">Refresh</button>
                </div>
            </div>

            <form id="adminLoginForm" class="stack">
                <label>Email
                    <input type="email" name="email" placeholder="you@glitchdata.com" autocomplete="email" required>
                </label>
                <label>Password
                    <input type="password" name="password" placeholder="********" autocomplete="current-password" required>
                </label>
                <button type="submit">Sign In</button>
                <p class="muted form-note">Your session stays scoped to this domain until you refresh or sign out.</p>
            </form>

            <div class="session-meta">
                <label>API Base URL
                    <input type="url" id="baseUrl" value="/api/licenses" autocomplete="off">
                    <small>E.g. <code>https://license.glitchdata.com/api/licenses</code></small>
                </label>
                <label>Favorite Product Code
                    <input type="text" id="defaultProduct" placeholder="APP_PRO" autocomplete="off">
                    <small>We use this to hydrate the quickstart snippets.</small>
                </label>
            </div>
        </div>
    </header>

    <section class="portal-grid" aria-label="Primary destinations">
        <article class="card portal-card">
            <span class="badge badge-gold">Admin</span>
            <h3>Operations Control</h3>
            <p>Sign in to issue, suspend, and audit licenses with full CRUD access.</p>
            <ul class="portal-list">
                <li>Real-time activation counts & status toggles.</li>
                <li>Notes for customer handoffs.</li>
                <li>Human-friendly keys plus API parity.</li>
            </ul>
            <button type="button" data-link="#adminPanel">Jump to Login</button>
        </article>
        <article class="card portal-card">
            <span class="badge badge-cyan">Customer Success</span>
            <h3>Customer Workspace</h3>
            <p>Point users to the self-service portal to activate, download, and manage devices.</p>
            <ul class="portal-list">
                <li>Customer ready UI mapped to their licenses.</li>
                <li>Same identity store, scoped privileges.</li>
                <li>Easy handoff from support tickets.</li>
            </ul>
            <a class="button-link ghost" href="/user/">Open Customer Portal</a>
        </article>
        <article class="card portal-card">
            <span class="badge badge-violet">Engineering</span>
            <h3>API & Integrations</h3>
            <p>Use the curated snippets below to script license lifecycles or wire up CI workflows.</p>
            <ul class="portal-list">
                <li>JWT-free admin endpoints (session cookie).</li>
                <li>Activation validation for clients.</li>
                <li>Simple JSON responses for monitoring.</li>
            </ul>
            <button type="button" class="ghost" data-link="#apiQuickstart">View Snippets</button>
        </article>
    </section>

    <section class="card api-quickstart" id="apiQuickstart">
        <div class="card-head">
            <div>
                <p class="eyebrow">API Quickstart</p>
                <h3>Hit the endpoints in minutes.</h3>
                <p class="muted">Snippets update with your base URL and favorite product code.</p>
            </div>
        </div>
        <div class="doc-grid">
            <article class="doc-card" data-snippet="issue">
                <header>
                    <span class="pill issue"></span>
                    <strong>Issue License</strong>
                </header>
                <p class="muted">Admin-only endpoint for generating or reissuing keys.</p>
                <pre><code></code></pre>
                <button type="button" class="copy" data-copy="issue">Copy snippet</button>
            </article>
            <article class="doc-card" data-snippet="activate">
                <header>
                    <span class="pill activate"></span>
                    <strong>Activate Instance</strong>
                </header>
                <p class="muted">Call from installers or agents to reserve an activation slot.</p>
                <pre><code></code></pre>
                <button type="button" class="copy" data-copy="activate">Copy snippet</button>
            </article>
            <article class="doc-card" data-snippet="validate">
                <header>
                    <span class="pill validate"></span>
                    <strong>Validate License</strong>
                </header>
                <p class="muted">Ping periodically from the product to confirm status.</p>
                <pre><code></code></pre>
                <button type="button" class="copy" data-copy="validate">Copy snippet</button>
            </article>
        </div>
    </section>

    <section class="card support" id="support">
        <div class="card-head">
            <h3>Need a human?</h3>
            <p>Ops and support share this data set. Reach out when you need deeper changes.</p>
        </div>
        <div class="support-grid">
            <div class="support-card">
                <p class="eyebrow">Operational requests</p>
                <h4>ops@glitchdata.com</h4>
                <p class="muted">Batch imports, large revocations, custom reporting.</p>
            </div>
            <div class="support-card">
                <p class="eyebrow">Customer escalations</p>
                <h4>support@glitchdata.com</h4>
                <p class="muted">Specific license troubleshooting or instance resets.</p>
            </div>
        </div>
    </section>
</main>

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
