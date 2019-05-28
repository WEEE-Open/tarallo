<?php

namespace WEEEOpen\Tarallo\Server\Database;


class Updater extends DAO {
	private $schemaVersion;
	private $dataVersion;

	public function __construct(Database $db, $callback) {
		parent::__construct($db, $callback);
		try {
			$result = $this->getPDO()->query("SELECT `Value` FROM Configuration WHERE `Key` = 'SchemaVersion'");
			$this->schemaVersion = (int) $result->fetchColumn();
		} catch(\PDOException $e) {
			if($e->getCode() === '42S02') {
				$this->schemaVersion = 0;
				$this->dataVersion = 0;
				return;
			} else {
				throw $e;
			}
		}
		$result = $this->getPDO()->query("SELECT `Value` FROM Configuration WHERE `Key` = 'DataVersion'");
		$this->dataVersion = (int) $result->fetchColumn();
	}

	public function updateTo(int $schema, int $data) {
		$this->updateSchema($schema);
		$this->updateData($data);
	}

	private function updateSchema(int $schema) {
		if($this->schemaVersion === $schema) {
			return;
		} else if($this->schemaVersion > $schema) {
			throw new \InvalidArgumentException("Trying to downgrade schema from $this->schemaVersion to $schema");
		}
		// $schema is now > $this->schemaVersion
		while($this->schemaVersion < $schema) {
			switch($this->schemaVersion) {
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
					if($intermediate->rowCount() > 0) {
						// Close that cursor so we can do other stuff
						$intermediate->closeCursor();
						// Trigger will create and Audit entry, this requires an username... will fix them manually
						// in production, they're not important in development.
						$this->exec(/** @lang MariaDB */ "CALL SetUser('IMPORT')");
						// Also, there still trigger preventing this from being a single query...
						$intermediate2 = $this->getPDO()->query("SELECT DISTINCT Descendant FROM Tree WHERE Ancestor = 'Lost' AND Depth > 0");
						$fetched = $intermediate2->fetchAll(\PDO::FETCH_COLUMN);
						foreach($fetched as $item) {
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
				default:
					throw new \RuntimeException('Schema version larger than maximum');
			}
			$this->schemaVersion++;
			echo 'Schema updated to version ' . $this->schemaVersion . PHP_EOL;
		}
		$this->exec("UPDATE Configuration SET `Value` = \"$this->schemaVersion\" WHERE `Key` = \"SchemaVersion\"");
	}

	private function updateData(int $data) {
		if($this->dataVersion === $data) {
			return;
		} else if($this->dataVersion > $data) {
			throw new \InvalidArgumentException("Trying to downgrade schema from $this->dataVersion to $data");
		}
		while($this->dataVersion < $data) {
			switch($this->dataVersion) {
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
				default:
					throw new \RuntimeException('Data version larger than maximum');
			}
			$this->dataVersion++;
			echo 'Data updated to version ' . $this->dataVersion . PHP_EOL;
		}
		$this->exec("UPDATE Configuration SET `Value` = '$this->dataVersion' WHERE `Key` = 'DataVersion'");
	}

	private function exec(string $query) {
		$result = $this->getPDO()->exec($query);

		if($result === false) {
			throw new \RuntimeException('Exec failed, see stack trace');
		}
	}
}
