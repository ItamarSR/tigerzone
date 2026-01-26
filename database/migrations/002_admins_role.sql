-- Role admin/subadmin para admins.
-- Executar em instalações existentes: mysql -u user -p database < database/migrations/002_admins_role.sql

ALTER TABLE `admins` ADD COLUMN `role` ENUM('admin','subadmin') NOT NULL DEFAULT 'admin' AFTER `name`;
