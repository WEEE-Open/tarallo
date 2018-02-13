<?php

namespace WEEEOpen\Tarallo\Server\Database;

use WEEEOpen\Tarallo\Server\SearchTriplet;
use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
use WEEEOpen\Tarallo\Server\ItemUpdate;

final class FeatureDAO extends DAO {

	/**
	 * Add features to Items passed as a parameter.
	 *
	 * @param $items array map from item code to Item.
	 */
	public function setFeatures(array $items) {
		if(empty($items)) {
			return;
		}

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

		$inItemID = $this->multipleIn(':item', $items);
		$featureStatement = $this->getPDO()->prepare('SELECT `Code`, Feature, COALESCE(`Value`, ValueText, ValueEnum) AS `FeatureValue`
            FROM ItemFeature
            WHERE `Code` IN (' . $inItemID . ');
		');

		// TODO: default features

		foreach($items as $itemID => $item) {
			$featureStatement->bindValue(':item' . $itemID, $itemID, \PDO::PARAM_INT);
		}

		$featureStatement->execute();
		try {
			if($featureStatement->rowCount() > 0) {
				foreach($featureStatement as $row) {
					/** @var Item[] $items */
					$items[$row['ItemID']]->addFeature(Feature::ofString($row['FeatureName'], $row['FeatureValue']));
				}
			}
		} finally {
			$featureStatement->closeCursor();
		}
	}

	/**
	 * Build some dynamic SQL queries, or rather pieces of queries, because that's how we roll.
	 * They are actually missing the SELECT part, so add it. Count on Item, ItemFeature and Feature being present
	 * and correctly joined, other tables may or may not be there.
	 * Bind search key to ":searchname . $key" and value to ":searchvalue . $key". Where $key is a key in the $searches
	 * array.
	 *
	 * @param SearchTriplet[] $searches non-empty array of SearchTriplet
	 * @param string $parameterIdentifier Parameter name, will be assembled as follows: ":" . $string . "name" (or "value").
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
					WHERE Feature.FeatureName = :'.$parameterIdentifier.'name' . $key . '
					AND Feature.FeatureType = ' . Feature::INTEGER . '
					AND ItemFeature.Value ' . $compare . ' :'.$parameterIdentifier.'value' . $key;
					break;
				case Feature::ENUM:
					$queries[] = '
					FROM Item
					JOIN ItemFeature ON Item.ItemID = ItemFeature.ItemID
					JOIN Feature ON ItemFeature.FeatureID = Feature.FeatureID
					JOIN FeatureValue ON ItemFeature.FeatureID = FeatureValue.FeatureID
					WHERE ItemFeature.ValueEnum = FeatureValue.ValueEnum
					AND Feature.FeatureName = :'.$parameterIdentifier.'name' . $key . '
					AND Feature.FeatureType = ' . Feature::ENUM . '
					AND FeatureValue.ValueText = :'.$parameterIdentifier.'value' . $key;
					break;
				default:
				case Feature::STRING:
					$queries[] = '
					FROM Item
					NATURAL JOIN ItemFeature
					NATURAL JOIN Feature
					WHERE Feature.FeatureName = :'.$parameterIdentifier.'name' . $key . '
					AND Feature.FeatureType = ' . Feature::STRING . '
					AND ItemFeature.ValueText LIKE :'.$parameterIdentifier.'value' . $key;
			}
		}

		return $queries;
	}

	private $deleteFeatureStatement = null;

	private function deleteFeature(ItemIncomplete $item, Feature $feature) {
		// TODO: this method may turn out SLIGHTLY too slow, since it's a single prepared statement executed a million times in a loop somewhere outside this method.
		$pdo = $this->getPDO();
		if($this->deleteFeatureStatement === null) {
			$this->deleteFeatureStatement = $pdo->prepare('DELETE * FROM ItemFeature WHERE `Code` = :id AND Feature = :feat');
		}
		$this->deleteFeatureStatement->bindValue(':id', $item->getCode(), \PDO::PARAM_STR);
		$this->deleteFeatureStatement->bindValue(':feat', $feature->name, \PDO::PARAM_STR);
		$this->deleteFeatureStatement->execute();
	}

	public function updateDeleteFeatures(ItemUpdate $item) {
		$features = $item->getFeatures();

		if(empty($features)) {
			return;
		}

		$newItem = new Item($item->getCode());
		foreach($features as $feature) {
			$this->deleteFeature($item, $feature);
			if($feature->value !== null) {
				$newItem->addFeature(new Feature($feature, $feature->value));
			}
		}
		$this->addFeatures($newItem);
	}

	private $featureNumberStatement = null;
	private $featureTextStatement = null;
	private $featureEnumStatement = null;

	public function addFeatures(Item $item) {
		$features = $item->getFeatures();

		if(empty($features)) {
			return;
		}

		$pdo = $this->getPDO();

		if($this->featureNumberStatement === null) {
			$this->featureNumberStatement = $pdo->prepare('INSERT INTO ItemFeature (Feature, `Code`, `Value`) VALUES (:feature, :item, :val)');
		}
		if($this->featureTextStatement === null) {
			$this->featureTextStatement = $pdo->prepare('INSERT INTO ItemFeature (Feature, `Code`, `ValueText`) VALUES (:feature, :item, :val)');
		}
		if($this->featureEnumStatement === null) {
			$this->featureEnumStatement = $pdo->prepare('INSERT INTO ItemFeature (Feature, `Code`, `ValueEnum`) VALUES (:feature, :item, :val)');
		}

		$itemId = $item->getCode();
		$this->featureNumberStatement->bindValue(':item', $itemId, \PDO::PARAM_INT);
		$this->featureTextStatement->bindValue(':item', $itemId, \PDO::PARAM_STR);
		$this->featureEnumStatement->bindValue(':item', $itemId, \PDO::PARAM_STR);

		foreach($features as $feature) {
			$name = $feature->name;
			$value = $feature->value;
			switch($feature->type) {
				// was really tempted to use variable variables here...
				case Feature::STRING:
					$this->featureTextStatement->bindValue(':feature', $name);
					$this->featureTextStatement->bindValue(':val', $value);
					$this->featureTextStatement->execute();
					break;
				case Feature::INTEGER:
					$this->featureNumberStatement->bindValue(':feature', $name);
					$this->featureNumberStatement->bindValue(':val', $value);
					$this->featureNumberStatement->execute();
					break;
				case Feature::ENUM:
					$this->featureEnumStatement->bindValue(':feature', $name);
					$this->featureEnumStatement->bindValue(':val', $name);
					$this->featureEnumStatement->execute();
					break;
				default:
					throw new \LogicException('Unknown feature type ' . $feature->type . ' returned by getFeatureTypeFromName (should never happen unless a cosmic ray flips a bit somewhere)');
			}
		}
	}
}