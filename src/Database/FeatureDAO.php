<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\InvalidParameterException;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\Query\SearchTriplet;

class FeatureDAO extends DAO {

    public function getFeatures($items) {
        if(!is_array($items)) {
            throw new \InvalidArgumentException('$items must be an array of item IDs');
        }

        if(empty($items)) {
            return [];
        }

        $inItemID = $this->multipleIn(':item', $items);
        $s = $this->getPDO()->prepare('SELECT ItemID, Feature.FeatureName, COALESCE(ItemFeature.`Value`, ItemFeature.ValueText, FeatureValue.ValueText) AS `Value`
            FROM Feature, ItemFeature
            LEFT JOIN FeatureValue ON ItemFeature.FeatureID = FeatureValue.FeatureID
            WHERE ItemFeature.FeatureID = Feature.FeatureID AND (ItemFeature.ValueEnum = FeatureValue.ValueEnum OR ItemFeature.ValueEnum IS NULL)
            AND ItemID IN (' . $inItemID . ');
		');

        foreach($items as $arrayID => $itemID) {
            if(!is_int($itemID) && !is_string($itemID)) {
                throw new \InvalidArgumentException('Item IDs must be integers or strings, ' . gettype($itemID) . ' given');
            }
            $s->bindParam(':item' . $arrayID, $itemID);
        }
        $s->execute();
        $reverseItems = array_flip($items);
        $result = [];
        if($s->rowCount() === 0) {
            $all = $s->fetchAll();
	        $s->closeCursor();
            foreach($items as $k => $v) {
                $result[$k] = [];
            }
            foreach($all as $row) {
                $result[$reverseItems[$row['ItemID']]][$row['FeatureName']] = $row['FeatureValue'];
            }
        }
        return $result;
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
        if($this->featureTypeStatement->rowCount() === 0) {
            throw new InvalidParameterException('Unknown feature name ' . $featureName);
        }
        $type = (int) $this->featureTypeStatement->fetch(\PDO::FETCH_NUM)[0];
	    $this->featureTypeStatement->closeCursor();
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
	 * @return string sequence of WHERE statements(?) (no "WHERE" keyword itself)
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
				$where .= '(Feature.Name = :searchname' . $numericKey . ' AND Value ' . $searches[$numericKey]->getCompare() . ' :searchvalue' . $numericKey . ') OR ';
			} else {
				$where .= '(Feature.Name = :searchname' . $numericKey . ' AND COALESCE(ItemFeature.ValueText, FeatureValue.ValueText) LIKE :searchvalue' . $numericKey . ') OR ';
			}
		}
		$where = substr(0, strlen($where) - 4); // remove last OR

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
        if($this->featureEnumNameStatement->rowCount() === 0) {
            throw new InvalidParameterException('Invalid value ' . $featureValueText . ' for feature ' . $featureName);
        }
        $result = $this->featureEnumNameStatement->fetch(\PDO::FETCH_NUM);
	    $this->featureEnumNameStatement->closeCursor();
        return $result[0];
    }

    private $featureNumberStatement = null;
    private $featureTextStatement = null;
    private $featureEnumStatement = null;

	public function addFeatures(Item $item) {
		static $featureNumberStatement = null;

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
		$this->featureNumberStatement->bindValue(':item', $itemId);
		$this->featureTextStatement->bindValue(':item', $itemId);
		$this->featureEnumStatement->bindValue(':item', $itemId);

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
}