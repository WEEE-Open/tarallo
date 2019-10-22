<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\BaseFeature;
use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\ItemWithCode;
use WEEEOpen\Tarallo\ItemWithFeatures;
use WEEEOpen\Tarallo\NotFoundException;


final class FeatureDAO extends DAO {/**
 * Obtain \PDO::PARAM_... constant from feature name
 *
 * @param int $type Feature type
 *
 * @return int Column name (e.g. ValueText)
 *@see getType
 *
 */public static function getPDOType(int $type): int {
	switch($type) {
		case BaseFeature::STRING:
			return \PDO::PARAM_STR;
		case BaseFeature::INTEGER:
			return \PDO::PARAM_INT;
		case BaseFeature::ENUM:
			return \PDO::PARAM_STR;
		case BaseFeature::DOUBLE:
			return \PDO::PARAM_STR;
		default:
			throw new \LogicException('Unrecognized feature type in getPDOType');
	}
}

	/**
 * Obtain database column name (for the ItemFeature table)
 *
 * @param int $type Feature type
 *
 * @return string Column name (e.g. ValueText)
 *@see getType
 *
 */public static function getColumn(int $type): string {
	switch($type) {
		case BaseFeature::STRING:
			return 'ValueText';
		case BaseFeature::INTEGER:
			return 'Value';
		case BaseFeature::ENUM:
			return 'ValueEnum';
		case BaseFeature::DOUBLE:
			return 'ValueDouble';
		default:
			throw new \LogicException('Unrecognized feature type in getColumn');
	}
}

	/**
	 * Get features from ALL TEH ITEMS
	 *
	 * @param ItemWithFeatures[] $items
	 *
	 * @return ItemWithFeatures[]|Item[] same array
	 */
	public function getFeaturesAll(array $items) {
		foreach($items as $item) {
			$this->getFeatures($item);
		}

		return $items;
	}

	/**
	 * Add features to an item
	 *
	 * @param ItemWithFeatures $item
	 *
	 * @return ItemWithFeatures|Item same item
	 */
	public function getFeatures(ItemWithFeatures $item) {
		/*
		 * This seemed a good query to fetch default and non-default features, when database structure was different:
		 *
		 * SELECT Item2.ItemID, Item2.ItemFor, Feature.FeatureName, COALESCE(ItemFeature.`Value`, ItemFeature.ValueText, FeatureValue.ValueText) AS `FeatureValue`
		 * FROM (SELECT ItemID, ItemID AS ItemFor FROM Item UNION ALL SELECT `Default` AS ItemID, ItemID AS ItemFor FROM Item WHERE `Default` IS NOT NULL)  Item2
		 * JOIN ItemFeature ON  Item2.ItemID = ItemFeature.ItemID
		 * JOIN Feature ON ItemFeature.FeatureID = Feature.FeatureID
		 * LEFT JOIN FeatureValue ON ItemFeature.FeatureID = FeatureValue.FeatureID
		 * WHERE (ItemFeature.ValueEnum = FeatureValue.ValueEnum OR ItemFeature.ValueEnum IS NULL)
		 * AND Item2.ItemID IN (1, 2, 3);
		 *
		 * However, the subquery gives the correct and expected result, but the main query loses FOR UNFATHOMABLE REASONS the second half of the UNIONed data.
		 * So we're doing two queries. That UNION probably killed performance, too, so it's acceptable anyway.
		 *
		 * TODO: retry with new structure: who knows, it might work!
		 */

		// TODO: default features
		$statement = $this->getPDO()->prepare(
			'SELECT Feature, COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) AS `Value`
            FROM ItemFeature
            WHERE `Code` = :cod;'
		);

		$statement->bindValue(':cod', $item->getCode(), \PDO::PARAM_STR);

		try {
			$statement->execute();
			if($statement->rowCount() > 0) {
				foreach($statement as $row) {
					/** @var Item[] $items */
					$item->addFeature(Feature::ofString($row['Feature'], $row['Value']));
				}
			}
		} finally {
			$statement->closeCursor();
		}

		return $item;
	}

