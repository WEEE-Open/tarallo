-- Now we're getting real.
DROP FUNCTION IF EXISTS GenerateCode;
DELIMITER $$
CREATE FUNCTION GenerateCode(currentPrefix varchar(20))
	RETURNS varchar(190)
MODIFIES SQL DATA
	-- This means that in two identical databases, with the same values everywhere, the function produces the same
	-- results, which is useful to know for replication. Setting to deterministic also enables some optimizations,
	-- apparently. However many people on TEH INTERNETS say that anything other than a pure function is not
	-- deterministic, so who knows! If the database crashes and burns, we'll know it wasn't actually deterministic.
DETERMINISTIC
SQL SECURITY INVOKER
	BEGIN
		DECLARE thePrefix varchar(20) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
		DECLARE theInteger bigint UNSIGNED;
		DECLARE duplicateExists boolean;
		DECLARE newCode varchar(190) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';

		IF NOT EXISTS(SELECT '1' FROM Prefixes WHERE Prefix = currentPrefix)
		THEN
            INSERT INTO Prefixes(Prefix, `Integer`)
		    VALUES (currentPrefix, 0);
        END IF;

		SELECT Prefix, `Integer`
		INTO thePrefix, theInteger
		FROM Prefixes
		WHERE Prefix = currentPrefix
		FOR UPDATE;

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

	END $$
DELIMITER ;

-- Set the Item Brand, Model and Variant after an INSERT operation
DROP TRIGGER IF EXISTS ItemBMVInsert;
DELIMITER $$
CREATE TRIGGER ItemBMVInsert
	AFTER INSERT
	ON ItemFeature
	FOR EACH ROW
	BEGIN
		IF(NEW.Feature = 'brand') THEN
			UPDATE Item SET Brand = NEW.ValueText WHERE Code = NEW.Code;
		ELSEIF(NEW.Feature = 'model') THEN
			UPDATE Item SET Model = NEW.ValueText WHERE Code = NEW.Code;
		ELSEIF(NEW.Feature = 'variant') THEN
			UPDATE Item SET Variant = NEW.ValueText WHERE Code = NEW.Code;
		END IF;
	END $$
DELIMITER ;

-- Set the Item Brand, Model and Variant after an UPDATE operation. If MySQL supported multiple events per trigger this would be less redundant...
DROP TRIGGER IF EXISTS ItemBMVUpdate;
DELIMITER $$
CREATE TRIGGER ItemBMVUpdate
	AFTER UPDATE
	ON ItemFeature
	FOR EACH ROW
	BEGIN
		IF(NEW.Code = OLD.Code) THEN -- This prevents infinite loop on item rename
			IF(NEW.Feature = 'brand') THEN
				UPDATE Item SET Brand = NEW.ValueText WHERE Code = NEW.Code;
			ELSEIF(NEW.Feature = 'model') THEN
				UPDATE Item SET Model = NEW.ValueText WHERE Code = NEW.Code;
			ELSEIF(NEW.Feature = 'variant') THEN
				UPDATE Item SET Variant = NEW.ValueText WHERE Code = NEW.Code;
			END IF;
		END IF;
	END $$
DELIMITER ;

-- (Un)set the Item Brand, Model and Variant after a DELETE operation.
DROP TRIGGER IF EXISTS ItemBMVDelete;
DELIMITER $$
CREATE TRIGGER ItemBMVDelete
	AFTER DELETE
	ON ItemFeature
	FOR EACH ROW
	BEGIN
		IF(OLD.Feature = 'brand') THEN
			UPDATE Item SET Brand = NULL WHERE Code = OLD.Code;
		ELSEIF(OLD.Feature = 'model') THEN
			UPDATE Item SET Model = NULL WHERE Code = OLD.Code;
		ELSEIF(OLD.Feature = 'variant') THEN
			UPDATE Item SET Variant = NULL WHERE Code = OLD.Code;
		END IF;
	END $$
