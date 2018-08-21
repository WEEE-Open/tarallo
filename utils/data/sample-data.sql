SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

USE `tarallo`;

SET NAMES utf8mb4;

TRUNCATE `User`;
INSERT INTO `User` (`Name`, `Password`, `Session`, `SessionExpiry`, `Enabled`) VALUES
('asd',	'$2y$10$wXP1ooxhHQ2X63Rgxi8GZeHkotzjwW2/M3HX/so1bwal4zDhsMyW6',	NULL,	0,	1),
('IMPORT',	'$2y$10$wXP1ooxhHQ2X63Rgxi8GZeHkotzjwW2/M3HX/so1bwal4zDhsMyW6',	NULL,	0,	1),
('not-enabled',	'$2y$10$wXP1ooxhHQ2X63Rgxi8GZeHkotzjwW2/M3HX/so1bwal4zDhsMyW6',	NULL,	0,	0),
('test',	'$2y$10$wXP1ooxhHQ2X63Rgxi8GZeHkotzjwW2/M3HX/so1bwal4zDhsMyW6',	NULL,	0,	1);
