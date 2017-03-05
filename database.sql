SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

CREATE DATABASE `tarallo` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci /*!40100 DEFAULT CHARACTER SET utf8mb4 */;
USE `tarallo`;

CREATE TABLE `Feature` (
  `FeatureID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `FeatureName` text NOT NULL,
  PRIMARY KEY (`FeatureID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `Item` (
  `ItemID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ItemCode` bigint(20) unsigned NOT NULL,
  `IsDefault` tinyint(1) NOT NULL,
  `Type` int(11) DEFAULT NULL,
  `Brand` text COLLATE utf8mb4_unicode_ci,
  `Model` text COLLATE utf8mb4_unicode_ci,
  `Serial` text COLLATE utf8mb4_unicode_ci,
  `Status` int(11) DEFAULT NULL,
  `Owner` text COLLATE utf8mb4_unicode_ci,
  `Borrowed` tinyint(1) DEFAULT NULL,
  `Notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`ItemID`),
  KEY `Status` (`Status`),
  KEY `Type` (`Type`),
  CONSTRAINT `Item_ibfk_1` FOREIGN KEY (`Status`) REFERENCES `ItemStatus` (`StatusID`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `Item_ibfk_2` FOREIGN KEY (`Type`) REFERENCES `ItemType` (`TypeID`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `ItemFeature` (
  `FeatureID` bigint(20) unsigned NOT NULL,
  `ItemID` bigint(20) unsigned NOT NULL,
  `Value` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`FeatureID`,`ItemID`),
  KEY `ItemID` (`ItemID`),
  CONSTRAINT `ItemFeature_ibfk_1` FOREIGN KEY (`ItemID`) REFERENCES `Item` (`ItemID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ItemFeature_ibfk_3` FOREIGN KEY (`FeatureID`) REFERENCES `Feature` (`FeatureID`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `ItemLocationModification` (
  `ModificationID` bigint(20) unsigned NOT NULL,
  `ParentFrom` bigint(20) unsigned NOT NULL,
  `ParentTo` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`ModificationID`,`ParentFrom`,`ParentTo`),
  KEY `ParentFrom` (`ParentFrom`),
  KEY `ParentTo` (`ParentTo`),
  CONSTRAINT `ItemLocationModification_ibfk_2` FOREIGN KEY (`ModificationID`) REFERENCES `Modification` (`ModificationID`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `ItemLocationModification_ibfk_3` FOREIGN KEY (`ParentFrom`) REFERENCES `Item` (`ItemID`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `ItemLocationModification_ibfk_4` FOREIGN KEY (`ParentTo`) REFERENCES `Item` (`ItemID`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `ItemModification` (
  `ModificationID` bigint(20) unsigned NOT NULL,
  `ItemID` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`ModificationID`,`ItemID`),
  KEY `ItemID` (`ItemID`),
  CONSTRAINT `ItemModification_ibfk_1` FOREIGN KEY (`ModificationID`) REFERENCES `Modification` (`ModificationID`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `ItemModification_ibfk_3` FOREIGN KEY (`ItemID`) REFERENCES `Item` (`ItemID`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `ItemStatus` (
  `StatusID` int(11) NOT NULL,
  `StatusText` text NOT NULL,
  PRIMARY KEY (`StatusID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `ItemType` (
  `TypeID` int(11) NOT NULL,
  `TypeText` text NOT NULL,
  PRIMARY KEY (`TypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `Modification` (
  `ModificationID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `UserID` bigint(20) unsigned NOT NULL,
  `Date` datetime NOT NULL,
  `Notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`ModificationID`),
  KEY `UserID` (`UserID`),
  CONSTRAINT `Modification_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `User` (`UserID`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Tree` (
  `AncestorID` bigint(20) unsigned NOT NULL,
  `DescendantID` bigint(20) unsigned NOT NULL,
  `Depth` int(10) unsigned NOT NULL,
  PRIMARY KEY (`AncestorID`,`DescendantID`),
  CONSTRAINT `Tree_ibfk_1` FOREIGN KEY (`AncestorID`) REFERENCES `Item` (`ItemID`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `Tree_ibfk_2` FOREIGN KEY (`DescendantID`) REFERENCES `Item` (`ItemID`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `User` (
  `UserID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `Name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `Password` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

