<?php

declare(strict_types=1);

namespace LicenseServer;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use RuntimeException;

class LicenseService
{
    private DateTimeZone $timezone;

    public function __construct(private PDO $pdo, private array $config)
    {
        $this->timezone = new DateTimeZone($config['timezone'] ?? 'UTC');
    }

    public function issueLicense(array $data): array
    {
        $productCode = $this->requireString($data, 'product_code');
        $product = $this->findProduct($productCode);
        if (!$product) {
            throw new RuntimeException('Unknown product_code.');
        }

        $licenseKey = strtoupper($data['license_key'] ?? $this->generateLicenseKey());
        $status = $this->normalizeStatus($data['status'] ?? 'active');
        $maxActivations = isset($data['max_activations']) ? max(1, (int) $data['max_activations']) : null;
        $notes = trim((string) ($data['notes'] ?? '')) ?: null;

        $expiresAt = null;
        if (!empty($data['expires_at'])) {
            $expires = new DateTimeImmutable((string) $data['expires_at'], $this->timezone);
            $expiresAt = $expires->format('Y-m-d H:i:s');
        }

        $now = $this->now();

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO licenses (product_id, license_key, status, max_activations, expires_at, notes, created_at, updated_at)
                 VALUES (:product_id, :license_key, :status, :max_activations, :expires_at, :notes, :created_at, :updated_at)'
            );
            $stmt->execute([
                'product_id' => $product['id'],
                'license_key' => $licenseKey,
                'status' => $status,
                'max_activations' => $maxActivations,
                'expires_at' => $expiresAt,
                'notes' => $notes,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw new RuntimeException('Failed to issue license: ' . $exception->getMessage(), 0, $exception);
        }

        $license = $this->findLicense($licenseKey);
        if (!$license) {
            throw new RuntimeException('New license could not be read back.');
        }

        $limit = $this->determineActivationLimit($license);
        return $this->formatLicense($license, $limit, 0);
    }

    public function activate(array $data, ?string $ipAddress = null): array
    {
        $license = $this->lookupLicense($data);
        $instanceId = $this->requireString($data, 'instance_id');
        $domain = $this->optionalString($data, 'domain');
        $userAgent = $this->optionalString($data, 'user_agent');

        $limit = $this->determineActivationLimit($license);
        $existing = $this->findActivation($license['id'], $instanceId);
        $now = $this->now();

        if ($existing) {
            $stmt = $this->pdo->prepare(
                'UPDATE license_activations SET domain = :domain, ip_address = :ip, user_agent = :agent, last_validated_at = :updated
                 WHERE id = :id'
            );
            $stmt->execute([
                'domain' => $domain,
                'ip' => $ipAddress,
                'agent' => $userAgent,
                'updated' => $now,
                'id' => $existing['id'],
            ]);
        } else {
            $count = $this->countActivations($license['id']);
            if ($count >= $limit) {
                throw new RuntimeException('Activation limit reached.');
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO license_activations (license_id, instance_id, domain, ip_address, user_agent, activated_at, last_validated_at)
                 VALUES (:license_id, :instance_id, :domain, :ip, :agent, :activated_at, :last_validated_at)'
            );
            $stmt->execute([
                'license_id' => $license['id'],
                'instance_id' => $instanceId,
                'domain' => $domain,
                'ip' => $ipAddress,
                'agent' => $userAgent,
                'activated_at' => $now,
                'last_validated_at' => $now,
            ]);
        }

        $count = $this->countActivations($license['id']);
        return $this->formatLicense($license, $limit, $count);
    }

    public function validate(array $data): array
    {
        $license = $this->lookupLicense($data);
        $instanceId = $this->optionalString($data, 'instance_id');

        if ($instanceId) {
            $activation = $this->findActivation($license['id'], $instanceId);
            if ($activation) {
                $stmt = $this->pdo->prepare('UPDATE license_activations SET last_validated_at = :updated WHERE id = :id');
                $stmt->execute([
                    'updated' => $this->now(),
                    'id' => $activation['id'],
                ]);
            }
        }

        $limit = $this->determineActivationLimit($license);
        $count = $this->countActivations($license['id']);
        return $this->formatLicense($license, $limit, $count);
    }