DELIMITER ;


DROP TRIGGER IF EXISTS ItemSetDeleted;
DELIMITER $$
CREATE TRIGGER ItemSetDeleted
	BEFORE UPDATE
	ON Item
	FOR EACH ROW
	BEGIN
		IF(NEW.DeletedAt IS NOT NULL AND (OLD.DeletedAt IS NULL OR OLD.DeletedAt <> NEW.DeletedAt)) THEN
			IF(CountDescendants(OLD.Code) > 0) THEN
				SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot delete an item while contains other items';
			ELSE
				DELETE FROM Tree WHERE Descendant = OLD.Code AND Depth > 0;
				DELETE FROM SearchResult WHERE Item = OLD.Code;
			END IF;
		END IF;
	END $$
DELIMITER ;

DROP TRIGGER IF EXISTS ItemSetLost;
DELIMITER $$
CREATE TRIGGER ItemSetLost
	BEFORE UPDATE
	ON Item
	FOR EACH ROW
BEGIN
	IF(NEW.LostAt IS NOT NULL AND (OLD.LostAt IS NULL OR OLD.LostAt <> NEW.LostAt)) THEN
		IF(CountDescendants(OLD.Code) > 0) THEN
			SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot mark an item as lost while it contains other items';
		ELSE
			DELETE FROM Tree WHERE Descendant = OLD.Code AND Depth > 0;
		END IF;
	END IF;
END $$
DELIMITER ;

-- Tree ------------------------------------------------------------------------

DROP FUNCTION IF EXISTS GetParent;
DELIMITER $$
CREATE FUNCTION GetParent(child varchar(100))
	RETURNS varchar(100)
	READS SQL DATA
	DETERMINISTIC
	SQL SECURITY INVOKER
	BEGIN
		DECLARE found varchar(100);
		SELECT Ancestor
		INTO found
		FROM Tree
		WHERE Descendant = child
			AND Depth = 1;
		RETURN found;
	END $$
DELIMITER ;

DROP FUNCTION IF EXISTS CountDescendants;
DELIMITER $$
CREATE FUNCTION CountDescendants(item varchar(100))
	RETURNS bigint UNSIGNED
	READS SQL DATA
	DETERMINISTIC
	SQL SECURITY INVOKER
	BEGIN
		DECLARE descendants bigint UNSIGNED;
	  SELECT COUNT(*) INTO descendants
		FROM Tree
		WHERE Ancestor = item
		AND Depth > 0;
		RETURN descendants;
	END $$
DELIMITER ;

DROP PROCEDURE IF EXISTS DetachSubtree;
DELIMITER $$
CREATE PROCEDURE DetachSubtree(root varchar(100))
	SQL SECURITY INVOKER
	BEGIN
		DELETE Tree.* FROM Tree, Tree AS Pointless
		WHERE Tree.Descendant=Pointless.Descendant
		AND Pointless.Ancestor = root;
	END $$
DELIMITER ;

-- Changing codes should be easy, right? It's just a matter of UPDATE on Item.Code and watching the magnificent cascade
-- happen, right?
-- WRONG! It spits out right in my face a "Cannot add or update a child row: a foreign key constraint fails" error.
-- After deleting every single trigger, I concluded it doesn't come from one of these queries. What is it, then?
-- Tree has two foreign keys, both pointing to Item.Code. The second one, no matter if it is Descendant or Ancestor, fails.
-- The only "rational" explanation I could find is that MySQL/MariaDB forgets the second FK or tries to update it AFTER
-- changing Code, for no apparent reason.
-- But those FK do work, they prevent inserting rows pointing to nonexistant Items, I've tested that.
-- They're also both ON UPDATE CASCADE and if I remove one FK the other does in fact cascade, they both do work if the
-- other one is removed.
-- I've tried setting them even to ON DELETE CASCADE, to NO ACTION, to RESTRICT (which is the same as NO ACTION), and
-- obviously nothing worked.
-- Well, BEFORE triggers should fire before FK constraints are checked, right? If I could manually shove the right code
-- into the table... Nope, doesn't work, FK constraint fails.
-- And so here we are: a downright scary SET FOREIGN_KEY_CHECKS = 0, but it gets set back to 1, this works, FKs are
-- still there, referential integrity is preserved, the result seems correct, MariaDB doesn't complain, it's in a trigger
-- so partial failures that leave FK checks disabled shouldn't be possible, let's just hope that transactions protect
-- us from everything else.

