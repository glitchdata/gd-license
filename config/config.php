<?php

declare(strict_types=1);

return [
    'timezone' => getenv('LICENSE_TZ') ?: 'UTC',
    'db' => [
        'host' => getenv('LICENSE_DB_HOST') ?: 'localhost',
        'port' => (int) (getenv('LICENSE_DB_PORT') ?: 3306),
        'name' => getenv('LICENSE_DB_NAME') ?: 'license_db',
        'user' => getenv('LICENSE_DB_USER') ?: 'license_user',
        'pass' => getenv('LICENSE_DB_PASS') ?: 'change-me',
        'charset' => 'utf8mb4',
    ],
    'api' => [
        'admin_token' => getenv('LICENSE_ADMIN_TOKEN') ?: 'replace-this-token',
    ],
    'security' => [
        'allowed_origins' => getenv('LICENSE_ALLOWED_ORIGINS') ?: '*',
    ],
];
