-- Garante que os jogos padrão existam e estejam ativos.
-- Executar em instalações existentes: mysql -u user -p database < database/migrations/004_seed_games.sql

INSERT INTO `games` (`slug`,`name`,`icon`,`is_active`) VALUES
('fortune-tiger','Fortune Tiger','tiger',1),
('fortune-dragon','Fortune Dragon','dragon',1),
('fortune-ox','Fortune Ox','ox',1)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `icon` = VALUES(`icon`),
  `is_active` = VALUES(`is_active`);

