-- Remove sistema de pontos (mantém apenas saldo em R$).
-- Executar em instalações existentes: mysql -u user -p database < database/migrations/003_remove_points.sql

ALTER TABLE `wallets` DROP COLUMN `points`;

