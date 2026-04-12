-- ─────────────────────────────────────────────
--  Memeshift Invoice Manager — Database Schema
--  Run this once in phpMyAdmin or via MySQL CLI
-- ─────────────────────────────────────────────

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- Invoice sequence tracker (safe auto-increment per year)
CREATE TABLE IF NOT EXISTS `invoice_sequence` (
  `year`        INT NOT NULL,
  `last_number` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoices
CREATE TABLE IF NOT EXISTS `invoices` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `invoice_number`   VARCHAR(20)  NOT NULL UNIQUE,
  `client_name`      VARCHAR(255) NOT NULL DEFAULT '',
  `client_email`     VARCHAR(255) NOT NULL DEFAULT '',
  `client_address`   TEXT,
  `currency`         ENUM('EUR','USD') NOT NULL DEFAULT 'EUR',
  `issue_date`       DATE         NOT NULL,
  `due_date`         DATE         NOT NULL,
  `status`           ENUM('draft','sent','paid','overdue') NOT NULL DEFAULT 'draft',
  `paid_date`        DATE         NULL DEFAULT NULL,
  `notes`            TEXT,
  `subtotal`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total`            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoice line items
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `invoice_id`  INT UNSIGNED NOT NULL,
  `description` TEXT         NOT NULL,
  `quantity`    DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `rate`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `amount`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `sort_order`  INT          NOT NULL DEFAULT 0,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
