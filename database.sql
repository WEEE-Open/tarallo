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
	UNIQUE KEY (`Code`),
	INDEX (`Code`),
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
	`Value` bigint(20) UNSIGNED DEFAULT NULL,
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
		ON UPDATE CASCADE
	# TODO: replace with a trigger
	#CHECK ((`Value` IS NOT NULL AND `ValueText` IS NULL AND `ValueDouble` IS NULL)
	#	OR (`Value` IS NULL AND `ValueText` IS NOT NULL AND `ValueDouble` IS NULL)
	#	OR (`Value` IS NULL AND `ValueText` IS NULL AND `ValueDouble` IS NOT NULL))
)
	ENGINE = InnoDB
	DEFAULT CHARSET = utf8mb4
	COLLATE = utf8mb4_unicode_ci;

-- To be added again in future and managed via triggers
# CREATE TABLE `ItemLocationModification` (
# 	`ModificationID` bigint(20) unsigned NOT NULL,
# 	`ItemID` bigint(20) unsigned NOT NULL,
# 	-- parentFrom is useless if adding an item also creates a new row here: first row is the original parent...
# 	`ParentTo` bigint(20) unsigned NOT NULL,
# 	PRIMARY KEY (`ModificationID`, `ItemID`),
# 	KEY (`ParentTo`),
# 	CONSTRAINT FOREIGN KEY (`ModificationID`) REFERENCES `Modification` (`ModificationID`)
# 		ON DELETE NO ACTION
# 		ON UPDATE CASCADE,
# 	CONSTRAINT FOREIGN KEY (`ItemID`) REFERENCES `Item` (`ItemID`)
# 		ON DELETE NO ACTION
# 		ON UPDATE CASCADE,
# 	CONSTRAINT FOREIGN KEY (`ParentTo`) REFERENCES `Item` (`ItemID`)
# 		ON DELETE NO ACTION
# 		ON UPDATE CASCADE
# )
# 	ENGINE = InnoDB
# 	DEFAULT CHARSET = utf8mb4
# 	COLLATE = utf8mb4_unicode_ci;
#
# CREATE TABLE `ItemModificationDelete` (
# 	`ModificationID` bigint(20) unsigned NOT NULL,
# 	`ItemID` bigint(20) unsigned NOT NULL,
# 	PRIMARY KEY (`ModificationID`, `ItemID`),
# 	KEY (`ItemID`),
# 	CONSTRAINT FOREIGN KEY (`ModificationID`) REFERENCES `Modification` (`ModificationID`)
# 		ON DELETE NO ACTION
# 		ON UPDATE CASCADE,
# 	CONSTRAINT FOREIGN KEY (`ItemID`) REFERENCES `Item` (`ItemID`)
# 		ON DELETE NO ACTION
# 		ON UPDATE CASCADE
# )
# 	ENGINE = InnoDB
# 	DEFAULT CHARSET = utf8mb4
# 	COLLATE = utf8mb4_unicode_ci;
#
# CREATE TABLE `ItemModification` (
# 	`ModificationID` bigint(20) unsigned NOT NULL,
# 	`ItemID` bigint(20) unsigned NOT NULL,
# 	PRIMARY KEY (`ModificationID`, `ItemID`),
# 	KEY (`ItemID`),
# 	CONSTRAINT FOREIGN KEY (`ModificationID`) REFERENCES `Modification` (`ModificationID`)
# 		ON DELETE NO ACTION
# 		ON UPDATE CASCADE,
# 	CONSTRAINT FOREIGN KEY (`ItemID`) REFERENCES `Item` (`ItemID`)
# 		ON DELETE NO ACTION
# 		ON UPDATE CASCADE
# )
# 	ENGINE = InnoDB
# 	DEFAULT CHARSET = utf8mb4
# 	COLLATE = utf8mb4_unicode_ci;
#
# CREATE TABLE `Modification` (
# 	`ModificationID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
# 	`UserID` bigint(20) unsigned NOT NULL,
# 	`Date` bigint(20) unsigned NOT NULL,
# 	`Notes` text COLLATE utf8mb4_unicode_ci,
# 	PRIMARY KEY (`ModificationID`),
# 	KEY (`UserID`),
# 	CONSTRAINT FOREIGN KEY (`UserID`) REFERENCES `User` (`UserID`)
# 		ON DELETE NO ACTION
# 		ON UPDATE CASCADE
# )
# 	ENGINE = InnoDB
# 	DEFAULT CHARSET = utf8mb4
# 	COLLATE = utf8mb4_unicode_ci;

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
	`SessionExpiry` bigint UNSIGNED NOT NULL DEFAULT 0, -- timestamp NOT NULL DEFAULT 0 ON UPDATE CURRENT_TIMESTAMP + INTERVAL 6 HOUR,
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
	-- Too good to be true, PHPStorm even considered it valid SQL in MySQL dialect...
	`Expires` bigint UNSIGNED NOT NULL DEFAULT 0, -- TIMESTAMPADD(HOUR, 6, CURRENT_TIMESTAMP),
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

