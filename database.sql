-- Adminer 4.2.5 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

CREATE DATABASE `tarallo` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `tarallo`;

SET NAMES utf8mb4;

CREATE TABLE `Feature` (
  `FeatureID` bigint(20) unsigned NOT NULL,
  `ItemID` bigint(20) unsigned NOT NULL,
  `Value` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`FeatureID`,`ItemID`)
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
  PRIMARY KEY (`ItemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `ItemModification` (
  `ModificationID` bigint(20) unsigned NOT NULL,
  `ItemID` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`ModificationID`,`ItemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `ItemPositionModification` (
  `ModificationID` bigint(20) unsigned NOT NULL,
  `ParentFrom` bigint(20) unsigned NOT NULL,
  `ParentTo` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`ModificationID`,`ParentFrom`,`ParentTo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `ItemSticker` (
  `ItemID` bigint(20) unsigned NOT NULL,
  `Type` int(11) NOT NULL,
  `Color` int(11) DEFAULT NULL,
  `Code` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `Returned` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `Modification` (
  `ModificationID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `UserID` bigint(20) unsigned NOT NULL,
  `Date` datetime NOT NULL,
  `Notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`ModificationID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `User` (
  `UserID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `Name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `Password` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

