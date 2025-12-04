<?php

declare(strict_types=1);

use LicenseServer\Database;
use LicenseServer\LicenseService;
use LicenseServer\UserService;

if (!defined('LICENSE_SERVER_AUTOLOADED')) {
    define('LICENSE_SERVER_AUTOLOADED', true);
    spl_autoload_register(static function (string $class): void {
        $prefix = 'LicenseServer\\';
        if (strpos($class, $prefix) !== 0) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $relativePath = str_replace('\\', '/', $relative);
        $file = __DIR__ . '/' . $relativePath . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });
}

$configPath = __DIR__ . '/../config/config.php';
if (!file_exists($configPath)) {
    throw new RuntimeException('Missing config/config.php.');
}

$config = require $configPath;

date_default_timezone_set($config['timezone'] ?? 'UTC');

$database = new Database($config['db']);
$licenseService = new LicenseService($database->pdo(), $config);
$userService = new UserService($database->pdo());

return [$config, $database, $licenseService, $userService];
