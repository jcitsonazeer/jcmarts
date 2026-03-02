CREATE TABLE `cart` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(255) NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `rate_master_id` bigint unsigned NOT NULL,
  `quantity` int unsigned NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_id` bigint unsigned DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by_id` bigint unsigned DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cart_session` (`session_id`),
  KEY `idx_cart_product` (`product_id`),
  KEY `idx_cart_rate` (`rate_master_id`),
  KEY `idx_cart_session_active` (`session_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