-- Now we're getting real.
DELIMITER $$

CREATE FUNCTION GenerateCode(currentPrefix varchar(20))
	RETURNS varchar(190)
MODIFIES SQL DATA
	-- This means that in two identical databases, with the same values everywhere, the function produces the same
	-- results, which is useful to know for replication. Setting to deterministic also enables some optimizations,
	-- apparently. However many people on TEH INTERNETS say that anything other than a pure function is nonderministic,
	-- so who knows! If the database crashes and burns, we'll know it wasn't actually deterministic.
DETERMINISTIC
	BEGIN
		DECLARE thePrefix varchar(20) CHARACTER SET 'utf8mb4'
		COLLATE 'utf8mb4_unicode_ci';
		DECLARE theInteger bigint UNSIGNED;
		DECLARE duplicateExists boolean;
		DECLARE newCode varchar(190) CHARACTER SET 'utf8mb4'
		COLLATE 'utf8mb4_unicode_ci';

		SELECT Prefix, `Integer`
		INTO thePrefix, theInteger
		FROM Prefixes
		WHERE Prefix = currentPrefix;

		IF (thePrefix IS NOT NULL)
		THEN
			REPEAT
				SET theInteger = theInteger + 1;
				SET NewCode = CONCAT(thePrefix, CAST(theInteger AS char(20)));

				SELECT IF(COUNT(*) > 0, TRUE, FALSE)
				INTO duplicateExists
				FROM Item
				WHERE Code = NewCode;
			UNTIL duplicateExists = FALSE
			END REPEAT;

			UPDATE Prefixes
			SET `Integer` = theInteger
			WHERE Prefix = thePrefix;

			RETURN newCode;
		ELSE
			RETURN NULL;
		END IF;

	END$$

DELIMITER ;
DELIMITER $$

-- TODO: extend and use to log in?
CREATE PROCEDURE SetUser(IN username varchar(100) CHARACTER SET 'utf8mb4')
	BEGIN
		SET @taralloAuditUsername = username;
	END$$

CREATE FUNCTION GetParent(child varchar(100))
	RETURNS varchar(100)
READS SQL DATA
DETERMINISTIC
	BEGIN
		DECLARE found varchar(100);
		SELECT Ancestor
		INTO found
		FROM Tree
		WHERE Descendant = child
			AND Depth = 1;
		RETURN found;
	END$$

-- TODO: update expiration date, too
CREATE TRIGGER SearchResultsDelete
	AFTER DELETE
	ON SearchResult
	FOR EACH ROW -- MySQL doesn't have statement-level triggers. Excellent piece of software, I must say.
	BEGIN
		UPDATE Search
		SET ResultsCount = ResultsCount - 1
		WHERE Code = OLD.Search;
	END $$

-- These triggers can't be added, BTW, because UPDATE and DELETE are the same action for MySQL apparently and it can't have two triggers for the same action
CREATE TRIGGER SearchResultsUpdate
	AFTER UPDATE -- Also can't specify UPDATE of what.
	ON SearchResult
	FOR EACH ROW
	BEGIN
		-- "UPDATE OF Search"
		IF (OLD.Search != NEW.Search)
		THEN
			UPDATE Search
			SET ResultsCount = ResultsCount - 1
			WHERE Code = OLD.Search;
			UPDATE Search
			SET ResultsCount = ResultsCount - 1
			WHERE Code = OLD.Search;
		END IF;
	END $$

CREATE TRIGGER SearchResultsInsert
	AFTER INSERT
	ON SearchResult
	FOR EACH ROW -- This may kill performance...
	BEGIN
		UPDATE Search
		SET ResultsCount = ResultsCount + 1
		WHERE Code = NEW.Search;
	END $$

CREATE TRIGGER SetRealSearchResultTimestampBecauseMySQLCant
	BEFORE INSERT
	ON Search
	FOR EACH ROW
	BEGIN
		SET NEW.Expires = TIMESTAMPADD(HOUR, 6, CURRENT_TIMESTAMP);
	END $$

CREATE TRIGGER SetRealSearchResultTimestampBecauseMySQLCantAgain
	BEFORE UPDATE
	ON Search
	FOR EACH ROW
	BEGIN
		SET NEW.Expires = TIMESTAMPADD(HOUR, 6, CURRENT_TIMESTAMP);
	END $$

DELIMITER ;
