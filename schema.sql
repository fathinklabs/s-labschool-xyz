-- ==============================================================================
-- Schema Basis Data: s.labschool.xyz (s.id Clone Labschool)
-- DBMS: MariaDB 11.4+
-- ==============================================================================

USE `s_labschool_xyz`;

-- 1. Tabel Users (Restriksi Domain @labschool.xyz & @elabschool.sch.id)
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `domain` ENUM('labschool.xyz', 'elabschool.sch.id') NOT NULL,
    `avatar_url` VARCHAR(255) NULL,
    `role` ENUM('superadmin', 'teacher', 'staff', 'student') DEFAULT 'teacher',
    `api_key` VARCHAR(64) UNIQUE NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_domain` (`domain`),
    INDEX `idx_api_key` (`api_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel Short URLs (Fitur Lengkap Free & Pro)
CREATE TABLE IF NOT EXISTS `short_urls` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT NOT NULL,
    `short_code` VARCHAR(64) UNIQUE NOT NULL,
    `original_url` TEXT NOT NULL,
    `title` VARCHAR(255) NULL,
    `custom_slug` VARCHAR(64) UNIQUE NULL,
    `password_hash` VARCHAR(255) NULL,           -- Pro: Password Protected
    `expires_at` DATETIME NULL,                  -- Pro: Link Expiration
    `starts_at` DATETIME NULL,                   -- Pro: Scheduled Link
    `is_active` TINYINT(1) DEFAULT 1,            -- Pro: Deactivate Toggle
    `utm_source` VARCHAR(100) NULL,              -- Pro: UTM Builder
    `utm_medium` VARCHAR(100) NULL,
    `utm_campaign` VARCHAR(100) NULL,
    `tags` VARCHAR(255) NULL,                    -- Feature: Tagging & Campaign aggregation
    `ios_url` TEXT NULL,                         -- Feature: Device-based deep linking for iOS
    `android_url` TEXT NULL,                     -- Feature: Device-based deep linking for Android
    `qr_fg_color` VARCHAR(20) DEFAULT '#1E40AF', -- Pro: Custom QR Foreground
    `qr_bg_color` VARCHAR(20) DEFAULT '#FFFFFF', -- Pro: Custom QR Background
    `qr_logo` VARCHAR(50) DEFAULT 'labschool',   -- Pro: Center logo
    `qr_frame` VARCHAR(50) DEFAULT 'none',       -- Feature: Smart QR Frame ('none', 'badge-bottom', 'badge-top', 'card')
    `qr_cta_text` VARCHAR(100) DEFAULT 'PINDAI SAYA', -- Feature: QR Frame Call to Action Text
    `clicks_count` INT UNSIGNED DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_short_code` (`short_code`),
    INDEX `idx_custom_slug` (`custom_slug`),
    INDEX `idx_user_urls` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel Log Analitik Klik (Detail Perangkat, Geolokasi CF, Referrer)
CREATE TABLE IF NOT EXISTS `click_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `url_id` BIGINT NOT NULL,
    `clicked_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `ip_address` VARCHAR(45) NULL,
    `country` VARCHAR(64) NULL,                  -- Dari Header Cloudflare CF-IPCountry
    `city` VARCHAR(100) NULL,                    -- Dari Header Cloudflare CF-IPCity
    `user_agent` TEXT NULL,
    `device_type` ENUM('mobile', 'desktop', 'tablet', 'bot', 'other') DEFAULT 'other',
    `browser` VARCHAR(50) NULL,
    `os` VARCHAR(50) NULL,
    `referrer` VARCHAR(255) NULL,
    FOREIGN KEY (`url_id`) REFERENCES `short_urls`(`id`) ON DELETE CASCADE,
    INDEX `idx_url_time` (`url_id`, `clicked_at`),
    INDEX `idx_clicked_at` (`clicked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel Halaman Microsite / Bio Link
CREATE TABLE IF NOT EXISTS `microsites` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT NOT NULL,
    `slug` VARCHAR(64) UNIQUE NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `bio` TEXT NULL,
    `avatar_url` VARCHAR(255) NULL,
    `theme` VARCHAR(50) DEFAULT 'labs-navy',     -- Pro: Themes
    `template_style` VARCHAR(50) DEFAULT 'genially-showcase', -- Genially modern template presets ('standard', 'genially-showcase', 'genially-roadmap', 'genially-cards')
    `countdown_date` DATETIME NULL,              -- Genially Event Countdown
    `countdown_title` VARCHAR(150) NULL,
    `custom_bg_type` ENUM('theme', 'color', 'gradient', 'image') DEFAULT 'theme',
    `custom_bg_value` VARCHAR(255) NULL,
    `button_style` VARCHAR(50) DEFAULT 'rounded-xl',
    `seo_title` VARCHAR(150) NULL,
    `seo_description` TEXT NULL,
    `seo_image` VARCHAR(255) NULL,
    `social_links` JSON NULL,                    -- WhatsApp, IG, YT, X, dll
    `remove_branding` TINYINT(1) DEFAULT 1,
    `custom_footer` VARCHAR(255) DEFAULT 'SMP Labschool Jakarta',
    `is_active` TINYINT(1) DEFAULT 1,
    `clicks_count` INT UNSIGNED DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_microsite_slug` (`slug`),
    INDEX `idx_user_microsite` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabel Item / Blok Komponen Microsite
CREATE TABLE IF NOT EXISTS `microsite_blocks` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `microsite_id` BIGINT NOT NULL,
    `block_type` ENUM('link', 'social', 'youtube', 'text', 'faq', 'vcard', 'image', 'pdf') NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `subtitle` VARCHAR(255) NULL,
    `target_url` TEXT NULL,
    `icon` VARCHAR(50) DEFAULT 'link',
    `animation` VARCHAR(50) DEFAULT 'none',      -- pulse, bounce, glow
    `metadata` JSON NULL,                        -- FAQ items, vCard fields, embed IDs
    `display_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `clicks_count` INT UNSIGNED DEFAULT 0,
    FOREIGN KEY (`microsite_id`) REFERENCES `microsites`(`id`) ON DELETE CASCADE,
    INDEX `idx_microsite_order` (`microsite_id`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabel Kolaborasi Organisasi / Workspace Pengguna
CREATE TABLE IF NOT EXISTS `organization_collaborators` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `owner_id` BIGINT NOT NULL,                  -- User pemilik organisasi/lingkup
    `collaborator_id` BIGINT NOT NULL,           -- User yang diundang
    `role` ENUM('editor', 'viewer') DEFAULT 'editor',
    `status` ENUM('active', 'pending') DEFAULT 'active',
    `invited_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`collaborator_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_collab` (`owner_id`, `collaborator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabel Grup Kerja & Pembatasan Scope Tag (Maksimal 10 Grup per User)
CREATE TABLE IF NOT EXISTS `user_groups` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `owner_id` BIGINT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `tags` VARCHAR(255) NULL,                     -- Tag yang diasosiasikan dengan grup ini
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_group_members` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `group_id` BIGINT NOT NULL,
    `user_id` BIGINT NOT NULL,
    `added_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`group_id`) REFERENCES `user_groups`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_group_member` (`group_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabel Riwayat Bulk Upload Tautan
CREATE TABLE IF NOT EXISTS `bulk_upload_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT NOT NULL,
    `batch_name` VARCHAR(150) NOT NULL,
    `total_links` INT UNSIGNED DEFAULT 0,
    `tags` VARCHAR(255) NULL,
    `sample_links` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Tabel Log Aktivitas Pengguna (User Activity Logs)
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `description` TEXT NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_activity` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
