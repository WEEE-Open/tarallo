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
					$this->exec(<<<EOQ
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
					$this->exec(<<<EOQ
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
					$this->exec(<<<EOQ
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
					$this->exec(<<<EOQ
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
					$this->exec(<<<EOQ
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
					$this->exec("# noinspection SqlResolve
ALTER TABLE Audit DROP CONSTRAINT CONSTRAINT_1");
					$this->exec(<<<EOQ
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
					$this->exec('ALTER TABLE `Audit` CHANGE `Time` `Time` timestamp(6) NOT NULL DEFAULT now(6) AFTER `Other`;');
					break;
			}
			$this->schemaVersion++;
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
			}
			$this->dataVersion++;
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