-- Update from a future developer: this is terrible and gives all sorts of issues. We could consider to make it so that root locations simply do not have an ancestor.
DROP TRIGGER IF EXISTS CascadeItemCodeUpdateForReal;
DELIMITER $$
CREATE TRIGGER CascadeItemCodeUpdateForReal
	BEFORE UPDATE
    ON Item
    FOR EACH ROW
    BEGIN
        IF(NEW.Code <> OLD.Code) THEN
            SET FOREIGN_KEY_CHECKS = 0;
            UPDATE ItemFeature
            SET Code=NEW.Code
            WHERE Code=OLD.Code;
            UPDATE LocationAutosuggestCache
            SET Name=NEW.Code
            WHERE Name=OLD.Code;
            UPDATE Tree
            SET Ancestor=NEW.Code
            WHERE Ancestor=OLD.Code;
            UPDATE Tree
            SET Descendant=NEW.Code
            WHERE Descendant=OLD.Code;
            SET FOREIGN_KEY_CHECKS = 1;
        END IF;
    END $$
DELIMITER ;

-- Search ----------------------------------------------------------------------

-- Update results counter when deleting a search result
DROP TRIGGER IF EXISTS SearchResultsDelete;
DELIMITER $$
CREATE TRIGGER SearchResultsDelete
	AFTER DELETE
	ON SearchResult
	FOR EACH ROW -- MySQL doesn't have statement-level triggers. Excellent piece of software, I must say.
	BEGIN
		CALL RefreshSearch(OLD.Search);
		UPDATE Search
		SET ResultsCount = ResultsCount - 1
		WHERE Code = OLD.Search;
	END $$
DELIMITER ;

-- Update results counter when "renaming" a search (which should never happen, but still...)
DROP TRIGGER IF EXISTS SearchResultsUpdate;
DELIMITER $$
CREATE TRIGGER SearchResultsUpdate
	AFTER UPDATE -- Also can't specify UPDATE of what.
	ON SearchResult
	FOR EACH ROW
	BEGIN
		-- "UPDATE OF Search"
		IF (OLD.Search != NEW.Search)
		THEN
			CALL RefreshSearch(NEW.Search);
			CALL RefreshSearch(OLD.Search);
			UPDATE Search
			SET ResultsCount = ResultsCount - 1
			WHERE Code = OLD.Search;
			UPDATE Search
			SET ResultsCount = ResultsCount - 1
			WHERE Code = OLD.Search;
		END IF;
	END $$
DELIMITER ;

-- Update results counter when inserting new search results
DROP TRIGGER IF EXISTS SearchResultsInsert;
DELIMITER $$
CREATE TRIGGER SearchResultsInsert
	AFTER INSERT
	ON SearchResult
	FOR EACH ROW -- This may kill performance...
	BEGIN
		CALL RefreshSearch(NEW.Search);
		UPDATE Search
		SET ResultsCount = ResultsCount + 1
		WHERE Code = NEW.Search;
	END $$
DELIMITER ;

-- Set default value for search expiration timestamp, just insert NULL and the trigger will do the rest
DROP TRIGGER IF EXISTS SetRealSearchResultTimestampBecauseMySQLCant;
DELIMITER $$
CREATE TRIGGER SetRealSearchResultTimestampBecauseMySQLCant
	BEFORE INSERT
	ON Search
	FOR EACH ROW
	BEGIN
		SET NEW.Expires = TIMESTAMPADD(HOUR, 6, CURRENT_TIMESTAMP);
	END $$
