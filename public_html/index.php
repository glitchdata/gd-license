<?php

declare(strict_types=1);

use LicenseServer\LicenseService;
use LicenseServer\UserService;
use RuntimeException;
use Throwable;

$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '', '/');
$segments = $path === '' ? [] : explode('/', $path);

if (($segments[0] ?? '') !== 'api') {
    servePortal();
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
        if ($method !== 'POST') {
            respond(405, ['error' => 'Only POST is supported for licenses.']);
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

    if (!in_array($action, ['issue', 'activate', 'deactivate', 'validate'], true)) {
        throw new RuntimeException('Unknown license route.');
    }

    if ($action !== 'validate') {
        $userId = requireAuthenticatedUser(true);
        requireAdmin($userId);
    }

    return match ($action) {
        'issue' => handleIssue($service, $payload, $config),
        'activate' => $service->activate($payload, $ip),
        'validate' => $service->validate($payload),
        'deactivate' => $service->deactivate($payload),
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

function servePortal(): void
{
    $portal = __DIR__ . '/portal.php';
    if (!is_file($portal)) {
        http_response_code(200);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'GD License Server is online.';
        exit;
    }

    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    readfile($portal);
    exit;
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
