CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(64) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    max_activations INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS licenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    license_key VARCHAR(128) NOT NULL UNIQUE,
    status ENUM('active','suspended','revoked') NOT NULL DEFAULT 'active',
    max_activations INT UNSIGNED DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_license_product FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS license_activations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_id BIGINT UNSIGNED NOT NULL,
    instance_id VARCHAR(128) NOT NULL,
    domain VARCHAR(255) DEFAULT NULL,
    ip_address VARCHAR(64) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    activated_at DATETIME NOT NULL,
    last_validated_at DATETIME NOT NULL,
    CONSTRAINT fk_activation_license FOREIGN KEY (license_id) REFERENCES licenses(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uniq_license_instance (license_id, instance_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    full_name VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    last_login_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_licenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    license_id BIGINT UNSIGNED NOT NULL,
    assigned_at DATETIME NOT NULL,
    CONSTRAINT fk_user_license_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_user_license_license FOREIGN KEY (license_id) REFERENCES licenses(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uniq_user_license (user_id, license_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional starter product row
INSERT INTO products (code, name, max_activations, created_at, updated_at)
VALUES ('APP_PRO', 'Example Product', 3, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

-- Optional starter user with password `Passw0rd!`
INSERT INTO users (email, full_name, password_hash, created_at, updated_at)
VALUES ('demo@glitchdata.com', 'Demo User', '$2y$12$.vR9eJNcT.IvO.4/rREXFekCxpbaF/nBrDdsFHF7kHMV1RTjEOhpa', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

-- Assign sample license to demo user if both exist
INSERT INTO user_licenses (user_id, license_id, assigned_at)
SELECT u.id, l.id, NOW()
FROM users u
JOIN licenses l ON l.license_key = (SELECT license_key FROM licenses ORDER BY id ASC LIMIT 1)
WHERE u.email = 'demo@glitchdata.com'
ON DUPLICATE KEY UPDATE assigned_at = VALUES(assigned_at);