    public function deactivate(array $data): array
    {
        $license = $this->lookupLicense($data);
        $instanceId = $this->requireString($data, 'instance_id');

        $stmt = $this->pdo->prepare('DELETE FROM license_activations WHERE license_id = :license_id AND instance_id = :instance_id');
        $stmt->execute([
            'license_id' => $license['id'],
            'instance_id' => $instanceId,
        ]);

        $limit = $this->determineActivationLimit($license);
        $count = $this->countActivations($license['id']);

        return [
            'deactivated' => $stmt->rowCount() > 0,
            'license' => $this->formatLicense($license, $limit, $count),
        ];
    }

    public function listLicenses(array $filters = []): array
    {
        $limit = max(1, min(200, (int) ($filters['limit'] ?? 50)));
        $offset = max(0, (int) ($filters['offset'] ?? 0));
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        $conditions = [];
        $params = [];

        if ($search !== '') {
            $conditions[] = '(l.license_key LIKE :search OR p.code LIKE :search OR p.name LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($status !== '') {
            $conditions[] = 'l.status = :status';
            $params['status'] = $this->normalizeStatus($status);
        }

        $sql = 'SELECT l.*, p.code AS product_code, p.name AS product_name, p.max_activations AS product_max_activations,
                       COUNT(a.id) AS activations_in_use
                FROM licenses l
                JOIN products p ON p.id = l.product_id
                LEFT JOIN license_activations a ON a.license_id = l.id';

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' GROUP BY l.id
                  ORDER BY l.created_at DESC
                  LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll() ?: [];
        $licenses = array_map(function (array $row): array {
            $limit = $this->determineActivationLimit($row);
            $count = (int) ($row['activations_in_use'] ?? 0);
            return $this->formatLicense($row, $limit, $count);
        }, $rows);

        return [
            'licenses' => $licenses,
            'count' => count($licenses),
        ];
    }

    public function updateLicense(array $data): array
    {
        $licenseKey = $this->requireString($data, 'license_key');
        $license = $this->findLicense($licenseKey);
        if (!$license) {
            throw new RuntimeException('License not found.');
        }

        $fields = [];

        if (array_key_exists('status', $data)) {
            $fields['status'] = $this->normalizeStatus((string) $data['status']);
        }

        if (array_key_exists('max_activations', $data)) {
            $value = trim((string) $data['max_activations']);
            $fields['max_activations'] = $value === '' ? null : max(1, (int) $value);
        }

        if (array_key_exists('expires_at', $data)) {
            $value = trim((string) $data['expires_at']);
            if ($value === '') {
                $fields['expires_at'] = null;
            } else {
                $expires = new DateTimeImmutable($value, $this->timezone);
                $fields['expires_at'] = $expires->format('Y-m-d H:i:s');
            }
        }

        if (array_key_exists('notes', $data)) {
            $note = trim((string) $data['notes']);
            $fields['notes'] = $note === '' ? null : $note;
        }

        if (!$fields) {
            $limit = $this->determineActivationLimit($license);
            $count = $this->countActivations($license['id']);
            return ['license' => $this->formatLicense($license, $limit, $count)];
        }

        $fields['updated_at'] = $this->now();

        $assignments = [];
        foreach ($fields as $column => $value) {
            $assignments[] = sprintf('%s = :%s', $column, $column);
        }

        $sql = sprintf('UPDATE licenses SET %s WHERE license_key = :license_key', implode(', ', $assignments));
        $stmt = $this->pdo->prepare($sql);
        foreach ($fields as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }
        $stmt->bindValue(':license_key', $licenseKey);
        $stmt->execute();

        $updated = $this->findLicense($licenseKey);
        if (!$updated) {
            throw new RuntimeException('Updated license could not be retrieved.');
        }

        $limit = $this->determineActivationLimit($updated);
        $count = $this->countActivations($updated['id']);

        return ['license' => $this->formatLicense($updated, $limit, $count)];
    }

