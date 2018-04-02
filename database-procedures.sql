DELIMITER $$
-- Now we're getting real.
CREATE OR REPLACE FUNCTION GenerateCode(currentPrefix varchar(20))
	RETURNS varchar(190)
MODIFIES SQL DATA
	-- This means that in two identical databases, with the same values everywhere, the function produces the same
	-- results, which is useful to know for replication. Setting to deterministic also enables some optimizations,
	-- apparently. However many people on TEH INTERNETS say that anything other than a pure function is nonderministic,
	-- so who knows! If the database crashes and burns, we'll know it wasn't actually deterministic.
DETERMINISTIC
SQL SECURITY INVOKER
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

	END $$

-- Pointless procedure to set a global variable (used by the audit triggers)
CREATE OR REPLACE PROCEDURE SetUser(IN username varchar(100) CHARACTER SET 'utf8mb4')
	SQL SECURITY INVOKER
	BEGIN
		SET @taralloAuditUsername = username;
	END $$

-- Set the Item Brand, Model and Variant after an INSERT operation
CREATE OR REPLACE TRIGGER ItemBMVInsert
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

-- Set the Item Brand, Model and Variant after an UPDATE operation. If MySQL supported multiple events per trigger this would be less redundant...
CREATE OR REPLACE TRIGGER ItemBMVUpdate
	AFTER UPDATE
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

-- (Un)set the Item Brand, Model and Variant after a DELETE operation.
CREATE OR REPLACE TRIGGER ItemBMVDelete
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

-- Tree ------------------------------------------------------------------------

CREATE OR REPLACE FUNCTION GetParent(child varchar(100))
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

CREATE OR REPLACE PROCEDURE DetachSubtree(root varchar(100))
	SQL SECURITY INVOKER
	BEGIN
		DELETE Tree.* FROM Tree, Tree AS Pointless
		WHERE Tree.Descendant=Pointless.Descendant
		AND Pointless.Ancestor = root;
	END $$

-- Search ----------------------------------------------------------------------

-- Delete items from search results, when deleting from database
CREATE OR REPLACE TRIGGER ItemDeleteSearchIntegrity
	AFTER DELETE
	ON Item
	FOR EACH ROW
	BEGIN
		DELETE FROM SearchResult WHERE Code=OLD.Code;
	END $$

-- Update item codes in search results, when changing code
CREATE OR REPLACE TRIGGER ItemUpdateSearchIntegrity
	AFTER UPDATE
	ON Item
	FOR EACH ROW
	BEGIN
		IF(NEW.Code <> OLD.Code) THEN
			UPDATE SearchResult SET Code = NEW.Code
			WHERE Code = OLD.Code;
		END IF;
	END $$

-- Update results counter when deleting a search result
CREATE OR REPLACE TRIGGER SearchResultsDelete
	AFTER DELETE
	ON SearchResult
	FOR EACH ROW -- MySQL doesn't have statement-level triggers. Excellent piece of software, I must say.
	BEGIN
		CALL RefreshSearch(OLD.Search);
		UPDATE Search
		SET ResultsCount = ResultsCount - 1
		WHERE Code = OLD.Search;
	END $$

-- Update results counter when "renaming" a search (which should never happen, but still...)
CREATE OR REPLACE TRIGGER SearchResultsUpdate
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

-- Update results counter when inserting new search results
CREATE OR REPLACE TRIGGER SearchResultsInsert
	AFTER INSERT
	ON SearchResult
	FOR EACH ROW -- This may kill performance...
	BEGIN
		CALL RefreshSearch(NEW.Search);
		UPDATE Search
		SET ResultsCount = ResultsCount + 1
		WHERE Code = NEW.Search;
	END $$

-- Set default value for search expiration timestamp, just insert NULL and the trigger will do the rest
CREATE OR REPLACE TRIGGER SetRealSearchResultTimestampBecauseMySQLCant
	BEFORE INSERT
	ON Search
	FOR EACH ROW
	BEGIN
		SET NEW.Expires = TIMESTAMPADD(HOUR, 6, CURRENT_TIMESTAMP);
	END $$

-- Refresh expiration timestamp for a search. Already called by necessary triggers, call it when reading results or sorting, too.
CREATE OR REPLACE PROCEDURE RefreshSearch(id bigint UNSIGNED)
	SQL SECURITY INVOKER
	BEGIN
		UPDATE Search SET Expires = TIMESTAMPADD(HOUR, 6, CURRENT_TIMESTAMP) WHERE Code = id;
	END $$

-- Remove old searches. Search results are removed by ON DELETE CASCADE.
CREATE EVENT `SearchCleanup`
ON SCHEDULE EVERY '1' HOUR ON COMPLETION NOT PRESERVE
ENABLE DO
	DELETE
	FROM Search
	WHERE Expires < DATE_SUB(NOW(), INTERVAL 1 HOUR)$$

-- Audit -----------------------------------------------------------------------

-- If an item is permanently deleted its code may be reused, but there might still be previous audit table entires.
CREATE OR REPLACE TRIGGER RemoveOldAuditEntries
	BEFORE INSERT
	ON Item
	FOR EACH ROW
	BEGIN
		DELETE FROM Audit WHERE Code = NEW.Code OR Other = NEW.Code;
	END $$

-- Add a 'C' entry to audit table
CREATE OR REPLACE TRIGGER AuditCreateItem
	AFTER INSERT
	ON Item
	FOR EACH ROW
	BEGIN
		DECLARE parent varchar(100);

		SELECT Ancestor INTO parent
		FROM Tree
		WHERE Descendant = NEW.Code;

		INSERT INTO Audit(Code, `Change`, Other, `User`)
		VALUES(NEW.Code, 'C', NULL, @taralloAuditUsername);
	END $$

-- Add a 'R' entry to audit table
CREATE OR REPLACE TRIGGER AuditCreateItem
	AFTER UPDATE
	ON Item
	FOR EACH ROW
	BEGIN
		IF(NEW.Code <> OLD.Code) THEN
			INSERT INTO Audit(Code, `Change`, Other, `User`)
			VALUES(NEW.Code, 'R', OLD.Code, @taralloAuditUsername);
		END IF;
	END $$

-- Add a 'D' entry to audit table
CREATE OR REPLACE TRIGGER AuditDeleteItem
	AFTER UPDATE
	ON Item
	FOR EACH ROW
	BEGIN
		IF(NEW.DeletedAt IS NOT NULL) THEN
			INSERT INTO Audit(Code, `Change`, `User`)
			VALUES(NEW.Code, 'D', @taralloAuditUsername);
		END IF;
	END $$

-- Rename users in Audit table when renaming an account (to keep some kind of referential integrity)
CREATE OR REPLACE TRIGGER AuditUserRename
	AFTER UPDATE
	ON `User`
	FOR EACH ROW
	BEGIN
		IF(NEW.Name <> OLD.Name) THEN
			UPDATE Audit
			SET `User` = NEW.Name
			WHERE `User` = OLD.Name;
		END IF;
	END $$

DELIMITER ;

SET GLOBAL event_scheduler = ON;
