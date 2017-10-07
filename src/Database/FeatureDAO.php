<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\InvalidParameterException;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\ItemIncomplete;
use WEEEOpen\Tarallo\ItemUpdate;
use WEEEOpen\Tarallo\Query\SearchTriplet;

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
         * This seems a good query to fetch default and non-default features:
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
         */

        $inItemID = $this->multipleIn(':item', $items);
        $featureStatement = $this->getPDO()->prepare('SELECT ItemID, Feature.FeatureName, COALESCE(ItemFeature.`Value`, ItemFeature.ValueText, FeatureValue.ValueText) AS `FeatureValue`
            FROM Feature, ItemFeature
            LEFT JOIN FeatureValue ON ItemFeature.FeatureID = FeatureValue.FeatureID
            WHERE ItemFeature.FeatureID = Feature.FeatureID AND (ItemFeature.ValueEnum = FeatureValue.ValueEnum OR ItemFeature.ValueEnum IS NULL)
            AND ItemID IN (' . $inItemID . ');
		');

	    $defaultFeatureStatement = $this->getPDO()->prepare('SELECT Item.ItemID, Feature.FeatureName, COALESCE(ItemFeature.`Value`, ItemFeature.ValueText, FeatureValue.ValueText) AS `FeatureValue`
			FROM Item, Feature, ItemFeature
			LEFT JOIN FeatureValue ON ItemFeature.FeatureID = FeatureValue.FeatureID
			WHERE (Item.`Default` = ItemFeature.ItemID AND Item.`Default` IS NOT NULL AND Item.isDefault = 1) AND ItemFeature.FeatureID = Feature.FeatureID AND (ItemFeature.ValueEnum = FeatureValue.ValueEnum OR ItemFeature.ValueEnum IS NULL)
			AND Item.ItemID IN (' . $inItemID . ');
		');

        foreach($items as $itemID => $item) {
            $featureStatement->bindValue(':item' . $itemID, $itemID, \PDO::PARAM_INT);
            $defaultFeatureStatement->bindValue(':item' . $itemID, $itemID, \PDO::PARAM_INT);
        }

        $featureStatement->execute();
        try {
	        if($featureStatement->rowCount() > 0) {
		        foreach($featureStatement as $row) {
			        /** @var Item[] $items */
			        $items[ $row['ItemID'] ]->addFeature($row['FeatureName'], $row['FeatureValue']);
		        }
	        }
        } finally {
	        $featureStatement->closeCursor();
        }

	    $defaultFeatureStatement->execute();
	    try {
		    if($defaultFeatureStatement->rowCount() > 0) {
			    foreach($defaultFeatureStatement as $row) {
				    $items[ $row['ItemID'] ]->addFeatureDefault($row['FeatureName'], $row['FeatureValue']);
			    }
		    }
	    } finally {
		    $defaultFeatureStatement->closeCursor();
	    }
    }

    private $featureTypeStatement = null;
    const FEATURE_TEXT = 0;
    const FEATURE_NUMBER = 1;
    const FEATURE_ENUM = 2;

    /**
     * @param $featureName
     * @return int
     * @throws InvalidParameterException
     */
    private function getFeatureTypeFromName($featureName) {
        $pdo = $this->getPDO();
        if($this->featureTypeStatement === null) {
            $this->featureTypeStatement = $pdo->prepare('SELECT `FeatureType` FROM Feature WHERE FeatureName = ? LIMIT 1');
        }
        $this->featureTypeStatement->bindValue(1, $featureName);
        $this->featureTypeStatement->execute();
        try {
	        if($this->featureTypeStatement->rowCount() === 0) {
		        throw new InvalidParameterException('Unknown feature name ' . $featureName);
	        }
	        $type = (int) $this->featureTypeStatement->fetch(\PDO::FETCH_NUM)[0];
        } finally {
	        $this->featureTypeStatement->closeCursor();
        }

	    /** @noinspection PhpUndefinedVariableInspection without the try-block PHPStorm didn't complain, but apparently looks like a catch-block to it */
	    switch($type) {
            case 0:
                return self::FEATURE_TEXT;
            case 1:
                return self::FEATURE_NUMBER;
            case 2:
                return self::FEATURE_ENUM;
            default:
                throw new \LogicException('Unknown feature type for ' . $featureName . ' found in database');
        }
    }

	/**
	 * Build some dynamic SQL queries, or rather pieces of queries, because that's how we roll.
	 * Bind search key to ":searchname . $key" and value to ":searchvalue . $key". Where $key is the numeric index of the $searches array.
	 *
	 * @param SearchTriplet[] $searches non-empty array of SearchTriplet
	 *
	 * @return string sequence of WHERE statements(?) (no "WHERE" keyword itself)
	 * @throws InvalidParameterException
	 * @throws \InvalidArgumentException - wrong parameters
	 */
	public function getWhereStringFromSearches($searches) {
		$where = '';

		foreach($searches as $numericKey => $triplet) {
			if(!($triplet instanceof SearchTriplet)) {
				if(is_object($triplet)) {
					throw new \InvalidArgumentException('Search parameters must be instances of SearchTriplet, ' . get_class($triplet) . ' given');
				} else {
					throw new \InvalidArgumentException('Search parameters must be instances of SearchTriplet, ' . gettype($triplet) . ' given');
				}
			}
		}

		foreach($searches as $numericKey => $triplet) {
			if($this->database->featureDAO()->getFeatureTypeFromName($triplet->getKey()) === self::FEATURE_NUMBER) {
				$where .= '(Feature.FeatureName = :searchname' . $numericKey . ' AND Value ' . $searches[$numericKey]->getCompare() . ' :searchvalue' . $numericKey . ') OR ';
			} else {
				$where .= '(Feature.FeatureName = :searchname' . $numericKey . ' AND COALESCE(ItemFeature.ValueText, FeatureValue.ValueText) LIKE :searchvalue' . $numericKey . ') OR ';
			}
		}
		$where = substr($where, 0, strlen($where) - 4); // remove last OR

		return $where;
    }

    private $featureEnumNameStatement = null;

    public function getFeatureValueEnumFromName($featureName, $featureValueText) {
        $pdo = $this->getPDO();
        if($this->featureEnumNameStatement === null) {
            $this->featureEnumNameStatement = $pdo->prepare('SELECT `ValueEnum` FROM FeatureValue, Feature WHERE Feature.FeatureID = FeatureValue.FeatureID AND Feature.FeatureName = :n AND FeatureValue.ValueText = :valuetext AND Feature.FeatureType = :type LIMIT 1');
        }
        $this->featureEnumNameStatement->bindValue(':n', $featureName);
        $this->featureEnumNameStatement->bindValue(':valuetext', $featureValueText);
        $this->featureEnumNameStatement->bindValue(':type', self::FEATURE_ENUM);
        $this->featureEnumNameStatement->execute();
        try {
	        if($this->featureEnumNameStatement->rowCount() === 0) {
		        $this->featureEnumNameStatement->closeCursor();
		        throw new InvalidParameterException('Invalid value ' . $featureValueText . ' for feature ' . $featureName);
	        }
           $result = $this->featureEnumNameStatement->fetch(\PDO::FETCH_NUM);
        } finally {
	        $this->featureEnumNameStatement->closeCursor();
        }

	    /** @noinspection PhpUndefinedVariableInspection */
	    return $result[0];
    }

    private $deleteFeatureStatement = null;
	private function deleteFeature(ItemIncomplete $item, $featureName) {
		// TODO: this method may turn out SLIGHTLY too slow, since it's a single prepared statement executed a million times in a loop somewhere outside this method.
		$pdo = $this->getPDO();
		if($this->deleteFeatureStatement === null) {
			$this->deleteFeatureStatement = $pdo->prepare('DELETE ItemFeature.* FROM ItemFeature JOIN Feature ON ItemFeature.FeatureID = Feature.FeatureID WHERE ItemFeature.ItemID = :id AND Feature.FeatureName = :feat');
		}
		$this->deleteFeatureStatement->bindValue(':id', $this->database->itemDAO()->getItemId($item), \PDO::PARAM_INT);
		$this->deleteFeatureStatement->bindValue(':feat', $featureName, \PDO::PARAM_STR);
		$this->deleteFeatureStatement->execute();
    }

	public function updateDeleteFeatures(ItemUpdate $item) {
		$features = $item->getFeatures();

		if(empty($features)) {
			return;
		}

		$newItem = new Item($item->getCode());
		foreach($features as $feature => $value) {
			$this->deleteFeature($item, $feature);
			if($value !== null) {
				$newItem->addFeature($feature, $value);
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
		    $this->featureNumberStatement = $pdo->prepare('INSERT INTO ItemFeature (FeatureID, ItemID, `Value`)   SELECT FeatureID, :item, :val FROM Feature WHERE Feature.FeatureName = :feature');
	    }
	    if($this->featureTextStatement === null) {
		    $this->featureTextStatement = $pdo->prepare('INSERT INTO ItemFeature (FeatureID, ItemID, `ValueText`) SELECT FeatureID, :item, :val FROM Feature WHERE Feature.FeatureName = :feature');
	    }
	    if($this->featureEnumStatement === null) {
		    $this->featureEnumStatement = $pdo->prepare('INSERT INTO ItemFeature (FeatureID, ItemID, `ValueEnum`) SELECT FeatureID, :item, :val FROM Feature WHERE Feature.FeatureName = :feature');
	    }

	    $itemId = $this->database->itemDAO()->getItemId($item);
		$this->featureNumberStatement->bindValue(':item', $itemId, \PDO::PARAM_INT);
		$this->featureTextStatement->bindValue(':item', $itemId, \PDO::PARAM_INT);
		$this->featureEnumStatement->bindValue(':item', $itemId, \PDO::PARAM_INT);

		foreach($features as $feature => $value) {
			$featureType = $this->database->featureDAO()->getFeatureTypeFromName($feature);
			switch($featureType) {
				// was really tempted to use variable variables here...
				case self::FEATURE_TEXT:
					$this->featureTextStatement->bindValue(':feature', $feature);
					$this->featureTextStatement->bindValue(':val', $value);
					$this->featureTextStatement->execute();
					break;
				case self::FEATURE_NUMBER:
					$this->featureNumberStatement->bindValue(':feature', $feature);
					$this->featureNumberStatement->bindValue(':val', $value);
					$this->featureNumberStatement->execute();
					break;
				case self::FEATURE_ENUM:
					$this->featureEnumStatement->bindValue(':feature', $feature);
					$this->featureEnumStatement->bindValue(':val', $this->database->featureDAO()->getFeatureValueEnumFromName($feature, $value));
					$this->featureEnumStatement->execute();
					break;
				default:
					throw new \LogicException('Unknown feature type ' . $featureType . ' returned by getFeatureTypeFromName (should never happen unless a cosmic ray flips a bit somewhere)');
			}
		}
	}

	private $getFeatureListStatement = null;

	public function getFeatureList() {
		if($this->getFeatureListStatement === null) {
			$this->getFeatureListStatement = $this->getPDO()->prepare('SELECT FeatureName FROM Feature');
		}
		$this->getFeatureListStatement->execute();
		if($this->getFeatureListStatement->rowCount() > 0) {
			$result = [];
			while(($row = $this->getFeatureListStatement->fetch(\PDO::FETCH_ASSOC)) !== false) {
				$result[] = $row['FeatureName'];
			}
			$this->getFeatureListStatement->closeCursor();
			return $result;
		}
		$this->getFeatureListStatement->closeCursor();
		return [];
	}
}