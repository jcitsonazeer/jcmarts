-- 1) offer_details
CREATE TABLE `offer_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `offer_name` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_id` bigint unsigned DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by_id` bigint unsigned DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_offer_details_offer_name` (`offer_name`),
  KEY `idx_offer_details_is_active` (`is_active`),
  KEY `idx_offer_details_created_by` (`created_by_id`),
  KEY `idx_offer_details_updated_by` (`updated_by_id`),
  CONSTRAINT `fk_offer_details_created_by` FOREIGN KEY (`created_by_id`) REFERENCES `admin_login` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_offer_details_updated_by` FOREIGN KEY (`updated_by_id`) REFERENCES `admin_login` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) offer_products
CREATE TABLE `offer_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `offer_id` bigint unsigned NOT NULL,
  `products_id` bigint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_id` bigint unsigned DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_by_id` bigint unsigned DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_offer_products_offer_product` (`offer_id`, `products_id`),
  KEY `idx_offer_products_offer` (`offer_id`),
  KEY `idx_offer_products_product` (`products_id`),
  KEY `idx_offer_products_is_active` (`is_active`),
  KEY `idx_offer_products_created_by` (`created_by_id`),
  KEY `idx_offer_products_updated_by` (`updated_by_id`),
  CONSTRAINT `fk_offer_products_offer` FOREIGN KEY (`offer_id`) REFERENCES `offer_details` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_offer_products_product` FOREIGN KEY (`products_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_offer_products_created_by` FOREIGN KEY (`created_by_id`) REFERENCES `admin_login` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_offer_products_updated_by` FOREIGN KEY (`updated_by_id`) REFERENCES `admin_login` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) index_banner changes for optional redirect link fields
ALTER TABLE `index_banner`
  ADD COLUMN `offer_details_id` bigint unsigned DEFAULT NULL AFTER `banner_image`,
  ADD COLUMN `sub_category_id` bigint unsigned DEFAULT NULL AFTER `offer_details_id`,
  ADD KEY `idx_index_banner_offer_details` (`offer_details_id`),
  ADD KEY `idx_index_banner_sub_category` (`sub_category_id`),
  ADD CONSTRAINT `fk_index_banner_offer_details` FOREIGN KEY (`offer_details_id`) REFERENCES `offer_details` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_index_banner_sub_category` FOREIGN KEY (`sub_category_id`) REFERENCES `sub_category` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