DELIMITER ;

-- Refresh expiration timestamp for a search. Already called by necessary triggers, call it when reading results or sorting, too.
DROP PROCEDURE IF EXISTS RefreshSearch;
DELIMITER $$
CREATE PROCEDURE RefreshSearch(id bigint UNSIGNED)
	SQL SECURITY INVOKER
	BEGIN
		UPDATE Search SET Expires = TIMESTAMPADD(HOUR, 6, CURRENT_TIMESTAMP) WHERE Code = id;
	END $$
DELIMITER ;

-- Remove old searches. Search results are removed by ON DELETE CASCADE.
DROP EVENT IF EXISTS `SearchCleanup`;
DELIMITER $$
CREATE EVENT `SearchCleanup`
ON SCHEDULE EVERY '1' HOUR
ON COMPLETION PRESERVE
ENABLE DO
	DELETE
	FROM Search
	WHERE Expires < NOW() $$
DELIMITER ;

-- Audit -----------------------------------------------------------------------

-- Pointless procedure to set a global variable (used by the audit triggers)
DROP PROCEDURE IF EXISTS SetUser;
DELIMITER $$
CREATE PROCEDURE SetUser(IN username varchar(100) CHARACTER SET 'utf8mb4')
	SQL SECURITY INVOKER
	BEGIN
		SET @taralloAuditUsername = username;
	END $$
DELIMITER ;

-- Avoid duplicate C entries in Audit table
DROP TRIGGER IF EXISTS AuditDuplicateCreation;
DELIMITER $$
CREATE TRIGGER AuditDuplicateCreation
	BEFORE INSERT
	ON Audit
	FOR EACH ROW
BEGIN
	DECLARE Duplicates bigint UNSIGNED;
	IF (NEW.`Change` = 'C')
		THEN

		SELECT COUNT(*) INTO Duplicates
		FROM Audit
		WHERE Code = NEW.Code AND `Change` = 'C';

		IF (Duplicates <> 0)
			THEN
			SET @msg = CONCAT('Duplicate C Audit entry for item ', NEW.Code);
			SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = @msg;
		END IF;
	END IF;
END $$
DELIMITER ;

-- Add a 'C' entry to audit table
DROP TRIGGER IF EXISTS AuditCreateItem;
DELIMITER $$
CREATE TRIGGER AuditCreateItem
	AFTER INSERT
	ON Item
	FOR EACH ROW
	BEGIN
		INSERT INTO Audit(Code, `Change`, Other, `User`)
		VALUES(NEW.Code, 'C', NULL, @taralloAuditUsername);
	END $$
DELIMITER ;

-- Same for products
DROP TRIGGER IF EXISTS AuditCreateProduct;
DELIMITER $$
CREATE TRIGGER AuditCreateProduct
    AFTER INSERT
    ON Product
    FOR EACH ROW
BEGIN
    INSERT INTO AuditProduct(Brand, Model, Variant, `Change`, `User`)
    VALUES(NEW.Brand, NEW.Model, NEW.Variant, 'C', @taralloAuditUsername);
END $$
DELIMITER ;

-- Add an 'M' entry to audit table
DROP TRIGGER IF EXISTS AuditMoveItem;
DELIMITER $$
CREATE TRIGGER AuditMoveItem
	AFTER INSERT
	ON Tree
	FOR EACH ROW
	BEGIN
		IF(NEW.Depth = 1) THEN
			INSERT INTO Audit(Code, `Change`, Other, `User`)
			VALUES(NEW.Descendant, 'M', NEW.Ancestor, @taralloAuditUsername);
		END IF;
	END $$
DELIMITER ;

