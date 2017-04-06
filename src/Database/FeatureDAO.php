<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\InvalidParameterException;

class FeatureDAO extends DAO {

    public function getFeatures($items) {
        if(!is_array($items)) {
            throw new \InvalidArgumentException('$items must be an array of item IDs');
        }

        if(empty($items)) {
            return [];
        }

        $inItemID = $this->multipleIn(':item', $items);
        $s = $this->getPDO()->prepare('
            SELECT ItemID, FeatureName, COALESCE(ItemFeature.`Value`, ItemFeature.ValueText, FeatureValue.ValueText) AS `Value`
            FROM Feature, ItemFeature, FeatureValue
            WHERE ItemFeature.FeatureID = Feature.FeatureID AND (ItemFeature.ValueEnum = FeatureValue.ValueEnum OR ItemFeature.ValueEnum IS NULL)
            AND ItemID ' . $inItemID . '
		');

        foreach($items as $arrayID => $itemID) {
            if(!is_int($itemID) || !is_string($itemID)) {
                throw new \InvalidArgumentException('Item IDs must be integers or strings, ' . gettype($itemID) . ' given');
            }
            $s->bindParam(':item' . $arrayID, $itemID);
        }
        $s->execute();
        $reverseItems = array_flip($items);
        $result = [];
        if($s->rowCount() === 0) {
            $all = $s->fetchAll();
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
        switch((int) $this->featureTypeStatement->fetch(\PDO::FETCH_NUM)[0]) {
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
        $result = $this->featureEnumNameStatement->fetch();
        return $result['ValueEnum'];
    }

    private $featureNumberStatement = null;
    private $featureTextStatement = null;
    private $featureEnumStatement = null;

	public function addFeatures($itemId, $features) {
		static $featureNumberStatement = null;

    	if(!is_numeric($itemId) || !($itemId > 0)) {
    		throw new \InvalidArgumentException('Item ID must be a positive integer (or string representing a positive integer)');
	    }

	    if(!is_array($features)) {
		    throw new \InvalidArgumentException('Features must be passed as an array');
	    }

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

		$this->featureNumberStatement->bindValue(':item', $itemId);
		$this->featureTextStatement->bindValue(':item', $itemId);
		$this->featureEnumStatement->bindValue(':item', $itemId);

		foreach($features as $feature => $value) {
			$featureType = $this->featureDAO()->getFeatureTypeFromName($feature);
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
					$this->featureEnumStatement->bindValue(':val', $this->featureDAO()->getFeatureValueEnumFromName($feature, $value));
					$this->featureEnumStatement->execute();
					break;
				default:
					throw new \LogicException('Unknown feature type ' . $featureType . ' returned by getFeatureTypeFromName (should never happen unless a cosmic ray flips a bit somewhere)');
			}
		}
	}
}