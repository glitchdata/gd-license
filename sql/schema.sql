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

-- Optional starter product row
INSERT INTO products (code, name, max_activations, created_at, updated_at)
VALUES ('APP_PRO', 'Example Product', 3, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
