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
		// $to is now > $this->version
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
		// $to is now > $this->version
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