-- Add a 'R' entry to audit table and rename "other" entries
-- Also update Code because MySQL doesn't care about the foreign key
-- (Probably due to the "SET FOREIGN_KEY_CHECKS = 0;" in the other trigger,
-- but it goes back to 1, so what's going on?)
DROP TRIGGER IF EXISTS AuditRenameItem;
DELIMITER $$
CREATE TRIGGER AuditRenameItem
	AFTER UPDATE
	ON Item
	FOR EACH ROW
	BEGIN
		IF(NEW.Code <> OLD.Code) THEN
            UPDATE Audit SET `Code` = NEW.Code
            WHERE `Code` = OLD.Code;
            UPDATE Audit SET `Other` = NEW.Code
            WHERE `Other` = OLD.Code;
            INSERT INTO Audit(Code, `Change`, Other, `User`)
            VALUES(NEW.Code, 'R', OLD.Code, @taralloAuditUsername);
		END IF;
	END $$
DELIMITER ;

-- Same for products, but foreign keys are handled correctly
DROP TRIGGER IF EXISTS AuditRenameProduct;
DELIMITER $$
CREATE TRIGGER AuditRenameProduct
    AFTER UPDATE
    ON Product
    FOR EACH ROW
BEGIN
    INSERT INTO AuditProduct(Brand, Model, Variant, `Change`, `User`)
    VALUES(NEW.Brand, NEW.Model, NEW.Variant, 'R', @taralloAuditUsername);
END $$
DELIMITER ;

-- Update Item too, this cannot be a foreign key probably (values may not correspond to a product)
DROP TRIGGER IF EXISTS CascadeRenameProduct;
DELIMITER $$
CREATE TRIGGER CascadeRenameProduct
    AFTER UPDATE
    ON Product
    FOR EACH ROW
BEGIN
    IF(NEW.Brand <> OLD.Brand) THEN
        UPDATE ItemFeature
        SET `ValueText` = NEW.Brand
        WHERE Feature = 'brand'
          AND `Code` IN (
            -- Cannot SELECT Code from Item:
            -- Error in query (1442): Can't update table 'Item' in stored function/trigger because it is already used by statement which invoked this stored function/trigger
            SELECT f1.`Code`
            FROM ItemFeature AS f1, ItemFeature AS f2, ItemFeature AS f3
            WHERE f1.Code = f2.Code AND f2.Code = f3.Code
              AND f1.Feature = 'brand' AND f2.Feature = 'model' AND f3.Feature = 'variant'
              AND f1.ValueText = OLD.Brand
              AND f2.ValueText = OLD.Model
              AND f3.ValueText = OLD.Variant
        );
    END IF;
    IF(NEW.Model <> OLD.Model) THEN
        UPDATE ItemFeature
        SET `ValueText` = NEW.Model
        WHERE Feature = 'model'
          AND `Code` IN (
            -- Cannot SELECT Code from Item:
            -- Error in query (1442): Can't update table 'Item' in stored function/trigger because it is already used by statement which invoked this stored function/trigger
            SELECT f1.`Code`
            FROM ItemFeature AS f1, ItemFeature AS f2, ItemFeature AS f3
            WHERE f1.Code = f2.Code AND f2.Code = f3.Code
              AND f1.Feature = 'brand' AND f2.Feature = 'model' AND f3.Feature = 'variant'
              AND f1.ValueText = OLD.Brand
              AND f2.ValueText = OLD.Model
              AND f3.ValueText = OLD.Variant
        );
    END IF;
    IF(NEW.Variant <> OLD.Variant) THEN
        UPDATE ItemFeature
        SET `ValueText` = NEW.Variant
        WHERE Feature = 'variant'
          AND `Code` IN (
            -- Cannot SELECT Code from Item:
            -- Error in query (1442): Can't update table 'Item' in stored function/trigger because it is already used by statement which invoked this stored function/trigger
            SELECT f1.`Code`
            FROM ItemFeature AS f1, ItemFeature AS f2, ItemFeature AS f3
            WHERE f1.Code = f2.Code AND f2.Code = f3.Code
              AND f1.Feature = 'brand' AND f2.Feature = 'model' AND f3.Feature = 'variant'
              AND f1.ValueText = OLD.Brand
              AND f2.ValueText = OLD.Model
              AND f3.ValueText = OLD.Variant
        );
    END IF;
END $$
DELIMITER ;

-- Add a 'D' entry to audit table
DROP TRIGGER IF EXISTS AuditDeleteItem;
DELIMITER $$
CREATE TRIGGER AuditDeleteItem
	AFTER UPDATE
	ON Item
	FOR EACH ROW
	BEGIN
		IF(NEW.DeletedAt IS NOT NULL AND (OLD.DeletedAt IS NULL OR OLD.DeletedAt <> NEW.DeletedAt)) THEN
			INSERT INTO Audit(Code, `Change`, `User`)
			VALUES(NEW.Code, 'D', @taralloAuditUsername);
		END IF;
	END $$
DELIMITER ;

-- Add a 'L' entry to audit table
DROP TRIGGER IF EXISTS AuditLostItem;
DELIMITER $$
CREATE TRIGGER AuditLostItem
  AFTER UPDATE
  ON Item
  FOR EACH ROW
BEGIN
  IF(NEW.LostAt IS NOT NULL AND (OLD.LostAt IS NULL OR OLD.LostAt <> NEW.LostAt)) THEN
    INSERT INTO Audit(Code, `Change`, `User`)
    VALUES(NEW.Code, 'L', @taralloAuditUsername);
  END IF;
END $$
DELIMITER ;

-- Features --------------------------------------------------------------------

-- Painless conversion between integer and double features. Maybe.
DROP TRIGGER IF EXISTS ChangeFeatureType;
DELIMITER $$
CREATE TRIGGER ChangeFeatureType
	AFTER UPDATE
	ON Feature
	FOR EACH ROW
	BEGIN
		IF(NEW.Feature = OLD.Feature) THEN
			IF(NEW.Type = 3 AND OLD.Type = 1) THEN
				UPDATE ItemFeature
				SET ValueDouble = `Value`, `Value` = NULL
				WHERE Feature = NEW.Feature;
			ELSEIF(NEW.Type = 1 AND OLD.Type = 3) THEN
				UPDATE ItemFeature
				SET `Value` = ValueDouble, ValueDouble = NULL
				WHERE Feature = NEW.Feature;
			END IF;
		END IF;
	END $$
DELIMITER ;

-- Sessions --------------------------------------------------------------------

DROP EVENT IF EXISTS `SessionsCleanup`;
DELIMITER $$
CREATE EVENT `SessionsCleanup`
    ON SCHEDULE EVERY '6' HOUR
    ON COMPLETION PRESERVE
    ENABLE DO
    DELETE
    FROM `Session`
    WHERE LastAccess < TIMESTAMPADD(DAY, -2, NOW()) $$
DELIMITER ;

DROP EVENT IF EXISTS `TokensCleanup`;
DELIMITER $$
CREATE EVENT `TokensCleanup`
    ON SCHEDULE EVERY '1' DAY
    ON COMPLETION PRESERVE
    ENABLE DO
    DELETE
    FROM `SessionToken`
    WHERE LastAccess < TIMESTAMPADD(MONTH, -6, NOW()) $$
DELIMITER ;

-- Product and Item features duplicates deleter --------------------------------

-- Delete rows from ItemFeature that are exact duplicates (same feature and value) of ProductFeature rows, for the same item.
-- Adding duplicate rows to ItemFeature is handled elsewhere (i.e. in the code), but creating a new product may yield such rows.
-- Deleting these duplicates immediately may delete rows that intentionally override a feature value, if the incorrect value is applied to the product.
-- To minimize this problem, we will delete such duplicates every 24 hours, only if both products and items haven't been modified in the last 24 hours.
-- So duplicate features are actually deleted between 24 and 48 hours after they've been created.
DROP EVENT IF EXISTS `DuplicateItemProductFeaturesCleanup`;
DELIMITER $$
CREATE EVENT `DuplicateItemProductFeaturesCleanup`
    ON SCHEDULE EVERY '2' HOUR STARTS '2020-01-01 00:30:00'
    ON COMPLETION PRESERVE
    ENABLE DO
    DELETE ItemFeature.*
    FROM Item
    NATURAL JOIN ProductFeature
    JOIN ItemFeature ON Item.Code = ItemFeature.Code AND ProductFeature.Feature = ItemFeature.Feature
    WHERE
    COALESCE(ProductFeature.Value, ProductFeature.ValueEnum, ProductFeature.ValueText, ProductFeature.ValueDouble) = COALESCE(ItemFeature.Value, ItemFeature.ValueEnum, ItemFeature.ValueText, ItemFeature.ValueDouble)
    AND NOT EXISTS(
        SELECT Audit.Code
        FROM Audit
        WHERE Item.Code = Audit.Code
          AND Audit.Change IN ('C', 'U')
          AND TIMESTAMPDIFF(HOUR, Audit.Time, NOW()) >= 2
    )
    AND NOT EXISTS(
        SELECT AuditProduct.Brand, AuditProduct.Model, AuditProduct.Variant
        FROM AuditProduct
        WHERE Item.Brand = AuditProduct.Brand
          AND Item.Model = AuditProduct.Model
          AND Item.Variant = AuditProduct.Variant
          AND AuditProduct.Change IN ('C', 'U')
          AND TIMESTAMPDIFF(HOUR, AuditProduct.Time, NOW()) >= 2
    )
$$
DELIMITER ;

DROP EVENT IF EXISTS `DonationItem_ai`;
DELIMITER $$
CREATE TRIGGER `DonationItem_ai` AFTER INSERT ON `DonationItem` FOR EACH ROW
BEGIN
    DECLARE TaskId BIGINT(20) UNSIGNED;
    DECLARE done INT DEFAULT FALSE;
    DECLARE cur CURSOR FOR SELECT Id FROM DonationTasks WHERE DonationId = NEW.Donation AND ItemType = (SELECT IFNULL(IFNULL(ItemFeature.ValueEnum, ProductFeature.ValueEnum), 'other')
        FROM Item
        LEFT JOIN ItemFeature ON Item.Code = ItemFeature.Code AND ItemFeature.Feature = 'type'
        LEFT JOIN ProductFeature ON Item.Brand = ProductFeature.Brand AND Item.Model = ProductFeature.Model AND Item.Variant = ProductFeature.Variant AND ProductFeature.Feature = 'type'
        WHERE Item.Code = NEW.Code);
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    insert_loop: LOOP
        FETCH cur INTO TaskId;
        IF done THEN
            LEAVE insert_loop;
        END IF;
        
        INSERT INTO DonationTasksProgress (DonationId, `TaskId`, ItemCode, Completed) VALUES (NEW.Donation, TaskId, NEW.Code, 0);
    END LOOP;
    
    CLOSE cur;
END $$
DELIMITER ;

DROP EVENT IF EXISTS `DonationTasks_ai`;
DELIMITER $$
CREATE TRIGGER `DonationTasks_ai` AFTER INSERT ON `DonationTasks` FOR EACH ROW
BEGIN
    DECLARE ItemId varchar(255);
    DECLARE done INT DEFAULT FALSE;
    DECLARE cur CURSOR FOR SELECT DonationItem.Code
        FROM DonationItem
        LEFT JOIN Item ON DonationItem.Code = Item.Code
        LEFT JOIN ItemFeature ON DonationItem.Code = ItemFeature.Code AND ItemFeature.Feature = 'type'
        LEFT JOIN ProductFeature ON Item.Brand = ProductFeature.Brand AND Item.Model = ProductFeature.Model AND Item.Variant = ProductFeature.Variant AND ProductFeature.Feature = 'type'
        WHERE DonationItem.Donation = NEW.DonationId AND IFNULL(IFNULL(ItemFeature.ValueEnum, ProductFeature.ValueEnum), 'other') = NEW.ItemType;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    insert_loop: LOOP
        FETCH cur INTO ItemId;
        IF done THEN
            LEAVE insert_loop;
        END IF;
        
        INSERT INTO DonationTasksProgress (DonationId, `TaskId`, ItemCode, Completed) VALUES (NEW.DonationId, NEW.Id, ItemId, 0);
    END LOOP;
    
    CLOSE cur;
END $$
DELIMITER ;

DROP EVENT IF EXISTS `LocationAutosuggestGenerateCache`;
DELIMITER $$
CREATE TRIGGER `LocationAutosuggestGenerateCache` AFTER INSERT ON `ItemFeature`
FOR EACH ROW
BEGIN
	IF NEW.Feature = 'type' AND NEW.ValueEnum = 'location' THEN
		INSERT INTO `LocationAutosuggestCache` (`Name`, `Color`) VALUES (NEW.Code, (SELECT ValueEnum FROM `ItemFeature` WHERE Feature = 'color' AND Code = NEW.Code LIMIT 1));
	ELSEIF NEW.Feature = 'color' AND (SELECT COUNT(*) FROM `LocationAutosuggestCache` WHERE Name = NEW.Code) > 0 THEN
		UPDATE `LocationAutosuggestCache`
		SET Color = NEW.ValueEnum
		WHERE Name = NEW.Code;
	END IF;
END $$
DELIMITER ;

DROP EVENT IF EXISTS `LocationAutosuggestUpdateCache`;
DELIMITER $$
CREATE TRIGGER `LocationAutosuggestUpdateCache` AFTER UPDATE ON `ItemFeature`
FOR EACH ROW
BEGIN
	IF OLD.Feature = 'type' AND OLD.ValueEnum = 'location' AND (NEW.Feature != 'type' OR NEW.ValueEnum != 'location') THEN
		DELETE FROM `LocationAutosuggestCache` WHERE Name = OLD.Code;
	ELSEIF (OLD.Feature != 'type' OR OLD.ValueEnum != 'location') AND NEW.Feature = 'type' AND NEW.ValueEnum = 'location' THEN
		INSERT INTO `LocationAutosuggestCache` (`Name`, `Color`)
		SELECT NEW.Code, ValueEnum
		FROM `ItemFeature`
		WHERE Feature = 'color' AND Code = NEW.Code
		LIMIT 1;
	ELSEIF (OLD.Feature = 'color' OR NEW.Feature = 'color') AND (SELECT COUNT(*) FROM `LocationAutosuggestCache` WHERE Name = NEW.Code) > 0 THEN
		UPDATE `LocationAutosuggestCache`
		SET Color = (SELECT ValueEnum FROM `ItemFeature` WHERE Feature = 'color' AND Code = NEW.Code LIMIT 1)
		WHERE Name = NEW.Code;
	END IF;
END $$
DELIMITER ;


DROP EVENT IF EXISTS `LocationAutosuggestDeleteCache`;
DELIMITER $$
CREATE TRIGGER `LocationAutosuggestDeleteCache` AFTER DELETE ON `ItemFeature`
FOR EACH ROW
BEGIN
	IF OLD.Feature = 'type' AND OLD.ValueEnum = 'location' THEN
		DELETE FROM `LocationAutosuggestCache` WHERE Name = OLD.Code;
	ELSEIF OLD.Feature = 'color' THEN
		UPDATE `LocationAutosuggestCache`
		SET Color = NULL
		WHERE Name = OLD.Code;
	END IF;
END $$
DELIMITER ;

-- SET GLOBAL ------------------------------------------------------------------

SET GLOBAL event_scheduler = ON;
