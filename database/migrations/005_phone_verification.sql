-- Adiciona celular e verificação por código no cadastro/login.
-- Executar em instalações existentes: mysql -u user -p database < database/migrations/005_phone_verification.sql

ALTER TABLE `users`
  ADD COLUMN `phone` VARCHAR(20) NULL AFTER `name`,
  ADD COLUMN `phone_verified_at` DATETIME NULL AFTER `phone`,
  ADD COLUMN `phone_verification_code` VARCHAR(8) NULL AFTER `phone_verified_at`,
  ADD COLUMN `phone_verification_expires_at` DATETIME NULL AFTER `phone_verification_code`,
  ADD UNIQUE KEY `users_phone` (`phone`);

