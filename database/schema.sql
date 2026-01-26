-- TigerZone – Schema MySQL

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `invite_code` VARCHAR(16) NOT NULL UNIQUE,
    `referred_by` INT UNSIGNED NULL,
    `ip` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `fingerprint` VARCHAR(64) NULL,
    `last_ip` VARCHAR(45) NULL,
    `last_login_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email` (`email`),
    KEY `users_invite_code` (`invite_code`),
    KEY `users_referred_by` (`referred_by`),
    KEY `users_ip` (`ip`),
    KEY `users_fingerprint` (`fingerprint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wallets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `wallets_user_id` (`user_id`),
    CONSTRAINT `fk_wallets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `type` ENUM('deposit','withdraw','win','bet','bonus','refund') NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `balance_before` DECIMAL(15,2) NULL,
    `balance_after` DECIMAL(15,2) NULL,
    `reference` VARCHAR(64) NULL,
    `metadata` JSON NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `transactions_user_id` (`user_id`),
    KEY `transactions_type` (`type`),
    KEY `transactions_created_at` (`created_at`),
    CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `games` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(64) NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `icon` VARCHAR(32) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `games_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `game_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `game_id` INT UNSIGNED NOT NULL,
    `bet` DECIMAL(15,2) NOT NULL,
    `win` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `balance_before` DECIMAL(15,2) NULL,
    `balance_after` DECIMAL(15,2) NULL,
    `metadata` JSON NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `game_logs_user_id` (`user_id`),
    KEY `game_logs_game_id` (`game_id`),
    KEY `game_logs_created_at` (`created_at`),
    CONSTRAINT `fk_game_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_game_logs_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invites` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `code` VARCHAR(16) NOT NULL,
    `invited_user_id` INT UNSIGNED NULL,
    `inviter_ip` VARCHAR(45) NULL,
    `invited_ip` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `bonus_given` TINYINT(1) NOT NULL DEFAULT 0,
    `bonus_blocked_reason` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `invites_code` (`code`),
    KEY `invites_user_id` (`user_id`),
    KEY `invites_invited_user_id` (`invited_user_id`),
    KEY `invites_inviter_ip` (`inviter_ip`),
    KEY `invites_invited_ip` (`invited_ip`),
    CONSTRAINT `fk_invites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_invites_invited` FOREIGN KEY (`invited_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bans` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type` ENUM('user','ip','device') NOT NULL,
    `target` VARCHAR(255) NOT NULL,
    `reason` VARCHAR(500) NULL,
    `admin_id` INT UNSIGNED NULL,
    `expires_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `bans_type_target` (`type`,`target`),
    KEY `bans_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `access_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NULL,
    `ip` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(500) NULL,
    `fingerprint` VARCHAR(64) NULL,
    `action` VARCHAR(64) NULL,
    `path` VARCHAR(500) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `access_logs_user_id` (`user_id`),
    KEY `access_logs_ip` (`ip`),
    KEY `access_logs_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(64) NOT NULL,
    `value` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `settings_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados iniciais: jogos
INSERT INTO `games` (`slug`,`name`,`icon`) VALUES
('fortune-tiger','Fortune Tiger','tiger'),
('fortune-dragon','Fortune Dragon','dragon'),
('fortune-ox','Fortune Ox','ox')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `icon`=VALUES(`icon`);

-- Configurações padrão
INSERT INTO `settings` (`key`,`value`) VALUES
('site_title','TigerZone'),
('site_logo_url',''),
('stats_online_base','42'),
('stats_wins_base','12850'),
('stats_deposits_base','89420')
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`);

SET FOREIGN_KEY_CHECKS = 1;
