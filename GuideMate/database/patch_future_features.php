<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Core\Database;

$statements = [
    <<<'SQL'
CREATE TABLE IF NOT EXISTS `password_resets` (
    `email` VARCHAR(190) NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
    <<<'SQL'
CREATE TABLE IF NOT EXISTS `guide_availability` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `listing_id` INT UNSIGNED NOT NULL,
    `blocked_date` DATE NOT NULL,
    `note` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `guide_availability_unique` (`listing_id`, `blocked_date`),
    CONSTRAINT `guide_availability_listing_fk` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
    <<<'SQL'
CREATE TABLE IF NOT EXISTS `promo_codes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(40) NOT NULL,
    `discount_percent` DECIMAL(5,2) DEFAULT NULL,
    `discount_amount` DECIMAL(10,2) DEFAULT NULL,
    `valid_from` DATE DEFAULT NULL,
    `valid_until` DATE DEFAULT NULL,
    `max_uses` INT UNSIGNED DEFAULT NULL,
    `uses_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `promo_codes_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
    <<<'SQL'
CREATE TABLE IF NOT EXISTS `review_images` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `review_id` INT UNSIGNED NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `review_images_review_fk` (`review_id`),
    CONSTRAINT `review_images_review_fk` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
    <<<'SQL'
CREATE TABLE IF NOT EXISTS `dispute_files` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dispute_id` INT UNSIGNED NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `label` VARCHAR(190) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `dispute_files_dispute_fk` (`dispute_id`),
    CONSTRAINT `dispute_files_dispute_fk` FOREIGN KEY (`dispute_id`) REFERENCES `disputes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
    <<<'SQL'
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(80) NOT NULL,
    `entity_type` VARCHAR(40) NOT NULL,
    `entity_id` INT UNSIGNED DEFAULT NULL,
    `meta` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `audit_logs_admin_fk` (`admin_id`),
    CONSTRAINT `audit_logs_admin_fk` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
    'ALTER TABLE bookings ADD COLUMN verify_token VARCHAR(64) DEFAULT NULL',
    'ALTER TABLE bookings ADD COLUMN verified_at TIMESTAMP NULL DEFAULT NULL',
    'ALTER TABLE bookings ADD COLUMN promo_code_id INT UNSIGNED DEFAULT NULL',
    'ALTER TABLE bookings ADD COLUMN discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00',
    'ALTER TABLE bookings ADD COLUMN reminder_sent TINYINT(1) NOT NULL DEFAULT 0',
    'ALTER TABLE listings ADD COLUMN sale_price DECIMAL(10,2) DEFAULT NULL',
    'ALTER TABLE listings ADD COLUMN sale_ends_at DATE DEFAULT NULL',
    'ALTER TABLE users ADD COLUMN admin_totp_secret VARCHAR(64) DEFAULT NULL',
    'ALTER TABLE users ADD COLUMN admin_totp_enabled TINYINT(1) NOT NULL DEFAULT 0',
];

foreach ($statements as $sql) {
    try {
        Database::run($sql);
        echo "OK\n";
    } catch (Throwable $e) {
        echo 'SKIP: ' . $e->getMessage() . "\n";
    }
}

echo "Future features schema patch complete.\n";
