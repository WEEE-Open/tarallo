<?php

namespace WEEEOpen\Tarallo\Database;

class OptionDAO extends DAO
{
	private function getAllOptions(): array
	{
		$query = "SELECT `Key`, Value FROM Configuration";

		$options = [];
		$statement = $this->getPDO()->prepare($query);

		try {
			$success = $statement->execute();
			assert($success, 'get all options');
			while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
				$options[$row['Key']] = $row['Value'];
			}
		} finally {
			$statement->closeCursor();
		}
		return $options;
	}

	private function apcuGenerator($key)
	{
		return $this->getAllOptions();
	}

	public function getOptionValue(string $key)
	{
		if (Database::hasApcu()) {
			$success = null;
			$options = apcu_fetch('options', $success);
			if (!$success) {
				$options = apcu_entry('options', [$this, 'apcuGenerator']);
			}
		} else {
			$options = $this->getAllOptions();
		}

		return $options[$key] ?? null;
	}

	public function setOptionValue(string $key, string $value)
	{
		$pdo = $this->getPDO();
		$query = "INSERT INTO Configuration (`Key`, Value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE Value = :v2";

		$statement = $pdo->prepare($query);
		$statement->bindValue(':k', $key);
		$statement->bindValue(':v', $value);
		$statement->bindValue(':v2', $value);

		$success = false;
		try {
			$success = $statement->execute();
			assert($success, 'new option');
		} finally {
			$statement->closeCursor();
		}
		if (Database::hasApcu() && $success) {
			// Key will be generated on next read via apcu_entry which uses a big global lock
			$options = apcu_delete('options');
		}
	}
}
