<?php

namespace WEEEOpen\Tarallo\Database;

class Updater extends DAO
{
	private $schemaVersion;
	private $dataVersion;

	public function __construct(Database $db, $callback)
	{
		parent::__construct($db, $callback);
		try {
			$result = $this->getPDO()->query("SELECT `Value` FROM Configuration WHERE `Key` = 'SchemaVersion'");
			$this->schemaVersion = (int) $result->fetchColumn();
		} catch (\PDOException $e) {
			if ($e->getCode() === '42S02') {
				$this->schemaVersion = 0;
				$this->dataVersion = 0;
				return;
			} else {
				throw $e;
			}
		}
		$result = $this->getPDO()->query("SELECT `Value` FROM Configuration WHERE `Key` = 'DataVersion'");
		$this->dataVersion = (int) $result->fetchColumn();

		echo 'Start from schema version ' . $this->schemaVersion . ' and data version ' . $this->dataVersion . PHP_EOL;
	}

	public function updateTo(int $schema, int $data)
	{
		$this->updateSchema($schema);
		$this->updateData($data);
	}

	private function updateSchema(int $schema)
	{
		if ($this->schemaVersion === $schema) {
			return;
		} elseif ($this->schemaVersion > $schema) {
			throw new \InvalidArgumentException("Trying to downgrade schema from $this->schemaVersion to $schema");
		}
		// $schema is now > $this->schemaVersion
		while ($this->schemaVersion < $schema) {
			switch ($this->schemaVersion) {
				case 0:
					$this->exec(
						<<<EOQ
CREATE TABLE `Configuration` (
  `Key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Value` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
	PRIMARY KEY (`Key`)
)
	ENGINE = InnoDB
	DEFAULT CHARSET = utf8mb4
	COLLATE = utf8mb4_unicode_ci;
EOQ
					);
					$this->exec("INSERT INTO `Configuration` (`Key`, `Value`) VALUES ('SchemaVersion', 1)");
					$this->exec("INSERT INTO `Configuration` (`Key`, `Value`) VALUES ('DataVersion', 0)");
					break;
				case 1:
					$this->exec("ALTER TABLE Item ADD `LostAt` timestamp NULL DEFAULT NULL");
					$this->exec("CREATE INDEX LostAt ON Item (LostAt)");
					$this->exec("DROP TRIGGER IF EXISTS ItemSetDeleted");
					$this->exec("DROP FUNCTION IF EXISTS CountDescendants");
					$this->exec(
						<<<EOQ
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
	END
EOQ
					);
					$this->exec(
						<<<EOQ
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
	END
EOQ
					);
					$this->exec("DROP TRIGGER IF EXISTS ItemSetLost");
					$this->exec(
						<<<EOQ
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
END
EOQ
					);
					break;
				case 2:
					$this->exec("DROP TRIGGER IF EXISTS AuditLostItem");
					$this->exec(
						<<<EOQ
CREATE TRIGGER AuditLostItem
  AFTER UPDATE
  ON Item
  FOR EACH ROW
BEGIN
  IF(NEW.LostAt IS NOT NULL AND (OLD.LostAt IS NULL OR OLD.LostAt <> NEW.LostAt)) THEN
    INSERT INTO Audit(Code, `Change`, `User`)
    VALUES(NEW.Code, 'L', @taralloAuditUsername);
  END IF;
END
EOQ
					);
					// Generated name for CHECK constraints, nice
					$this->exec(
						"# noinspection SqlResolve
ALTER TABLE Audit DROP CONSTRAINT CONSTRAINT_1"
					);
					$this->exec(
						<<<EOQ
# noinspection SqlResolve
ALTER TABLE Audit
ADD CONSTRAINT check_change
	CHECK (
		(`Change` IN ('M', 'R') AND `Other` IS NOT NULL) OR
		(`Change` IN ('C', 'U', 'D', 'L') AND `Other` IS NULL)
	)
EOQ
					);
					break;
				case 3:
					$this->exec(
						'ALTER TABLE `Audit` CHANGE `Time` `Time` timestamp(6) NOT NULL DEFAULT now(6) AFTER `Other`;'
					);
					break;
				case 4:
					$this->exec('ALTER TABLE `Item` CHANGE `LostAt` `LostAt` timestamp(6) NULL');
					$this->exec('ALTER TABLE `Item` CHANGE `DeletedAt` `DeletedAt` timestamp(6) NULL');
					// "Can't update table 'Audit' in stored function/trigger because it is already used by statement
					// which invoked this stored function/trigger" => Drop trigger and recreate it later
					$this->exec("DROP TRIGGER IF EXISTS AuditLostItem");
					$this->exec(<<<EOQ
					UPDATE Item
					SET LostAt = (
						SELECT TIMESTAMPADD(SECOND, 1, MAX(`Time`))
						FROM Audit
						WHERE Code = Item.Code
					)
					WHERE Code IN (
						SELECT Code
						FROM ItemFeature
						WHERE Feature = 'check'
						AND ValueEnum = 'lost'
					)
EOQ
					);
					// This was a lot more painful to write than it should have been.
					// Random searches on the Internet still bring up old Stack Overflow questions from a time when
					// window functions were not widely available...
					// Anyway: add missing L Audit entries due to missing trigger
					$this->exec(<<<EOQ
					INSERT INTO Audit(Code, `Change`, Other, Time, User) 
					SELECT Updated.`Code`, 'L', NULL, TIMESTAMPADD(SECOND, 1, Updated.`Time`), Updated.User FROM (
					SELECT `Code`, `Time`, `User`, ROW_NUMBER() OVER (PARTITION BY `Code` ORDER BY `Time` DESC) AS RN
					FROM Audit
					WHERE Code IN (
						SELECT Code
						FROM ItemFeature
						WHERE Feature = 'check'
						AND ValueEnum = 'lost'
					)
					ORDER BY Code, RN
					) Updated
					WHERE RN = 1
EOQ
					);
					// Now, this is only useful in production right now... But may have ended up in some test
					// databases, so...
					// Mark as lost all the items in the "Lost" location, if it exists.
					$intermediate = $this->getPDO()->query("SELECT Code FROM Item WHERE Code = 'Lost'");
					if ($intermediate->rowCount() > 0) {
						// Close that cursor so we can do other stuff
						$intermediate->closeCursor();
						// Trigger will create and Audit entry, this requires an username... will fix them manually
						// in production, they're not important in development.
						$this->exec(/** @lang MariaDB */ "CALL SetUser('IMPORT')");
						// Also, there still trigger preventing this from being a single query...
						$intermediate2 = $this->getPDO()->query("SELECT DISTINCT Descendant FROM Tree WHERE Ancestor = 'Lost' AND Depth > 0");
						$fetched = $intermediate2->fetchAll(\PDO::FETCH_COLUMN);
						foreach ($fetched as $item) {
							// Again, there are a billion triggers preventing the simplest of queries, so we have to
							// make some inane byzantine workarounds, I don't even know anymore, it's 1.30 AM I just
							// want to insert these damn 4 rows into the damn table and be done with it, please,
							// pleeeease...
							// Also: unsanitized $item going into the query. Congratulations, you have found no SQL
							// injection at all since these are heavily validated everywhere and there's no item
							// named "); DROP DATABASE -- " in production...
							// Manual audit entries
							$this->exec(<<<EOQ
								INSERT INTO Audit(Code, `Change`, Other, Time, User) 
								SELECT Updated.`Code`, 'L', NULL, TIMESTAMPADD(SECOND, 1, Updated.`Time`), Updated.User FROM (
								SELECT `Code`, `Time`, `User`, ROW_NUMBER() OVER (PARTITION BY `Code` ORDER BY `Time` DESC) AS RN
								FROM Audit
								WHERE Code = '$item'
								) Updated
								WHERE RN = 1
EOQ
							);
							// Finally lose the item
							$this->exec("
							UPDATE Item
							SET LostAt = (SELECT MAX(`Time`) FROM `Audit` WHERE `Code` = '$item')
							WHERE Code = '$item'");
							unset($item);
						}
						$intermediate2->closeCursor();
						unset($intermediate2);
						unset($fetched);
						// Remove the location
						//$this->exec("DELETE FROM Item WHERE Code = 'Lost'");
						// Doesn't work due to usual limitations that make ON UPDATE CASCADE become ON UPDATE
						// RESTRICT AND PREVENT ANYTHING NO ACTION STOP HALT AND CATCH FIRE
					} else {
						$intermediate->closeCursor();
					}
					unset($intermediate);
					// The trigger returns
					$this->exec(
						<<<EOQ
CREATE TRIGGER AuditLostItem
  AFTER UPDATE
  ON Item
  FOR EACH ROW
BEGIN
  IF(NEW.LostAt IS NOT NULL AND (OLD.LostAt IS NULL OR OLD.LostAt <> NEW.LostAt)) THEN
    INSERT INTO Audit(Code, `Change`, `User`)
    VALUES(NEW.Code, 'L', @taralloAuditUsername);
  END IF;
END
EOQ
					);
					break;
				case 5:
					$this->exec('DROP TRIGGER AuditUserRename;');
					$this->exec('CREATE TABLE Session
(
    Session VARCHAR(100) NOT NULL,
    Data TEXT,
    Redirect TEXT,
    PRIMARY KEY (`Session`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;');
					$this->exec('ALTER TABLE `Search` DROP FOREIGN KEY `Search_ibfk_1`');
					$this->exec('DROP TABLE User;');
					break;
				case 6:
					$this->exec('ALTER TABLE `Session` ADD COLUMN `LastAccess` TIMESTAMP NOT NULL DEFAULT current_timestamp AFTER Redirect;');
					$this->exec('CREATE TABLE `SessionToken`
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
  COLLATE = utf8mb4_unicode_ci;');
					break;
				case 7:
					$this->exec('DROP EVENT IF EXISTS `SessionsCleanup`');
					$this->exec('CREATE EVENT `SessionsCleanup`
    ON SCHEDULE EVERY \'6\' HOUR
    ON COMPLETION PRESERVE
    ENABLE DO
    DELETE
    FROM `Session`
    WHERE LastAccess < TIMESTAMPADD(DAY, -2, NOW());
');
					$this->exec('DROP EVENT IF EXISTS `TokensCleanup`;');
					$this->exec('CREATE EVENT `TokensCleanup`
    ON SCHEDULE EVERY \'1\' DAY
    ON COMPLETION PRESERVE
    ENABLE DO
    DELETE
    FROM `SessionToken`
    WHERE LastAccess < TIMESTAMPADD(MONTH, -6, NOW());
');
					break;
				case 8:
					$this->exec("    
CREATE TABLE `Product`
(
    -- Max length would be approx. 190 * 4 bytes = 760, less than the apparently random limit of 767 bytes.
    `Brand` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Model` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Variant` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`Brand`, `Model`, `Variant`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;");
					$this->exec("
						CREATE TABLE `ProductFeature`
(
`Brand` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Model` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Variant` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `Feature` VARCHAR(40) COLLATE utf8mb4_bin NOT NULL,
    `Value` BIGINT UNSIGNED DEFAULT NULL,
    `ValueEnum` VARCHAR(40) COLLATE utf8mb4_bin DEFAULT NULL,
    `ValueText` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `ValueDouble` DOUBLE DEFAULT NULL,
    PRIMARY KEY (`Brand`, `Model`, `Variant`, `Feature`),
    INDEX `Value` (`Value`),
    INDEX `ValueDouble` (`ValueDouble`),
    CONSTRAINT fk_product_features FOREIGN KEY (`Brand`, `Model`, `Variant`) REFERENCES `Product` (`Brand`, `Model`, `Variant`)
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
					");
					break;
				case 9:
					$this->exec("CREATE VIEW ProductItemFeature AS
SELECT  I.CODE AS Code, PF.BRAND AS Brand, PF.MODEL AS Model, PF.VARIANT AS Variant,
        PF.FEATURE AS Feature_Prod, PF.VALUE AS Value_Prod, PF.VALUEENUM AS Valueenum_Prod,
        PF.VALUETEXT AS Valuetext_Prod, PF.VALUEDOUBLE AS Valuedouble_Prod,
        I.VARIANT AS Variant_Item, I.TOKEN AS Token, I.DELETEDAT AS DeletedAt, I.LOSTAT AS LostAt,
        IFT.FEATURE AS Feature_Item, IFT.VALUE AS Value_Item, IFT.VALUEENUM AS Valueenum_Item,
        IFT.VALUETEXT AS Valuetext_Item, IFT.VALUEDOUBLE AS Valuedouble_Item
FROM    ProductFeature PF, Item I, ItemFeature IFT
WHERE   I.Code = IFT.Code AND
        PF.Model = I.Model AND
        PF.Brand = I.Brand AND
        PF.Variant = I.Variant AND
        IFT.Feature = PF.Feature");
					break;
				case 10:
					$this->exec("DROP VIEW ProductItemFeature"); // Possibly unnecessary
					$this->exec("CREATE VIEW ProductItemFeature AS
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
GROUP BY Code, Feature;");
					$this->exec("CREATE VIEW ProductItemFeatureUnified AS
SELECT Code,
       Feature,
    COALESCE(Value_Item, Value_Product) AS `Value`,
    COALESCE(ValueText_Item, ValueText_Product) AS ValueText,
    COALESCE(ValueEnum_Item, ValueEnum_Product) AS ValueEnum,
    COALESCE(ValueDouble_Item, ValueDouble_Product) AS ValueDouble
FROM ProductItemFeature;");
					break;
				case 11:
					$this->exec("alter table Product modify Variant varchar(100) not null;");
					$this->exec("alter table ProductFeature modify Variant varchar(100) not null;");
					$this->exec("CREATE TABLE AuditProduct
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
        CHECK (`Change` IN ('C', 'U'))
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;");
					$this->exec("CREATE TRIGGER AuditCreateProduct
    AFTER INSERT
    ON Product
    FOR EACH ROW
BEGIN
    INSERT INTO AuditProduct(Brand, Model, Variant, `Change`, `User`)
    VALUES(NEW.Brand, NEW.Model, NEW.Variant, 'C', @taralloAuditUsername);
END;");
					break;
				case 12:
					$this->exec("ALTER TABLE `Item` ADD INDEX `Brand_Model_Variant` (`Brand`, `Model`, `Variant`);");
					break;
				case 13:
					$this->exec("DROP FUNCTION GenerateCode");
					$this->exec("CREATE FUNCTION GenerateCode(currentPrefix varchar(20))
RETURNS varchar(190)
MODIFIES SQL DATA
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

	END");
					break;
				case 14:
					try {
						$this->exec("ALTER TABLE AuditProduct DROP CONSTRAINT check_change;");
					} catch (\PDOException $ignored) {
					}
					try {
						$this->exec("ALTER TABLE AuditProduct DROP CONSTRAINT check_change_2;");
					} catch (\PDOException $ignored) {
					}
					$this->exec("ALTER TABLE AuditProduct ADD CONSTRAINT check_change CHECK (`Change` IN ('C', 'R', 'U', 'D'));");
					$this->exec("DROP TRIGGER IF EXISTS AuditRenameProduct;");
					$this->exec("CREATE TRIGGER AuditRenameProduct
    AFTER UPDATE
    ON Product
    FOR EACH ROW
BEGIN
    INSERT INTO AuditProduct(Brand, Model, Variant, `Change`, `User`)
    VALUES(NEW.Brand, NEW.Model, NEW.Variant, 'R', @taralloAuditUsername);
END;");
					break;
				case 15:
					$this->exec("CREATE EVENT `DuplicateItemProductFeaturesCleanup`
    ON SCHEDULE EVERY '1' DAY
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
          AND DATEDIFF(NOW(), Audit.Time) >= 1
    )
    AND NOT EXISTS(
        SELECT AuditProduct.Brand, AuditProduct.Model, AuditProduct.Variant
        FROM AuditProduct
        WHERE Item.Brand = AuditProduct.Brand
          AND Item.Model = AuditProduct.Model
          AND Item.Variant = AuditProduct.Variant
          AND AuditProduct.Change IN ('C', 'U')
          AND DATEDIFF(NOW(), AuditProduct.Time) >= 1
    )");
					break;
				case 16:
					$this->exec(
						"CREATE TABLE `BulkTable`
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
  COLLATE = utf8mb4_unicode_ci;"
					);
					break;
				case 17:
					$this->exec(
						"CREATE TRIGGER CascadeRenameProduct
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
END;"
					);
					break;
				case 18:
					$this->exec("DROP EVENT IF EXISTS `DuplicateItemProductFeaturesCleanup`;");
					$this->exec(
						"CREATE EVENT `DuplicateItemProductFeaturesCleanup`
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
;"
					);
					break;
				case 19:
					$this->exec(
						'CREATE TABLE `Normalization`
(
    `MinimizedKey` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `NormalizedValue` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Category` VARCHAR(40) COLLATE utf8mb4_unicode_ci NOT NULL,
    PRIMARY KEY (`MinimizedKey`),
    INDEX (`Category`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;'
					);
					break;
				case 20:
					$this->exec(
						'CREATE TABLE `NormalizationForbidden`
(
    `MinimizedKey` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `Category` VARCHAR(40) COLLATE utf8mb4_unicode_ci NOT NULL,
    PRIMARY KEY (`MinimizedKey`),
    INDEX (`Category`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;'
					);
					break;
				case 21:
					$this->exec("DROP TRIGGER IF EXISTS CascadeItemCodeUpdateForReal");
					$this->exec("
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
		UPDATE Tree
		SET Ancestor=NEW.Code
		WHERE Ancestor=OLD.Code;
		UPDATE Tree
		SET Descendant=NEW.Code
		WHERE Descendant=OLD.Code;
		SET FOREIGN_KEY_CHECKS = 1;
	END IF;
END;");
					$this->exec("DROP TRIGGER IF EXISTS ItemBMVUpdate");
					$this->exec("
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
	END;");
					break;
				case 22:
					$this->exec(
						'CREATE TABLE `Donations` (
                             `Id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                             `Name` text NOT NULL,
                             `Location` text DEFAULT NULL,
                             `Date` timestamp(6) NULL DEFAULT NULL,
                             `Notes` text DEFAULT NULL,
                             `IsCompleted` tinyint(1) DEFAULT 0,
                             PRIMARY KEY (`Id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;'
					);
					$this->exec(
						'CREATE TABLE `DonationItem` (
    `Donation` bigint(20) unsigned NOT NULL,
    `Code` varchar(255) NOT NULL,
    PRIMARY KEY (`Donation`,`Code`),
    KEY `Code` (`Code`),
    CONSTRAINT `DonationItem_ibfk_1` FOREIGN KEY (`Donation`) REFERENCES `Donations` (`Id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `DonationItem_ibfk_2` FOREIGN KEY (`Code`) REFERENCES `Item` (`Code`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;'
					);
					$this->exec(
						'CREATE TABLE `DonationTasks` (
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
  COLLATE=utf8mb4_unicode_ci;'
					);
					$this->exec(
						'CREATE TABLE `DonationTasksProgress` (
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
  COLLATE=utf8mb4_unicode_ci;'
					);
					$this->exec("DROP EVENT IF EXISTS `DonationItem_ai`");
					$this->exec("
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
END;");
					$this->exec("DROP EVENT IF EXISTS `DonationTasks_ai`");
					$this->exec("
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
END;");
					$this->exec("DROP TRIGGER IF EXISTS CascadeItemCodeUpdateForReal");
					$this->exec("
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
		UPDATE Tree
		SET Ancestor=NEW.Code
		WHERE Ancestor=OLD.Code;
		UPDATE Tree
		SET Descendant=NEW.Code
		WHERE Descendant=OLD.Code;
		UPDATE DonationItem
		SET Code=NEW.Code
		WHERE Code=OLD.Code;
		UPDATE DonationTasksProgress
		SET ItemCode=NEW.Code
		WHERE ItemCode=OLD.Code;
		SET FOREIGN_KEY_CHECKS = 1;
	END IF;
END;");
					break;
				case 23:
					$this->exec("ALTER TABLE `Search` ADD COLUMN `Query` TEXT AFTER `Code`");
					break;
				case 24:
					$this->exec("DROP TABLE IF EXISTS `LocationAutosuggestCache`");
					$this->exec("
CREATE TABLE `LocationAutosuggestCache` (
	`Name` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
	`Color` VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL,
	FOREIGN KEY (`Name`) REFERENCES `Item` (`Code`) ON DELETE CASCADE ON UPDATE CASCADE -- REMEMBER TO DO NOT TRUST THIS FOREIGN KEY, THERE IS A TRIGGER THAT WILL DISABLE CASCADE WHEN RENAMING AN ITEM
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;");
					$this->exec("DROP EVENT IF EXISTS `LocationAutosuggestGenerateCache`");
					$this->exec("CREATE TRIGGER `LocationAutosuggestGenerateCache` AFTER INSERT ON `ItemFeature`
FOR EACH ROW
BEGIN
	IF NEW.Feature = 'type' AND NEW.ValueEnum = 'location' THEN
		INSERT INTO `LocationAutosuggestCache` (`Name`, `Color`) VALUES (NEW.Code, (SELECT ValueEnum FROM `ItemFeature` WHERE Feature = 'color' AND Code = NEW.Code LIMIT 1));
	ELSEIF NEW.Feature = 'color' AND (SELECT COUNT(*) FROM `LocationAutosuggestCache` WHERE Name = NEW.Code) > 0 THEN
		UPDATE `LocationAutosuggestCache`
		SET Color = NEW.ValueEnum
		WHERE Name = NEW.Code;
	END IF;
END;");
					$this->exec("DROP EVENT IF EXISTS `LocationAutosuggestUpdateCache`");
					$this->exec("CREATE TRIGGER `LocationAutosuggestUpdateCache` AFTER UPDATE ON `ItemFeature`
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
END;");
					$this->exec("DROP EVENT IF EXISTS `LocationAutosuggestDeleteCache`");
					$this->exec("CREATE TRIGGER `LocationAutosuggestDeleteCache` AFTER DELETE ON `ItemFeature`
FOR EACH ROW
BEGIN
	IF OLD.Feature = 'type' AND OLD.ValueEnum = 'location' THEN
		DELETE FROM `LocationAutosuggestCache` WHERE Name = OLD.Code;
	ELSEIF OLD.Feature = 'color' THEN
		UPDATE `LocationAutosuggestCache`
		SET Color = NULL
		WHERE Name = OLD.Code;
	END IF;
END;");
					break;
				default:
					throw new \RuntimeException('Schema version larger than maximum');
			}
			$this->schemaVersion++;
			echo 'Schema updated to version ' . $this->schemaVersion . PHP_EOL;
		}
		$this->exec("UPDATE Configuration SET `Value` = \"$this->schemaVersion\" WHERE `Key` = \"SchemaVersion\"");
	}

	private function updateData(int $data)
	{
		if ($this->dataVersion === $data) {
			return;
		} elseif ($this->dataVersion > $data) {
			throw new \InvalidArgumentException("Trying to downgrade schema from $this->dataVersion to $data");
		}
		while ($this->dataVersion < $data) {
			switch ($this->dataVersion) {
				case 0:
					$this->exec("INSERT INTO FeatureEnum (Feature, ValueEnum) VALUES ('type', 'ssd')");
					break;
				case 1:
					$this->exec("INSERT INTO `Feature` (`Feature`, `Group`, `Type`) VALUES ('wwn', 'codes', 0), ('cib-qr', 'administrative', 0), ('ram-timings', 'features', 0)");
					break;
				case 2:
					$this->exec("UPDATE `Feature` SET `Type` = 3 WHERE `Feature` = 'power-idle-pfc'");
					// There's no trigger for this, but the Type change still goes through.
					$this->exec("UPDATE `ItemFeature` SET `ValueDouble` = `ValueText`, `ValueText` = NULL WHERE `Feature` = 'power-idle-pfc'");
					break;
				case 3:
					// This was a mistake...
					break;
				case 4:
					$this->exec("INSERT INTO `Feature` (`Feature`, `Group`, `Type`) VALUES ('internal-name', 'commercial', 0)");
					break;
				case 5:
					$this->exec("INSERT INTO `Feature` (`Feature`, `Group`, `Type`) VALUES ('odd-form-factor', 'physical', 2)");
					$this->exec("INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES ('odd-form-factor', '5.25'), ('odd-form-factor', 'laptop-odd-7mm'), ('odd-form-factor', 'laptop-odd-8.5mm'), ('odd-form-factor', 'laptop-odd-9.5mm'), ('odd-form-factor', 'laptop-odd-12.7mm')");
					$this->exec("UPDATE `ItemFeature` SET `Feature` = 'odd-form-factor' WHERE `Feature` = 'hdd-odd-form-factor' AND (`ValueEnum` = '5.25' OR `ValueEnum` LIKE 'laptop%')");
					$this->exec("DELETE FROM `FeatureEnum` WHERE `Feature` = 'hdd-odd-form-factor' AND (`ValueEnum` = '5.25' OR `ValueEnum` LIKE 'laptop%')");
					// "If ON UPDATE CASCADE or ON UPDATE SET NULL recurses to update the same table it has previously updated during the cascade, it acts like RESTRICT"
					// - https://dev.mysql.com/doc/refman/5.7/en/innodb-foreign-key-constraints.html
					// And it's _obviously_ the same in MariaDB. There are 2 overlapping foreign key indexes: one for
					// normal features, one for enums. They can't be done in any other way. Both are updated, this
					// apparently counts as "recursively" so MariaDB throws a big fat error saying that it cannot
					// CASCADE because there's a foreign key with ON UPDATE CASCADE preventing the cascade, because
					// it decided that this is really a RESTRICT. Maybe it's to prevent infinite indirect loops, but
					// other DBMS apparently do it without infinite loops...
					$this->exec("SET FOREIGN_KEY_CHECKS = 0;");
					$this->exec("UPDATE `Feature` SET `Feature` = 'hdd-form-factor' WHERE `Feature` = 'hdd-odd-form-factor'");
					$this->exec("UPDATE `FeatureEnum` SET `Feature` = 'hdd-form-factor' WHERE `Feature` = 'hdd-odd-form-factor'");
					$this->exec("UPDATE `ItemFeature` SET `Feature` = 'hdd-form-factor' WHERE `Feature` = 'hdd-odd-form-factor'");
					$this->exec("SET FOREIGN_KEY_CHECKS = 1;");
					break;
				case 6:
					$this->exec("INSERT INTO FeatureEnum (Feature, ValueEnum) VALUES ('type', 'card-reader')");
					break;
				case 7:
					$this->exec("INSERT INTO FeatureEnum (Feature, ValueEnum) VALUES ('working', 'to-be-tested')");
					$this->exec("INSERT INTO FeatureEnum (Feature, ValueEnum) VALUES ('check', 'partial-inventory')");
					break;
				case 8:
					$this->exec("INSERT INTO `Feature` (`Feature`, `Group`, `Type`) VALUES ('thread-n', 'features', 1)");
					break;
				case 9:
					$this->exec("DELETE FROM ItemFeature WHERE Feature = 'check' AND ValueEnum = 'lost'");
					$this->exec("DELETE FROM FeatureEnum WHERE Feature = 'check' AND ValueEnum = 'lost'");
					break;
				case 10:
					$this->exec("INSERT INTO FeatureEnum (Feature, ValueEnum) VALUES ('color', 'silver')");
					break;
				case 11:
					$this->exec("INSERT INTO `Feature` (`Feature`, `Group`, `Type`) VALUES ('todo', 'general', 2)");
					$this->exec("INSERT INTO FeatureEnum (Feature, ValueEnum) VALUES ('todo', 'transplant'), ('todo', 'container'), ('todo', 'install-os'), ('todo', 'finish-os-install'), ('todo', 'replace-capacitors'), ('todo', 'add-components'), ('todo', 'salvage-components')");
					break;
				case 12:
					$this->exec("INSERT INTO `Feature` (`Feature`, `Group`, `Type`) VALUES ('sas-sata-ports-n', 'ports', 1),  ('sas-sff-8087-ports-n', 'ports', 1), ('sas-sff-8088-ports-n', 'ports', 1), ('usb-c-ports-n', 'ports', 1), ('mini-displayport-ports-n', 'ports', 1), ('micro-hdmi-ports-n', 'ports', 1), ('thunderbolt-ports-n', 'ports', 1), ('esata-ports-n', 'ports', 1)");
					break;
				case 13:
					$this->exec("INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES ('psu-connector-cpu', '8pin8pin'), ('psu-connector-cpu', '8pin4pin'), ('cpu-socket', 'f')");
					break;
				case 14:
					$this->exec("INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES ('todo', 'replace-cmos-battery')");
					break;
				case 15:
					$this->exec("INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES ('todo', 'finish-testing'), ('todo', 'finish-inventory')");
					$this->exec("INSERT INTO `Feature` (`Feature`, `Group`, `Type`) VALUES ('psu-12v-rail-ampere', 'power', 3)");
					$this->exec("SET FOREIGN_KEY_CHECKS = 0;"); // See case 5 for an explanation
					$this->exec("UPDATE `FeatureEnum` SET `ValueEnum` = 'storage-card' WHERE `ValueEnum` = 'scsi-card' AND `Feature` = 'type'");
					$this->exec("UPDATE `ItemFeature` SET `ValueEnum` = 'storage-card' WHERE `ValueEnum` = 'scsi-card' AND `Feature` = 'type'");
					$this->exec("SET FOREIGN_KEY_CHECKS = 1;");
					break;
				case 16:
					$this->exec("INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES ('todo', 'replace-temp-parts'), ('todo', 'remove-from-computer'), ('todo', 'replace-broken-parts'), ('todo', 'replace-elec-components')");
					$this->exec("SET FOREIGN_KEY_CHECKS = 0;"); // See case 5 for an explanation
					$this->exec("UPDATE `FeatureEnum` SET `ValueEnum` = 'add-parts' WHERE `ValueEnum` = 'add-components' AND `Feature` = 'todo'");
					$this->exec("UPDATE `FeatureEnum` SET `ValueEnum` = 'salvage-parts' WHERE `ValueEnum` = 'salvage-components' AND `Feature` = 'todo'");
					$this->exec("UPDATE `ItemFeature` SET `ValueEnum` = 'add-parts' WHERE `ValueEnum` = 'add-components' AND `Feature` = 'todo'");
					$this->exec("UPDATE `ItemFeature` SET `ValueEnum` = 'salvage-parts' WHERE `ValueEnum` = 'salvage-components' AND `Feature` = 'todo'");
					$this->exec("SET FOREIGN_KEY_CHECKS = 1;");
					break;
				case 17:
					$this->exec("INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES ('todo', 'thermal-paste'), ('check', 'wrong-data')");
					break;
				case 18:
					$this->exec("INSERT INTO `Feature` (`Feature`, `Group`, `Type`) VALUES ('cnr-sockets-n', 'sockets', 1)");
					break;
				case 19:
					$this->exec("INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES ('todo', 'see-notes')");
					$this->exec("UPDATE `ItemFeature` SET `ValueEnum`='install-os' WHERE `Feature`='todo' AND `ValueEnum`='finish-os-install'");
					$this->exec("DELETE FROM `FeatureEnum` WHERE `Feature`='todo' AND `ValueEnum`='finish-os-install'");
					$this->exec("INSERT INTO `Feature` (`Feature`, `Group`, `Type`) VALUES ('gotcha', 'general', 0)");
					break;
				case 20:
					$this->exec("INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES ('power-connector', 'c5'), ('power-connector', 'c7')");
					break;
				case 21:
					$this->exec("DELETE FROM ItemFeature WHERE Feature = 'working' AND ValueEnum = 'to-be-tested'");
					$this->exec("DELETE FROM ProductFeature WHERE Feature = 'working' AND ValueEnum = 'to-be-tested'");
					$this->exec("DELETE FROM FeatureEnum WHERE Feature = 'working' AND ValueEnum = 'to-be-tested'");
					break;
				case 22:
					$this->exec("INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES ('cpu-socket', 's1'), ('cpu-socket', 'fs1'), ('cpu-socket', 'socket563'), ('cpu-socket', 'soldered')");
					break;
				case 23:
					$this->exec("INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES ('color', 'darkgreen'), ('color', 'olivedrab'), ('color', 'teal')");
					break;
				case 24:
					$this->exec("UPDATE `ItemFeature` SET `ValueEnum`='showcase' WHERE `Feature`='restrictions' AND `ValueEnum`='training'");
					$this->exec("UPDATE `ItemFeature` SET `ValueEnum`='missing-content' WHERE `Feature`='check' AND `ValueEnum`='partial-inventory'");
					$this->exec("INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES ('check', 'wrong-content')");
					break;
				case 25:
					$this->exec("INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES ('color', 'copper'), ('color', 'weeerde')");
					break;
				case 26:
					$this->exec("INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES ('smart-data', 'sus')");
					break;
				case 27:
					$this->exec("INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES ('type', 'smartphone-tablet')");
					$this->exec("INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES ('power-connector', 'usb-c')");
					break;
				case 28:
					$this->exec("INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES ('type', 'projector')");
					$this->exec("INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES ('todo', 'repair')");
					$this->exec("INSERT INTO `Feature` (`Feature`, `Group`, `Type`) VALUES ('video-api', 'software', 0)");
					$this->exec("SET FOREIGN_KEY_CHECKS = 0;"); // See case 5 for an explanation
					$this->exec("UPDATE `ItemFeature` SET `ValueEnum` = 'add-parts' WHERE `ValueEnum` = 'container' AND `Feature` = 'todo'");
					$this->exec("UPDATE `ItemFeature` SET `ValueEnum` = 'add-parts' WHERE `ValueEnum` = 'replace-temp-parts' AND `Feature` = 'todo'");
					$this->exec("UPDATE `FeatureEnum` SET `ValueEnum` = 'test-and-inventory' WHERE `ValueEnum` = 'finish-testing' AND `Feature` = 'todo'");
					$this->exec("UPDATE `ItemFeature` SET `ValueEnum` = 'test-and-inventory' WHERE `ValueEnum` = 'finish-testing' AND `Feature` = 'todo'");
					$this->exec("UPDATE `ItemFeature` SET `ValueEnum` = 'test-and-inventory' WHERE `ValueEnum` = 'finish-inventory' AND `Feature` = 'todo'");
					$this->exec("SET FOREIGN_KEY_CHECKS = 1;");
					break;
				case 29:
					$this->exec("INSERT INTO `Feature` (Feature, `Group`, Type) VALUES ('height-mm', 'physical', 3), ('msata-ports-n', 'ports', 1), ('m2-connectors-n', 'ports', 1)");
					$this->exec("INSERT INTO `FeatureEnum` (`Feature`, `ValueEnum`) VALUES ('hdd-form-factor', '2.5'), ('hdd-form-factor', '1.8'), ('hdd-form-factor', '1')");
					$this->exec("INSERT INTO `ItemFeature` (Code, `Feature`, `ValueDouble`) SELECT Code, 'height-mm', 15.0 FROM ItemFeature WHERE Feature = 'hdd-form-factor' AND ValueEnum LIKE '%-15mm'");
					$this->exec("INSERT INTO `ItemFeature` (Code, `Feature`, `ValueDouble`) SELECT Code, 'height-mm', 9.5 FROM ItemFeature WHERE Feature = 'hdd-form-factor' AND ValueEnum LIKE '%-9.5mm'");
					$this->exec("INSERT INTO `ItemFeature` (Code, `Feature`, `ValueDouble`) SELECT Code, 'height-mm', 8.0 FROM ItemFeature WHERE Feature = 'hdd-form-factor' AND ValueEnum LIKE '%-8mm'");
					$this->exec("INSERT INTO `ItemFeature` (Code, `Feature`, `ValueDouble`) SELECT Code, 'height-mm', 7.0 FROM ItemFeature WHERE Feature = 'hdd-form-factor' AND ValueEnum LIKE '%-7mm'");
					$this->exec("INSERT INTO `ItemFeature` (Code, `Feature`, `ValueDouble`) SELECT Code, 'height-mm', 5.0 FROM ItemFeature WHERE Feature = 'hdd-form-factor' AND ValueEnum LIKE '%-5mm'");
					$this->exec("INSERT INTO `ProductFeature` (Brand, Model, Variant, `Feature`, `ValueDouble`) SELECT Brand, Model, Variant, 'height-mm', 15.0 FROM ProductFeature WHERE Feature = 'hdd-form-factor' AND ValueEnum LIKE '%-15mm'");
					$this->exec("INSERT INTO `ProductFeature` (Brand, Model, Variant, `Feature`, `ValueDouble`) SELECT Brand, Model, Variant, 'height-mm', 9.5 FROM ProductFeature WHERE Feature = 'hdd-form-factor' AND ValueEnum LIKE '%-9.5mm'");
					$this->exec("INSERT INTO `ProductFeature` (Brand, Model, Variant, `Feature`, `ValueDouble`) SELECT Brand, Model, Variant, 'height-mm', 8.0 FROM ProductFeature WHERE Feature = 'hdd-form-factor' AND ValueEnum LIKE '%-8mm'");
					$this->exec("INSERT INTO `ProductFeature` (Brand, Model, Variant, `Feature`, `ValueDouble`) SELECT Brand, Model, Variant, 'height-mm', 7.0 FROM ProductFeature WHERE Feature = 'hdd-form-factor' AND ValueEnum LIKE '%-7mm'");
					$this->exec("INSERT INTO `ProductFeature` (Brand, Model, Variant, `Feature`, `ValueDouble`) SELECT Brand, Model, Variant, 'height-mm', 5.0 FROM ProductFeature WHERE Feature = 'hdd-form-factor' AND ValueEnum LIKE '%-5mm'");
					$this->exec("SET FOREIGN_KEY_CHECKS = 0;"); // See case 5 for an explanation
					$this->exec("UPDATE `ItemFeature` SET `ValueEnum` = '2.5' WHERE `ValueEnum` LIKE '2.5-%' AND `Feature` = 'hdd-form-factor'");
					$this->exec("UPDATE `ItemFeature` SET `ValueEnum` = '1.8' WHERE `ValueEnum` LIKE '1.8-%' AND `Feature` = 'hdd-form-factor'");
					$this->exec("UPDATE `ItemFeature` SET `ValueEnum` = 'm2' WHERE `ValueEnum` = 'm2.2' AND `Feature` = 'hdd-form-factor'");
					$this->exec("UPDATE `ProductFeature` SET `ValueEnum` = '2.5' WHERE `ValueEnum` LIKE '2.5-%' AND `Feature` = 'hdd-form-factor'");
					$this->exec("UPDATE `ProductFeature` SET `ValueEnum` = '1.8' WHERE `ValueEnum` LIKE '1.8-%' AND `Feature` = 'hdd-form-factor'");
					$this->exec("UPDATE `ProductFeature` SET `ValueEnum` = 'm2' WHERE `ValueEnum` = 'm2.2' AND `Feature` = 'hdd-form-factor'");
					$this->exec("SET FOREIGN_KEY_CHECKS = 1;");
					$this->exec("DELETE FROM `FeatureEnum` WHERE ValueEnum LIKE '2.5-%' OR ValueEnum LIKE '1.8-%' OR ValueEnum = 'm2.2'");
					$this->exec("DELETE FROM `ProductFeature` WHERE ValueEnum LIKE '2.5-%' OR ValueEnum LIKE '1.8-%' OR ValueEnum = 'm2.2'");
					break;
				case 30:
					$this->exec("INSERT INTO `Feature` (Feature, `Group`, Type) VALUES ('m2-slot-length-mm', 'physical', 3)");
					break;
				case 31:
					$this->exec("INSERT INTO `LocationAutosuggestCache` (`Name`, `Color`) SELECT t1.Code AS name, t2.ValueEnum AS color FROM `ProductItemFeatureUnified` AS t1 LEFT JOIN `ItemFeature` AS t2 ON t2.Feature = 'color' AND t1.Code = t2.Code WHERE t1.ValueEnum = 'location'");
					break;
				default:
					throw new \RuntimeException('Data version larger than maximum');
			}
			$this->dataVersion++;
			echo 'Data updated to version ' . $this->dataVersion . PHP_EOL;
		}
		$this->exec("UPDATE Configuration SET `Value` = '$this->dataVersion' WHERE `Key` = 'DataVersion'");
	}

	private function exec(string $query)
	{
		$result = $this->getPDO()->exec($query);

		if ($result === false) {
			throw new \RuntimeException('Exec failed, see stack trace');
		}
	}
}
