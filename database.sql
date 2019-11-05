SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

-- DROP DATABASE IF EXISTS `tarallo`;
-- CREATE DATABASE `tarallo` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci /*!40100 DEFAULT CHARACTER SET utf8mb4 */;
-- USE `tarallo`;

SET NAMES utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE TABLE `Item`
(
    -- Max length would be approx. 190 * 4 bytes = 760, less than the apparently random limit of 767 bytes.
    `Code` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Brand` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `Model` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `Variant` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `Token` VARCHAR(100) COLLATE utf8mb4_bin DEFAULT NULL,
    `DeletedAt` TIMESTAMP(6) NULL DEFAULT NULL,
    `LostAt` TIMESTAMP(6) NULL DEFAULT NULL,
    INDEX (`DeletedAt`),
    INDEX (`LostAt`),
    -- TODO: reenable later
    -- FOREIGN KEY (`Brand`, `Model`, `Variant`) REFERENCES `Products` (`Brand`, `Model`, `Variant`)
    --	ON DELETE NO ACTION
    --	ON UPDATE CASCADE,
    PRIMARY KEY (`Code`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `Feature`
(
    `Feature` VARCHAR(40) COLLATE utf8mb4_bin NOT NULL,
    `Group` VARCHAR(100) COLLATE utf8mb4_bin NOT NULL,
    `Type` INT NOT NULL, -- 0 = text, 1 = number, 2 = "enum", 3 = double
    PRIMARY KEY (`Feature`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;


CREATE TABLE `FeatureEnum`
(
    `Feature` VARCHAR(40) COLLATE utf8mb4_bin NOT NULL,
    `ValueEnum` VARCHAR(40) COLLATE utf8mb4_bin NOT NULL,
    PRIMARY KEY (`Feature`, `ValueEnum`),
    CONSTRAINT FOREIGN KEY (`Feature`) REFERENCES `Feature` (`Feature`)
        ON DELETE NO ACTION
        ON UPDATE CASCADE
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;


CREATE TABLE `ItemFeature`
(
    `Code` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Feature` VARCHAR(40) COLLATE utf8mb4_bin NOT NULL,
    `Value` BIGINT UNSIGNED DEFAULT NULL,
    `ValueEnum` VARCHAR(40) COLLATE utf8mb4_bin DEFAULT NULL,
    `ValueText` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `ValueDouble` DOUBLE DEFAULT NULL,
    PRIMARY KEY (`Code`, `Feature`),
    -- INDEX `Feature` (`Feature`), -- FOREIGN KEY is already an INDEX
    INDEX `Value` (`Value`),
    INDEX `ValueDouble` (`ValueDouble`),
    -- INDEX `Value2` (`ValueEnum`), -- FOREIGN KEY is already an INDEX
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

CREATE TABLE Audit
(
    `Code` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Change` CHAR(1) COLLATE utf8mb4_bin NOT NULL,
    `Other` VARCHAR(100) NULL COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `Time` TIMESTAMP(6) DEFAULT NOW(6) NOT NULL,
    `User` VARCHAR(100) NULL COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`Code`, `Time`, `Change`),
    CONSTRAINT FOREIGN KEY (`Code`) REFERENCES `Item` (`Code`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX (`Change`),
    CONSTRAINT check_change
        CHECK (
                (`Change` IN ('M', 'R') AND `Other` IS NOT NULL) OR
                (`Change` IN ('C', 'U', 'D', 'L') AND `Other` IS NULL)
            )
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `Tree`
(
    `Ancestor` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Descendant` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Depth` INT UNSIGNED NOT NULL,
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

CREATE TABLE `Prefixes`
(
    `Prefix` VARCHAR(20) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Integer` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`Prefix`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `Search`
(
    `Code` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
    `Expires` TIMESTAMP NOT NULL DEFAULT 0,
    `ResultsCount` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `Owner` VARCHAR(100) COLLATE utf8mb4_unicode_ci,
    PRIMARY KEY (`Code`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `SearchResult`
(
    Search BIGINT UNSIGNED,
    `Item` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Order` BIGINT UNSIGNED,
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

CREATE TABLE `Configuration`
(
    `Key` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Value` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    PRIMARY KEY (`Key`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE Session
(
    Session VARCHAR(100) NOT NULL,
    Data TEXT DEFAULT NULL,
    Redirect TEXT DEFAULT NULL,
    `LastAccess` TIMESTAMP NOT NULL DEFAULT current_timestamp,
    PRIMARY KEY (`Session`)
    -- CHECK ((Data IS NULL AND Redirect IS NOT NULL) OR (Data IS NOT NULL AND Redirect IS NULL))
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `SessionToken`
(
    `Token` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Data` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
    `Owner` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `LastAccess` TIMESTAMP NOT NULL DEFAULT current_timestamp,
    PRIMARY KEY (`Token`),
    INDEX (`Owner`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

INSERT INTO `Configuration` (`Key`, `Value`)
VALUES ('SchemaVersion', 8);
