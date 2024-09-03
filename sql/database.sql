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
    INDEX `Brand_Model_Variant` (`Brand`, `Model`, `Variant`),
    PRIMARY KEY (`Code`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `Product`
(
    -- Max length would be approx. 190 * 4 bytes = 760, less than the apparently random limit of 767 bytes.
    `Brand` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Model` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Variant` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    PRIMARY KEY (`Brand`, `Model`, `Variant`)
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

CREATE TABLE `ProductFeature`
(
    `Brand` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Model` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Variant` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Feature` VARCHAR(40) COLLATE utf8mb4_bin NOT NULL,
    `Value` BIGINT UNSIGNED DEFAULT NULL,
    `ValueEnum` VARCHAR(40) COLLATE utf8mb4_bin DEFAULT NULL,
    `ValueText` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `ValueDouble` DOUBLE DEFAULT NULL,
    PRIMARY KEY (`Brand`, `Model`, `Variant`, `Feature`),
    INDEX `Value` (`Value`),
    INDEX `ValueDouble` (`ValueDouble`),
    CONSTRAINT FOREIGN KEY (`Brand`, `Model`, `Variant`) REFERENCES `Product` (`Brand`, `Model`, `Variant`)
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

CREATE TABLE AuditProduct
(
    `Brand` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Model` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Variant` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Change` CHAR(1) COLLATE utf8mb4_bin NOT NULL,
    `Time` TIMESTAMP(6) DEFAULT NOW(6) NOT NULL,
    `User` VARCHAR(100) NULL COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`Brand`, `Model`, `Variant`, `Time`, `Change`),
    CONSTRAINT FOREIGN KEY (`Brand`, `Model`, `Variant`) REFERENCES `Product` (`Brand`, `Model`, `Variant`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX (`Change`),
    CONSTRAINT check_change
        CHECK (`Change` IN ('C', 'R', 'U', 'D'))
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
    `Query` TEXT NOT NULL,
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

CREATE TABLE `BulkTable`
(
    `Identifier` BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
    `BulkIdentifier` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Time` TIMESTAMP(6) DEFAULT NOW(6) NOT NULL,
    `User` VARCHAR(100) NULL COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `Type` CHAR(1) COLLATE utf8mb4_bin NOT NULL,
    `JSON` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
    PRIMARY KEY (`Identifier`),
    INDEX (`Type`),
        CONSTRAINT check_type
            CHECK (`Type` IN ('I', 'P'))
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `Normalization`
(
    `MinimizedKey` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `NormalizedValue` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Category` VARCHAR(40) COLLATE utf8mb4_unicode_ci NOT NULL,
    PRIMARY KEY (`MinimizedKey`),
    INDEX (`Category`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `NormalizationForbidden`
(
    `MinimizedKey` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Category` VARCHAR(40) COLLATE utf8mb4_unicode_ci NOT NULL,
    PRIMARY KEY (`MinimizedKey`),
    INDEX (`Category`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE `Donations` (
                             `Id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                             `Name` text NOT NULL,
                             `Location` text DEFAULT NULL,
                             `Date` timestamp(6) NULL DEFAULT NULL,
                             `Notes` text DEFAULT NULL,
                             `IsCompleted` tinyint(1) DEFAULT 0,
                             PRIMARY KEY (`Id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `DonationItem` (
    `Donation` bigint(20) unsigned NOT NULL,
    `Code` varchar(255) NOT NULL,
    PRIMARY KEY (`Donation`,`Code`),
    KEY `Code` (`Code`),
    CONSTRAINT `DonationItem_ibfk_1` FOREIGN KEY (`Donation`) REFERENCES `Donations` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `DonationItem_ibfk_2` FOREIGN KEY (`Code`) REFERENCES `Item` (`Code`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `DonationTasks` (
    `DonationId` bigint(20) unsigned NOT NULL,
    `Id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `Index` int(11) NOT NULL,
    `Title` text NOT NULL,
    `ItemType` text NOT NULL,
    PRIMARY KEY (`Id`),
    KEY `DonationId` (`DonationId`),
    CONSTRAINT `DonationTasks_ibfk_1` FOREIGN KEY (`DonationId`) REFERENCES `Donations` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `DonationTasksProgress` (
    `DonationId` bigint(20) unsigned NOT NULL,
    `TaskId` bigint(20) unsigned NOT NULL,
    `ItemCode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `Completed` tinyint(1) NOT NULL,
    KEY `DonationId` (`DonationId`),
    KEY `TaskId` (`TaskId`),
    KEY `ItemCode` (`ItemCode`),
    CONSTRAINT `DonationTasksProgress_ibfk_5` FOREIGN KEY (`TaskId`) REFERENCES `DonationTasks` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `DonationTasksProgress_ibfk_6` FOREIGN KEY (`DonationId`, `ItemCode`) REFERENCES `DonationItem` (`Donation`, `Code`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `LocationAutosuggestCache` (
	`Name` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
	`Color` VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL,
	FOREIGN KEY (`Name`) REFERENCES `Item` (`Code`) ON DELETE CASCADE ON UPDATE CASCADE -- REMEMBER TO DO NOT TRUST THIS FOREIGN KEY, THERE IS A TRIGGER THAT WILL DISABLE CASCADE WHEN RENAMING AN ITEM
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ProductFeature - ItemFeature View

CREATE VIEW ProductItemFeature AS
SELECT unioned.Code,
       unioned.Feature AS Feature,
       MAX(Value_Item) AS Value_Item,
       MAX(ValueEnum_Item) AS ValueEnum_Item,
       MAX(ValueText_Item) AS ValueText_Item,
       MAX(ValueDouble_Item) AS ValueDouble_Item,
       MAX(Value_Product) AS Value_Product,
       MAX(ValueEnum_Product) AS ValueEnum_Product,
       MAX(ValueText_Product) AS ValueText_Product,
       MAX(ValueDouble_Product) AS ValueDouble_Product
FROM (
         SELECT Item.Code,
                ItemFeature.Feature AS Feature,
                ItemFeature.Value AS Value_Item,
                ItemFeature.ValueEnum AS ValueEnum_Item,
                ItemFeature.ValueText AS ValueText_Item,
                ItemFeature.ValueDouble AS ValueDouble_Item,
                NULL AS Value_Product,
                NULL AS ValueEnum_Product,
                NULL AS ValueText_Product,
                NULL AS ValueDouble_Product
         FROM Item
              NATURAL JOIN ItemFeature
         UNION
         SELECT Item.Code,
                PF.Feature AS Feature,
                NULL AS Value_Item,
                NULL AS ValueEnum_Item,
                NULL AS ValueText_Item,
                NULL AS ValueDouble_Item,
                PF.Value AS Value_Product,
                PF.ValueEnum AS ValueEnum_Product,
                PF.ValueText AS ValueText_Product,
                PF.ValueDouble AS ValueDouble_Product
         FROM Item
              JOIN ProductFeature PF ON Item.Brand = PF.Brand AND Item.Model = PF.Model AND Item.Variant = PF.Variant
     ) unioned
GROUP BY Code, Feature;

CREATE VIEW ProductItemFeatureUnified AS
SELECT Code,
       Feature,
    COALESCE(Value_Item, Value_Product) AS `Value`,
    COALESCE(ValueText_Item, ValueText_Product) AS ValueText,
    COALESCE(ValueEnum_Item, ValueEnum_Product) AS ValueEnum,
    COALESCE(ValueDouble_Item, ValueDouble_Product) AS ValueDouble
FROM ProductItemFeature;

-- Do not combine these lines, they're parsed by update-db... WITH A REGEX!
INSERT INTO `Configuration` (`Key`, `Value`) VALUES ('SchemaVersion', 25);
INSERT INTO `Configuration` (`Key`, `Value`) VALUES ('DataVersion', 32);
