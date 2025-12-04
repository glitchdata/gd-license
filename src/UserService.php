<?php

declare(strict_types=1);

namespace LicenseServer;

use DateTimeImmutable;
use PDO;
use RuntimeException;

class UserService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function authenticate(string $email, string $password): array
    {
        $user = $this->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new RuntimeException('Invalid email or password.');
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $this->updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        $this->recordLogin((int) $user['id']);
        return $this->formatUser($user);
    }

    public function profile(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        if (!$user) {
            throw new RuntimeException('User not found.');
        }
        return $this->formatUser($user);
    }

    public function licenses(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.license_key, l.status, l.expires_at, l.max_activations, ul.assigned_at,
                    p.code AS product_code, p.name AS product_name,
                    COALESCE(a.total, 0) AS activations_in_use
             FROM user_licenses ul
             JOIN licenses l ON l.id = ul.license_id
             JOIN products p ON p.id = l.product_id
             LEFT JOIN (
                 SELECT license_id, COUNT(*) AS total
                 FROM license_activations
                 GROUP BY license_id
             ) a ON a.license_id = l.id
             WHERE ul.user_id = :user_id
             ORDER BY ul.assigned_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll();

        return array_map(function (array $row): array {
            $max = $row['max_activations'] !== null ? (int) $row['max_activations'] : null;
            return [
                'license_key' => $row['license_key'],
                'status' => $row['status'],
                'expires_at' => $row['expires_at'],
                'assigned_at' => $row['assigned_at'],
                'product' => [
                    'code' => $row['product_code'],
                    'name' => $row['product_name'],
                ],
                'max_activations' => $max,
                'activations_in_use' => (int) $row['activations_in_use'],
                'activations_remaining' => $max !== null ? max(0, $max - (int) $row['activations_in_use']) : null,
            ];
        }, $rows);
    }

    private function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    private function formatUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'created_at' => $user['created_at'],
            'updated_at' => $user['updated_at'],
            'last_login_at' => $user['last_login_at'],
        ];
    }

    private function recordLogin(int $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET last_login_at = :time, updated_at = :time WHERE id = :id');
        $stmt->execute([
            'time' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'id' => $userId,
        ]);
    }

    private function updatePasswordHash(int $userId, string $hash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute([
            'hash' => $hash,
            'id' => $userId,
        ]);
    }
}
