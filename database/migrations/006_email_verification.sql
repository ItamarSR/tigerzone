-- Adiciona confirmação por e-mail (token + expiração).
-- Executar em instalações existentes: mysql -u user -p database < database/migrations/006_email_verification.sql

ALTER TABLE `users`
  ADD COLUMN `email_verified_at` DATETIME NULL AFTER `email`,
  ADD COLUMN `email_verification_token` VARCHAR(64) NULL AFTER `email_verified_at`,
  ADD COLUMN `email_verification_expires_at` DATETIME NULL AFTER `email_verification_token`,
  ADD UNIQUE KEY `users_email_verification_token` (`email_verification_token`);

-- Mantém compatibilidade: usuários antigos passam a ser considerados verificados.
UPDATE `users` SET `email_verified_at` = COALESCE(`email_verified_at`, `created_at`) WHERE `email_verified_at` IS NULL;

