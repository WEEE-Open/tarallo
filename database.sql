SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

-- DROP DATABASE IF EXISTS `tarallo`;
-- CREATE DATABASE `tarallo` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci /*!40100 DEFAULT CHARACTER SET utf8mb4 */;
-- USE `tarallo`;

SET NAMES utf8mb4
COLLATE utf8mb4_unicode_ci;

CREATE TABLE `Item` (
	-- Max length would be approx. 190 * 4 bytes = 760, less than the apparently random limit of 767 bytes.
	`Code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
	`Brand` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
	`Model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
	`Variant` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
	`Token` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL,
	`DeletedAt` timestamp NULL DEFAULT NULL,
	UNIQUE KEY (`Code`),
	INDEX (`Code`),
	INDEX (`DeletedAt`),
	FOREIGN KEY (`Brand`, `Model`, `Variant`) REFERENCES `Products` (`Brand`, `Model`, `Variant`)
		ON DELETE NO ACTION
		ON UPDATE CASCADE,
	PRIMARY KEY (`Code`)
)
	ENGINE = InnoDB
	DEFAULT CHARSET = utf8mb4
	COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `Feature` (
	`Feature` varchar(40) COLLATE utf8mb4_bin NOT NULL,
	`Type` int NOT NULL, -- 0 = text, 1 = number, 2 = "enum", 3 = double
	PRIMARY KEY (`Feature`)
)
	ENGINE = InnoDB
	DEFAULT CHARSET = utf8mb4
	COLLATE = utf8mb4_unicode_ci;


CREATE TABLE `FeatureEnum` (
	`Feature` varchar(40) COLLATE utf8mb4_bin NOT NULL,
	`ValueEnum` varchar(40) COLLATE utf8mb4_bin NOT NULL,
	PRIMARY KEY (`Feature`, `ValueEnum`),
	CONSTRAINT FOREIGN KEY (`Feature`) REFERENCES `Feature` (`Feature`)
		ON DELETE NO ACTION
		ON UPDATE CASCADE
)
	ENGINE = InnoDB
	DEFAULT CHARSET = utf8mb4
	COLLATE = utf8mb4_unicode_ci;


CREATE TABLE `ItemFeature` (
	`Code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
	`Feature` varchar(40) COLLATE utf8mb4_bin NOT NULL,
	`Value` bigint UNSIGNED DEFAULT NULL,
	`ValueEnum` varchar(40) COLLATE utf8mb4_bin DEFAULT NULL,
	`ValueText` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
	`ValueDouble` double DEFAULT NULL,
	PRIMARY KEY (`Code`, `Feature`),
	INDEX `Feature` (`Feature`),
	INDEX `Value1` (`Value`),
	INDEX `Value2` (`ValueEnum`),
	INDEX `Value3` (`ValueDouble`),
	CONSTRAINT FOREIGN KEY (`Code`) REFERENCES `Item` (`Code`)
		ON DELETE CASCADE
		ON UPDATE CASCADE,
	CONSTRAINT FOREIGN KEY (`Feature`) REFERENCES `Feature` (`Feature`)
		ON DELETE NO ACTION
		ON UPDATE CASCADE,
	CONSTRAINT FOREIGN KEY (`Feature`, `ValueEnum`) REFERENCES `FeatureEnum` (`Feature`, `ValueEnum`)
		ON DELETE NO ACTION
		ON UPDATE CASCADE,
	CHECK ((`Value` IS NOT NULL AND `ValueText` IS NULL AND `ValueEnum` IS NULL AND `ValueDouble` IS NULL)
		OR (`Value` IS NULL AND `ValueText` IS NOT NULL AND `ValueEnum` IS NULL AND `ValueDouble` IS NULL)
		OR (`Value` IS NULL AND `ValueText` IS NULL AND `ValueEnum` IS NOT NULL AND `ValueDouble` IS NULL)
		OR (`Value` IS NULL AND `ValueText` IS NULL AND `ValueEnum` IS NULL AND `ValueDouble` IS NOT NULL))
)
	ENGINE = InnoDB
	DEFAULT CHARSET = utf8mb4
	COLLATE = utf8mb4_unicode_ci;

CREATE TABLE Audit (
	`Code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
	`Change` char(1) COLLATE utf8mb4_bin NOT NULL,
	`Other` varchar(100) COLLATE utf8mb4_unicode_ci,
	`Time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`Code`),
		CHECK ((`Change` = 'C') OR (`Change` = 'R') OR (`Change` = 'U') OR (`Change` = 'D') OR (`Change` = 'M')), -- R is for Rename, actually
		CHECK (Other IS NULL OR (Other IS NOT NULL AND `Change` = 'M')),
	CONSTRAINT FOREIGN KEY (`Code`) REFERENCES `Item` (`Code`),
	CONSTRAINT FOREIGN KEY (`Other`) REFERENCES `Item` (`Code`)
)
	ENGINE = InnoDB
	DEFAULT CHARSET = utf8mb4
	COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `Tree` (
	`Ancestor` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
	`Descendant` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
	`Depth` int UNSIGNED NOT NULL,
	PRIMARY KEY (`Ancestor`, `Descendant`),
	-- PK is already an index on Ancestor
	INDEX (`Descendant`),
	INDEX (`Depth`),
	CONSTRAINT FOREIGN KEY (`Ancestor`) REFERENCES `Item` (`Code`)
		ON DELETE NO ACTION
		ON UPDATE CASCADE,
	CONSTRAINT FOREIGN KEY (`Descendant`) REFERENCES `Item` (`Code`)
		ON DELETE NO ACTION
		ON UPDATE CASCADE
)
	ENGINE = InnoDB
	DEFAULT CHARSET = utf8mb4
	COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `Prefixes` (
	`Prefix` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
	`Integer` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY (`Prefix`)
)
	ENGINE = InnoDB
	DEFAULT CHARSET = utf8mb4
	COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `User` (
	`Name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
	`Password` text COLLATE utf8mb4_unicode_ci NOT NULL,
	`Session` char(32) COLLATE utf8mb4_bin,
	`SessionExpiry` timestamp NOT NULL DEFAULT 0,
	`Enabled` boolean NOT NULL DEFAULT FALSE,
	PRIMARY KEY (`Name`),
	UNIQUE KEY (`Session`),
	UNIQUE KEY (`Name`),
	INDEX (`Name`)
)
	ENGINE = InnoDB
	DEFAULT CHARSET = utf8mb4
	COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `Search` (
	`Code` bigint UNSIGNED AUTO_INCREMENT NOT NULL,
	`Expires` timestamp NOT NULL DEFAULT 0,
	`ResultsCount` bigint UNSIGNED NOT NULL DEFAULT 0,
	`Owner` varchar(100) COLLATE utf8mb4_unicode_ci,
	PRIMARY KEY (`Code`),
	FOREIGN KEY (`Owner`) REFERENCES `User` (`Name`)
		ON UPDATE CASCADE
		ON DELETE SET NULL
)
	ENGINE = InnoDB
	DEFAULT CHARSET = utf8mb4
	COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `SearchResult` (
	Search bigint UNSIGNED,
	`Item` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
	`Order` bigint UNSIGNED,
	PRIMARY KEY (Search, `Item`),
	FOREIGN KEY (Search) REFERENCES `Search` (`Code`)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
	FOREIGN KEY (`Item`) REFERENCES `Item` (`Code`)
		ON UPDATE CASCADE
		ON DELETE CASCADE
)
	ENGINE = InnoDB
	DEFAULT CHARSET = utf8mb4
	COLLATE = utf8mb4_unicode_ci;
