-- Prize pool por marcos de depósitos.
-- Executar em instalações existentes: mysql -u user -p database < database/migrations/001_points_and_prize_pool.sql

INSERT INTO `settings` (`key`, `value`) VALUES
('prize_pool', '0'),
('prize_pool_last_milestone', '0')
ON DUPLICATE KEY UPDATE `key` = `key`;