	/**
	 * Add a U audit entry for the specified item.
	 *
	 * @param ItemWithCode $item
	 */
	public function addAuditEntry(ItemWithCode $item) {
		$statement = $this->getPDO()
			->prepare('INSERT INTO Audit (`Code`, `Change`, `User`) VALUES (?, \'U\', @taralloAuditUsername)');

		try {
			$success = $statement->execute([$item->getCode()]);
			assert($success, 'add audit table entry for features update of ' . $item->getCode());
		} catch(\PDOException $e) {
			// Foreign key constraint fails = item does not exist (deleteFeatures cannot check that it doesn't,
			// but still tries to add an audit entry)
			if($e->getCode() === '23000' && $statement->errorInfo()[1] === 1452) {
				throw new NotFoundException();
			}
			throw $e;
		} finally {
			$statement->closeCursor();
		}
	}

	/**
	 * Set item features.
	 *
	 * @param ItemWithFeatures $item
	 *
	 * @return bool True if anything actually changed (and an U audit entry was generated), false otherwise.
	 * @TODO: it would be cool if changing a feature to the value it already has still didn't generate an entry...
	 */
	public function setFeatures(ItemWithFeatures $item): bool {
		$features = $item->getFeatures();

		if(empty($features)) {
			return false;
		}

		foreach($features as $feature) {
			$column = self::getColumn($feature->type);
			$type = self::getPDOType($feature->type);
			/** @noinspection SqlResolve */
			$statement = $this->getPDO()
				->prepare(
					"INSERT INTO ItemFeature (Feature, `Code`, `$column`) VALUES (:feature, :item, :val) ON DUPLICATE KEY UPDATE `$column`=:val2"
				);

			try {
				$statement->bindValue(':feature', $feature->name, \PDO::PARAM_STR);
				$statement->bindValue(':item', $item->getCode(), \PDO::PARAM_STR);
				$statement->bindValue(':val', $feature->value, $type);
				$statement->bindValue(':val2', $feature->value, $type);
				$result = $statement->execute();
				assert($result !== false, 'set feature');
			} catch(\PDOException $e) {
				// This error has ever been witnessed when master-master replication breaks, but apparently it's used
				// to signify that there's no foreign key target thing for the primary key other thing.
				// That is: inserting/updating a row for an item that doesn't exist.
				if($e->getCode() === 'HY000'
					&& $statement->errorInfo()[1] === 1032
					&& $statement->errorInfo()[2] === 'Can\'t find record in \'ItemFeature\''
				) {
					throw new NotFoundException();
				} else if($e->getCode() === '23000'
					&& $statement->errorInfo()[0] === '23000'
					&& $statement->errorInfo()[1] === 1452
				) {
					throw new NotFoundException();
				}
				throw $e;
			} finally {
				$statement->closeCursor();
			}
		}

		$this->addAuditEntry($item);
		return true;
	}

	/**
	 * Delete a single feature from an item. This generates no audit entries, BTW.
	 *
	 * @param ItemWithFeatures $item
	 * @param string[] $features
	 *
	 * @return bool True if anything was deleted
	 */
	public function deleteFeature(ItemWithFeatures $item, array $features) {
		if(empty($features)) {
			return false;
		}
		// This never fails, ever for items that don't exist
		$statement = $this->getPDO()->prepare('DELETE IGNORE FROM ItemFeature WHERE `Code` = ? AND `Feature`= ?');
		try {
			foreach($features as $feature) {
				if(!is_string($feature)) {
					throw new \InvalidArgumentException('Name of feature to be deleted should be a string');
				}

				$result = $statement->execute([$item->getCode(), $feature]);
				assert($result !== false, 'delete feature');
			}
		} finally {
			$statement->closeCursor();
		}
		return true;
	}


	/**
	 * Delete all features from an item
	 *
	 * @param ItemWithCode $item
	 */
	public function deleteFeaturesAll(ItemWithCode $item) {
		$statement = $this->getPDO()->prepare('DELETE IGNORE FROM ItemFeature WHERE `Code` = ?');

		try {
			$result = $statement->execute([$item->getCode()]);
			assert($result !== false, 'delete all features');
		} finally {
			$statement->closeCursor();
		}
	}
}
