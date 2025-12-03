<?php

declare(strict_types=1);

use LicenseServer\LicenseService;
use RuntimeException;
use Throwable;

[$config, $database, $service] = require __DIR__ . '/../src/bootstrap.php';

$allowedOrigins = $config['security']['allowed_origins'] ?? '*';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . $allowedOrigins);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '', '/');
$segments = $path === '' ? [] : explode('/', $path);

if (($segments[0] ?? '') !== 'api' || ($segments[1] ?? '') !== 'licenses') {
    respond(404, ['error' => 'Route not found.']);
}

$action = $segments[2] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$allowed = ['issue', 'activate', 'validate', 'deactivate'];
if (!in_array($action, $allowed, true)) {
    respond(404, ['error' => 'Unknown license route.']);
}

if ($method !== 'POST') {
    respond(405, ['error' => 'Only POST is supported.']);
}

$body = file_get_contents('php://input');
$payload = json_decode($body ?: '[]', true);
if (!is_array($payload)) {
    respond(400, ['error' => 'Invalid JSON body.']);
}

try {
    $result = handleAction($service, $action, $payload, $config);
} catch (RuntimeException $exception) {
    respond(400, ['error' => $exception->getMessage()]);
} catch (Throwable $exception) {
    respond(500, ['error' => 'Server error: ' . $exception->getMessage()]);
}

$status = $action === 'issue' ? 201 : 200;
respond($status, ['success' => true, 'data' => $result]);

function handleAction(LicenseService $service, string $action, array $payload, array $config): array
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    return match ($action) {
        'issue' => handleIssue($service, $payload, $config),
        'activate' => $service->activate($payload, $ip),
        'validate' => $service->validate($payload),
        'deactivate' => $service->deactivate($payload),
        default => throw new RuntimeException('Unsupported route.'),
    };
}

function handleIssue(LicenseService $service, array $payload, array $config): array
{
    $token = getBearerToken();
    $expected = $config['api']['admin_token'] ?? '';
    if ($expected === '' || $token !== $expected) {
        throw new RuntimeException('Unauthorized.');
    }

    return $service->issueLicense($payload);
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

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    exit;
}
