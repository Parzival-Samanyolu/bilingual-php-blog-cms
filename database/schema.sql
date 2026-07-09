-- ============================================================================
-- real.com.tr — Database Schema
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- MySQL 8.x
-- ============================================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ----------------------------------------------------------------------------
-- 1. settings
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `key`   VARCHAR(100) NOT NULL,
  `value` TEXT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. users
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`              VARCHAR(255) NOT NULL,
  `username`          VARCHAR(100) NOT NULL,
  `email`             VARCHAR(255) NOT NULL,
  `password_hash`     VARCHAR(255) NULL,
  `role`              ENUM('admin','author','reader') NOT NULL DEFAULT 'reader',
  `google_id`         VARCHAR(255) NULL,
  `avatar`            VARCHAR(500) NULL,
  `bio`               TEXT NULL,
  `is_approved`       TINYINT(1) NOT NULL DEFAULT 0,
  `email_verified_at` DATETIME NULL,
  `preferred_lang`    ENUM('tr','en') NOT NULL DEFAULT 'tr',
  `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_google_id` (`google_id`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. login_attempts
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`           VARCHAR(45) NOT NULL,
  `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_ip_time` (`ip`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. password_resets
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(255) NOT NULL,
  `token`      VARCHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used`       TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_password_resets_token` (`token`),
  KEY `idx_password_resets_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. categories
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id`  INT UNSIGNED NULL,
  `slug`       VARCHAR(200) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  KEY `idx_categories_parent` (`parent_id`),
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`)
    REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. category_translations
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `category_translations` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `lang`        ENUM('tr','en') NOT NULL,
  `name`        VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_category_translations` (`category_id`, `lang`),
  CONSTRAINT `fk_cat_trans_category` FOREIGN KEY (`category_id`)
    REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. articles
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `articles` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `author_id`      INT UNSIGNED NOT NULL,
  `category_id`    INT UNSIGNED NOT NULL,
  `slug`           VARCHAR(300) NOT NULL,
  `cover_image`    VARCHAR(500) NULL,
  `status`         ENUM('draft','pending','published','rejected') NOT NULL DEFAULT 'draft',
  `view_count`     INT NOT NULL DEFAULT 0,
  `lang`           ENUM('tr','en') NOT NULL DEFAULT 'tr',
  `translation_of` INT UNSIGNED NULL,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_articles_slug` (`slug`),
  KEY `idx_articles_status_lang` (`status`, `lang`),
  KEY `idx_articles_category` (`category_id`),
  KEY `idx_articles_author` (`author_id`),
  KEY `idx_articles_created` (`created_at`),
  KEY `idx_articles_translation_of` (`translation_of`),
  CONSTRAINT `fk_articles_author` FOREIGN KEY (`author_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_articles_category` FOREIGN KEY (`category_id`)
    REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_articles_translation_of` FOREIGN KEY (`translation_of`)
    REFERENCES `articles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. article_translations
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `article_translations` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `article_id`       INT UNSIGNED NOT NULL,
  `lang`             ENUM('tr','en') NOT NULL,
  `title`            VARCHAR(500) NOT NULL,
  `content`          LONGTEXT NOT NULL,
  `excerpt`          TEXT NULL,
  `meta_title`       VARCHAR(160) NULL,
  `meta_description` VARCHAR(320) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_article_translations` (`article_id`, `lang`),
  CONSTRAINT `fk_art_trans_article` FOREIGN KEY (`article_id`)
    REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FULLTEXT KEY `ft_article_translations` (`title`, `content`, `excerpt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 9. tags
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tags` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`       VARCHAR(200) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tags_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 10. tag_translations
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tag_translations` (
  `id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tag_id` INT UNSIGNED NOT NULL,
  `lang`   ENUM('tr','en') NOT NULL,
  `name`   VARCHAR(200) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tag_translations` (`tag_id`, `lang`),
  CONSTRAINT `fk_tag_trans_tag` FOREIGN KEY (`tag_id`)
    REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 11. article_tags
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `article_tags` (
  `article_id` INT UNSIGNED NOT NULL,
  `tag_id`     INT UNSIGNED NOT NULL,
  PRIMARY KEY (`article_id`, `tag_id`),
  KEY `idx_article_tags_tag` (`tag_id`),
  CONSTRAINT `fk_article_tags_article` FOREIGN KEY (`article_id`)
    REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_article_tags_tag` FOREIGN KEY (`tag_id`)
    REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 12. comments
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `comments` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `article_id`  INT UNSIGNED NOT NULL,
  `user_id`     INT UNSIGNED NOT NULL,
  `parent_id`   INT UNSIGNED NULL,
  `content`     TEXT NOT NULL,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comments_article_approved` (`article_id`, `is_approved`),
  KEY `idx_comments_user` (`user_id`),
  KEY `idx_comments_parent` (`parent_id`),
  CONSTRAINT `fk_comments_article` FOREIGN KEY (`article_id`)
    REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_comments_parent` FOREIGN KEY (`parent_id`)
    REFERENCES `comments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 13. likes
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `likes` (
  `user_id`    INT UNSIGNED NOT NULL,
  `article_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `article_id`),
  KEY `idx_likes_article` (`article_id`),
  CONSTRAINT `fk_likes_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_likes_article` FOREIGN KEY (`article_id`)
    REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 14. bookmarks
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookmarks` (
  `user_id`    INT UNSIGNED NOT NULL,
  `article_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `article_id`),
  KEY `idx_bookmarks_article` (`article_id`),
  CONSTRAINT `fk_bookmarks_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bookmarks_article` FOREIGN KEY (`article_id`)
    REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;

-- ============================================================================
-- Default settings
-- ============================================================================
INSERT INTO `settings` (`key`, `value`) VALUES
  ('site_name_tr',      'real.com.tr'),
  ('site_name_en',      'real.com.tr'),
  ('adsense_client_id', ''),
  ('ga_measurement_id', ''),
  ('og_default_image',  '/img/og-default.jpg'),
  ('contact_email',     'info@real.com.tr'),
  ('articles_per_page', '12'),
  ('cache_ttl',         '3600')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
