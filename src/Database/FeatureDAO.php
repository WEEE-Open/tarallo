<?php

namespace WEEEOpen\Tarallo\Server\Database;

use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\SearchTriplet;


final class FeatureDAO extends DAO {

	/**
	 * Add features to ALL TEH ITEMS
	 *
	 * @param Item[] $items
	 *
	 * @return Item[] same array
	 */
	public function getFeaturesAll(array $items) {
		foreach($items as $item) {
			$this->getFeatures($item);
		}

		return $items;
	}

	private $getFeaturesStatement = null;

	/**
	 * Add features to an item
	 *
	 * @param Item $item
	 *
	 * @return Item same item
	 */
	public function getFeatures(Item $item) {
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
		if($this->getFeaturesStatement === null) {
			$this->getFeaturesStatement = $this->getPDO()->prepare('SELECT Feature, COALESCE(`Value`, ValueText, ValueEnum, ValueDouble) AS `Value`
            FROM ItemFeature
            WHERE `Code` = :cod;');
		}

		$this->getFeaturesStatement->bindValue(':cod', $item->getCode(), \PDO::PARAM_STR);

		try {
			$this->getFeaturesStatement->execute();
			if($this->getFeaturesStatement->rowCount() > 0) {
				foreach($this->getFeaturesStatement as $row) {
					/** @var Item[] $items */
					$item->addFeature(Feature::ofString($row['Feature'], $row['Value']));
				}
			}
		} finally {
			$this->getFeaturesStatement->closeCursor();
		}

		return $item;
	}

	/**
	 * Build some dynamic SQL queries, or rather pieces of queries, because that's how we roll.
	 * They are actually missing the SELECT part, so add it. Count on Item, ItemFeature and Feature being present
	 * and correctly joined, other tables may or may not be there.
	 * Bind search key to ":searchname . $key" and value to ":searchvalue . $key". Where $key is a key in the $searches
	 * array.
	 *
	 * @param SearchTriplet[] $searches non-empty array of SearchTriplet
	 * @param string $parameterIdentifier Parameter name, will be assembled as follows: ":" . $string . "name" (or
	 *     "value").
	 *
	 * @return string[] array of WHERE statements(?) (no "WHERE" keyword itself)
	 */
	public function getWhereStringFromSearches($searches, $parameterIdentifier) {
		$queries = [];

		foreach($searches as $key => $triplet) {
			if(!is_integer($key)) {
				throw new \InvalidArgumentException('Keys should be integers, ' . $key . ' isn\'t');
			}
			if(!($triplet instanceof SearchTriplet)) {
				if(is_object($triplet)) {
					throw new \InvalidArgumentException('Search parameters must be instances of SearchTriplet, ' . get_class($triplet) . ' given');
				} else {
					throw new \InvalidArgumentException('Search parameters must be instances of SearchTriplet, ' . gettype($triplet) . ' given');
				}
			}
		}

		foreach($searches as $key => $triplet) {
			switch(Feature::getType($triplet->getKey())) {
				case Feature::INTEGER:
					$compare = $searches[$key]->getCompare();
					if($compare === '>' || $compare === '<') {
						$compare .= '='; // greater than OR EQUAL, and the like
					}
					$queries[] = '
					FROM Item
					NATURAL JOIN ItemFeature
					NATURAL JOIN Feature
					WHERE Feature.FeatureName = :' . $parameterIdentifier . 'name' . $key . '
					AND Feature.FeatureType = ' . Feature::INTEGER . '
					AND ItemFeature.Value ' . $compare . ' :' . $parameterIdentifier . 'value' . $key;
					break;
				case Feature::ENUM:
					$queries[] = '
					FROM Item
					JOIN ItemFeature ON Item.ItemID = ItemFeature.ItemID
					JOIN Feature ON ItemFeature.FeatureID = Feature.FeatureID
					JOIN FeatureValue ON ItemFeature.FeatureID = FeatureValue.FeatureID
					WHERE ItemFeature.ValueEnum = FeatureValue.ValueEnum
					AND Feature.FeatureName = :' . $parameterIdentifier . 'name' . $key . '
					AND Feature.FeatureType = ' . Feature::ENUM . '
					AND FeatureValue.ValueText = :' . $parameterIdentifier . 'value' . $key;
					break;
				default:
				case Feature::STRING:
					$queries[] = '
					FROM Item
					NATURAL JOIN ItemFeature
					NATURAL JOIN Feature
					WHERE Feature.FeatureName = :' . $parameterIdentifier . 'name' . $key . '
					AND Feature.FeatureType = ' . Feature::STRING . '
					AND ItemFeature.ValueText LIKE :' . $parameterIdentifier . 'value' . $key;
			}
		}

		return $queries;
	}

	private $featureNumberStatement = null;
	private $featureTextStatement = null;
	private $featureEnumStatement = null;
	private $featureDoubleStatement = null;

	public function setFeatures(Item $item) {
		$features = $item->getFeatures();

		if(empty($features)) {
			return;
		}

		$pdo = $this->getPDO();

		if($this->featureNumberStatement === null) {
			$this->featureNumberStatement = $pdo->prepare('INSERT INTO ItemFeature (Feature, `Code`, `Value`) VALUES (:feature, :item, :val) ON DUPLICATE KEY UPDATE `Value`=:val2');
		}
		if($this->featureTextStatement === null) {
			$this->featureTextStatement = $pdo->prepare('INSERT INTO ItemFeature (Feature, `Code`, `ValueText`) VALUES (:feature, :item, :val) ON DUPLICATE KEY UPDATE `ValueText`=:val2');
		}
		if($this->featureEnumStatement === null) {
			$this->featureEnumStatement = $pdo->prepare('INSERT INTO ItemFeature (Feature, `Code`, `ValueEnum`) VALUES (:feature, :item, :val) ON DUPLICATE KEY UPDATE `ValueEnum`=:val2');
		}
		if($this->featureDoubleStatement === null) {
			$this->featureDoubleStatement = $pdo->prepare('INSERT INTO ItemFeature (Feature, `Code`, `ValueDouble`) VALUES (:feature, :item, :val) ON DUPLICATE KEY UPDATE `ValueDouble`=:val2');
		}

		foreach($features as $feature) {
			$name = $feature->name;
			$value = $feature->value;
			switch($feature->type) {
				// was really tempted to use variable variables here...
				case Feature::STRING:
					$statement = $this->featureTextStatement;
					$type = \PDO::PARAM_STR;
					break;
				case Feature::INTEGER:
					$statement = $this->featureNumberStatement;
					$type = \PDO::PARAM_INT;
					break;
				case Feature::ENUM:
					$statement = $this->featureEnumStatement;
					$type = \PDO::PARAM_STR;
					break;
				case Feature::DOUBLE:
					$statement = $this->featureDoubleStatement;
					$type = \PDO::PARAM_STR;
					break;
				default:
					throw new \LogicException('Unknown feature type ' . $feature->type . ' returned by getFeatureTypeFromName (should never happen unless a cosmic ray flips a bit somewhere)');
			}
			try {
				$statement->bindValue(':feature', $name, \PDO::PARAM_STR);
				$statement->bindValue(':item', $item->getCode(), \PDO::PARAM_STR);
				$statement->bindValue(':val', $value, $type);
				$statement->bindValue(':val2', $value, $type);
				if(!$statement->execute()) {
					throw new DatabaseException("Cannot add/upadate feature $name with value $value for item " . $item->getCode());
				}
			} finally {
				$statement->closeCursor();
			}
		}
	}
}