    public function deleteLicense(array $data): array
    {
        $licenseKey = $this->requireString($data, 'license_key');
        $license = $this->findLicense($licenseKey);
        if (!$license) {
            throw new RuntimeException('License not found.');
        }

        $stmt = $this->pdo->prepare('DELETE FROM licenses WHERE id = :id');
        $stmt->execute(['id' => $license['id']]);

        return ['deleted' => $stmt->rowCount() > 0];
    }

    private function lookupLicense(array $data): array
    {
        $licenseKey = $this->requireString($data, 'license_key');
        $productCode = $this->requireString($data, 'product_code');

        $license = $this->findLicense($licenseKey);
        if (!$license) {
            throw new RuntimeException('License not found.');
        }

        if ($license['product_code'] !== $productCode) {
            throw new RuntimeException('License does not match product_code.');
        }

        if ($license['status'] !== 'active') {
            throw new RuntimeException('License is not active.');
        }

        if ($license['expires_at'] && $this->isExpired($license['expires_at'])) {
            throw new RuntimeException('License has expired.');
        }

        return $license;
    }

    private function findProduct(string $code): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE code = :code LIMIT 1');
        $stmt->execute(['code' => $code]);
        return $stmt->fetch() ?: null;
    }

    private function findLicense(string $licenseKey): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.*, p.code AS product_code, p.name AS product_name, p.max_activations AS product_max_activations
             FROM licenses l
             JOIN products p ON p.id = l.product_id
             WHERE l.license_key = :license_key
             LIMIT 1'
        );
        $stmt->execute(['license_key' => $licenseKey]);
        $record = $stmt->fetch();
        return $record ?: null;
    }

    private function determineActivationLimit(array $license): int
    {
        $licenseLimit = $license['max_activations'] !== null ? (int) $license['max_activations'] : null;
        $productLimit = $license['product_max_activations'] !== null ? (int) $license['product_max_activations'] : null;
        return $licenseLimit ?? $productLimit ?? 1;
    }

    private function findActivation(int $licenseId, string $instanceId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM license_activations WHERE license_id = :license_id AND instance_id = :instance_id LIMIT 1'
        );
        $stmt->execute([
            'license_id' => $licenseId,
            'instance_id' => $instanceId,
        ]);
        return $stmt->fetch() ?: null;
    }

    private function countActivations(int $licenseId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS total FROM license_activations WHERE license_id = :license_id');
        $stmt->execute(['license_id' => $licenseId]);
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0);
    }

    private function generateLicenseKey(int $segments = 4, int $segmentLength = 5): string
    {
        $parts = [];
        for ($i = 0; $i < $segments; $i++) {
            $parts[] = substr(strtoupper(bin2hex(random_bytes($segmentLength))), 0, $segmentLength);
        }
        return implode('-', $parts);
    }

    private function normalizeStatus(string $status): string
    {
        $allowed = ['active', 'suspended', 'revoked'];
        return in_array($status, $allowed, true) ? $status : 'active';
    }

    private function requireString(array $data, string $key): string
    {
        $value = trim((string) ($data[$key] ?? ''));
        if ($value === '') {
            throw new RuntimeException($key . ' is required.');
        }
        return $value;
    }

    private function optionalString(array $data, string $key): ?string
    {
        if (!isset($data[$key])) {
            return null;
        }
        $value = trim((string) $data[$key]);
        return $value === '' ? null : $value;
    }

    private function isExpired(string $expiresAt): bool
    {
        $expiry = new DateTimeImmutable($expiresAt, $this->timezone);
        return $expiry < new DateTimeImmutable('now', $this->timezone);
    }

    private function now(): string
    {
        return (new DateTimeImmutable('now', $this->timezone))->format('Y-m-d H:i:s');
    }

    private function formatLicense(array $license, int $limit, int $count): array
    {
        return [
            'id' => (int) $license['id'],
            'license_key' => $license['license_key'],
            'product' => [
                'code' => $license['product_code'],
                'name' => $license['product_name'],
            ],
            'status' => $license['status'],
            'expires_at' => $license['expires_at'],
            'max_activations' => $limit,
            'activations_in_use' => $count,
            'activations_remaining' => max(0, $limit - $count),
            'notes' => $license['notes'] ?? null,
            'created_at' => $license['created_at'] ?? null,
            'updated_at' => $license['updated_at'] ?? null,
        ];
    }
}
