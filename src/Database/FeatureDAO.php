<?php

namespace WEEEOpen\Tarallo\Database;

use WEEEOpen\Tarallo\InvalidParameterException;

class FeatureDAO extends DAO {

    public function getFeatures($items) {
        if(!is_array($items)) {
            // TODO: use Item? But that's leaking the database through the abstraction...
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
     * @deprecated use join and COALESCE instead?
     */
    public function getFeatureTypeFromName($featureName) {
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
}