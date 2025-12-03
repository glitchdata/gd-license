<?php

declare(strict_types=1);

use Throwable;

[$config, $database, $service] = require __DIR__ . '/../src/bootstrap.php';

$options = getopt('', [
    'product:',
    'key::',
    'expires::',
    'activations::',
    'notes::',
    'status::',
]);

if (!$options || empty($options['product'])) {
    fwrite(STDERR, "Usage: php scripts/create_license.php --product=CODE [--key=ABC] [--expires='2025-12-31'] [--activations=3]\n");
    exit(1);
}

$data = [
    'product_code' => $options['product'],
];

if (!empty($options['key'])) {
    $data['license_key'] = $options['key'];
}

if (!empty($options['expires'])) {
    $data['expires_at'] = $options['expires'];
}

if (!empty($options['activations'])) {
    $data['max_activations'] = (int) $options['activations'];
}

if (!empty($options['notes'])) {
    $data['notes'] = $options['notes'];
}

if (!empty($options['status'])) {
    $data['status'] = $options['status'];
}

try {
    $license = $service->issueLicense($data);
    fwrite(STDOUT, 'License issued: ' . $license['license_key'] . PHP_EOL);
    fwrite(STDOUT, json_encode($license, JSON_PRETTY_PRINT) . PHP_EOL);
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
