-- Smart Restock & Waitlist Manager Database Schema
-- Version: 1.0.0
-- Description: Database tables for Smart Restock & Waitlist Manager plugin

-- Core Tables (Free Version)

-- Waitlist table to store customer waitlist entries
CREATE TABLE IF NOT EXISTS `{prefix}srwm_waitlist` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `product_id` bigint(20) unsigned NOT NULL,
    `customer_email` varchar(255) NOT NULL,
    `customer_name` varchar(255) DEFAULT NULL,
    `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notified` tinyint(1) NOT NULL DEFAULT 0,
    `notification_date` datetime DEFAULT NULL,
    `status` enum('active','notified','removed') NOT NULL DEFAULT 'active',
    PRIMARY KEY (`id`),
    UNIQUE KEY `product_email_unique` (`product_id`, `customer_email`),
    KEY `product_id` (`product_id`),
    KEY `customer_email` (`customer_email`),
    KEY `date_added` (`date_added`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Suppliers table to store supplier information
CREATE TABLE IF NOT EXISTS `{prefix}srwm_suppliers` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `product_id` bigint(20) unsigned NOT NULL,
    `email` varchar(255) NOT NULL,
    `name` varchar(255) DEFAULT NULL,
    `phone` varchar(50) DEFAULT NULL,
    `threshold` int(11) DEFAULT NULL,
    `channels` text DEFAULT NULL COMMENT 'JSON array of notification channels',
    `auto_generate_po` tinyint(1) NOT NULL DEFAULT 0,
    `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `product_email_unique` (`product_id`, `email`),
    KEY `product_id` (`product_id`),
    KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restock logs table to track restock activities
CREATE TABLE IF NOT EXISTS `{prefix}srwm_restock_logs` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `product_id` bigint(20) unsigned NOT NULL,
    `quantity` int(11) NOT NULL,
    `method` enum('manual','supplier_link','csv_upload','api') NOT NULL DEFAULT 'manual',
    `supplier_email` varchar(255) DEFAULT NULL,
    `admin_user_id` bigint(20) unsigned DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `product_id` (`product_id`),
    KEY `method` (`method`),
    KEY `timestamp` (`timestamp`),
    KEY `supplier_email` (`supplier_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pro Tables (Only created when Pro license is active)

-- Restock tokens table for secure one-click restock links
CREATE TABLE IF NOT EXISTS `{prefix}srwm_restock_tokens` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `token` varchar(255) NOT NULL,
    `product_id` bigint(20) unsigned NOT NULL,
    `supplier_email` varchar(255) NOT NULL,
    `expires_at` datetime NOT NULL,
    `used` tinyint(1) NOT NULL DEFAULT 0,
    `used_at` datetime DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`),
    KEY `product_id` (`product_id`),
    KEY `supplier_email` (`supplier_email`),
    KEY `expires_at` (`expires_at`),
    KEY `used` (`used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CSV upload tokens table for secure bulk restock links
CREATE TABLE IF NOT EXISTS `{prefix}srwm_csv_tokens` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `token` varchar(255) NOT NULL,
    `supplier_email` varchar(255) NOT NULL,
    `expires_at` datetime NOT NULL,
    `used` tinyint(1) NOT NULL DEFAULT 0,
    `used_at` datetime DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`),
    KEY `supplier_email` (`supplier_email`),
    KEY `expires_at` (`expires_at`),
    KEY `used` (`used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Purchase orders table for Pro purchase order generation
CREATE TABLE IF NOT EXISTS `{prefix}srwm_purchase_orders` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `po_number` varchar(50) NOT NULL,
    `product_id` bigint(20) unsigned NOT NULL,
    `supplier_email` varchar(255) NOT NULL,
    `quantity` int(11) NOT NULL,
    `unit_price` decimal(10,2) DEFAULT NULL,
    `total_amount` decimal(10,2) DEFAULT NULL,
    `status` enum('draft','sent','confirmed','received','cancelled') NOT NULL DEFAULT 'draft',
    `sent_at` datetime DEFAULT NULL,
    `confirmed_at` datetime DEFAULT NULL,
    `received_at` datetime DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `po_number` (`po_number`),
    KEY `product_id` (`product_id`),
    KEY `supplier_email` (`supplier_email`),
    KEY `status` (`status`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for better performance

-- Composite indexes for common queries
CREATE INDEX IF NOT EXISTS `idx_waitlist_product_status` ON `{prefix}srwm_waitlist` (`product_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_waitlist_notified` ON `{prefix}srwm_waitlist` (`notified`, `date_added`);
CREATE INDEX IF NOT EXISTS `idx_suppliers_product` ON `{prefix}srwm_suppliers` (`product_id`, `email`);
CREATE INDEX IF NOT EXISTS `idx_restock_logs_product_method` ON `{prefix}srwm_restock_logs` (`product_id`, `method`, `timestamp`);
CREATE INDEX IF NOT EXISTS `idx_restock_tokens_expires` ON `{prefix}srwm_restock_tokens` (`expires_at`, `used`);
CREATE INDEX IF NOT EXISTS `idx_csv_tokens_expires` ON `{prefix}srwm_csv_tokens` (`expires_at`, `used`);
CREATE INDEX IF NOT EXISTS `idx_purchase_orders_status` ON `{prefix}srwm_purchase_orders` (`status`, `created_at`);

-- Foreign key constraints (optional - for referential integrity)
-- Note: These are commented out as they may cause issues with some hosting environments
-- Uncomment if your hosting environment supports foreign keys

/*
ALTER TABLE `{prefix}srwm_waitlist`
ADD CONSTRAINT `fk_waitlist_product` FOREIGN KEY (`product_id`) REFERENCES `{prefix}posts` (`ID`) ON DELETE CASCADE;

ALTER TABLE `{prefix}srwm_suppliers`
ADD CONSTRAINT `fk_suppliers_product` FOREIGN KEY (`product_id`) REFERENCES `{prefix}posts` (`ID`) ON DELETE CASCADE;

ALTER TABLE `{prefix}srwm_restock_logs`
ADD CONSTRAINT `fk_restock_logs_product` FOREIGN KEY (`product_id`) REFERENCES `{prefix}posts` (`ID`) ON DELETE CASCADE;

ALTER TABLE `{prefix}srwm_restock_tokens`
ADD CONSTRAINT `fk_restock_tokens_product` FOREIGN KEY (`product_id`) REFERENCES `{prefix}posts` (`ID`) ON DELETE CASCADE;

ALTER TABLE `{prefix}srwm_purchase_orders`
ADD CONSTRAINT `fk_purchase_orders_product` FOREIGN KEY (`product_id`) REFERENCES `{prefix}posts` (`ID`) ON DELETE CASCADE;
*/

-- Sample data for testing (optional)
-- Uncomment the following lines to insert sample data for testing purposes

/*
INSERT INTO `{prefix}srwm_waitlist` (`product_id`, `customer_email`, `customer_name`, `date_added`) VALUES
(123, 'customer1@example.com', 'John Doe', NOW()),
(123, 'customer2@example.com', 'Jane Smith', NOW()),
(456, 'customer3@example.com', 'Bob Johnson', NOW());

INSERT INTO `{prefix}srwm_suppliers` (`product_id`, `email`, `name`, `threshold`, `channels`) VALUES
(123, 'supplier1@example.com', 'ABC Supplies', 5, '["email"]'),
(456, 'supplier2@example.com', 'XYZ Corporation', 10, '["email", "whatsapp"]');

INSERT INTO `{prefix}srwm_restock_logs` (`product_id`, `quantity`, `method`, `supplier_email`, `timestamp`) VALUES
(123, 50, 'manual', NULL, NOW()),
(456, 25, 'supplier_link', 'supplier2@example.com', NOW());
*/