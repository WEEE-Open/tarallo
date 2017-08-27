<?php
namespace WEEEOpen\Tarallo;


class Item extends ItemIncomplete implements \JsonSerializable {
	private $features = [];
	private $featuresDefault = [];
	private $content = [];
	private $location = [];
	protected $defaultCode = null;

	public function __construct($code, $defaultCode = null) {
		parent::__construct($code);
		if($defaultCode !== null) {
			try {
				$this->defaultCode = $this->sanitizeCode($defaultCode);
			} catch(InvalidParameterException $e) {
				throw new InvalidParameterException('Failed setting parent Item: ' . $e->getMessage());
			}
		}
	}

	private static function featureNameIsValid($name) {
		if(is_string($name)) {
			return true;
		} else {
			return false;
		}
	}

	protected static function featureValueIsValid($value) {
		// values are all unsigned ints in the database
		if(is_string($value) || (is_int($value) && $value >= 0)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param string $name
	 * @param string|int $value
	 *
	 * @return Item $this
	 */
	public function addFeature($name, $value) {
		$this->addFeatureInternal($name, $value, $this->features);
		return $this;
	}

	/**
	 * Add ancestor to location.
	 *
	 * @param int $distance 1 for direct parent, etc...
	 * @param string $code ancestor code
	 * @throws InvalidParameterException if code is invalid
	 * @throws \InvalidArgumentException if distance is less than 1
	 */
	public function addAncestor($distance, $code) {
		$distance = (int) $distance;
		if($distance < 1) {
			throw new \InvalidArgumentException('Ancestor distance too small: ' . $code .' is at distance ' . $distance . ' from its descendant ' . $this->getCode());
		}

		$this->location[--$distance] = new ItemIncomplete($code);
	}

	/**
	 * Get ancestor of an item from its "location", if set.
	 * Null if not set.
	 *
	 * @param int $distance 1 for direct parent, 2 for parent's parent, etc... 0 or less is invalid.
	 * @throws \InvalidArgumentException if distance is less than 1
	 * @return ItemIncomplete|null parent code
	 */
	public function getAncestor($distance) {
		if($distance < 1) {
			throw new \InvalidArgumentException('Ancestor distance ' . $distance . ' too small in ' . $this->getCode());
		}
		$distance--;

		if(isset($this->location[$distance])) {
			return $this->location[$distance];
		} else {
			return null;
		}
	}

	/**
	 * @param string $name
	 * @param string|int $value
	 *
	 * @return Item $this
	 */
	public function addFeatureDefault($name, $value) {
		$this->addFeatureInternal($name, $value, $this->featuresDefault);
		return $this;
	}

	private function addFeatureInternal($name, $value, &$array) {
		$this->checkFeature($name, $value, $array);
		$array[$name] = $value;
	}

	private function checkFeature($name, $value, $array) {
		if(!self::featureNameIsValid($name)) {
			throw new \InvalidArgumentException('Feature name must be a string, ' . gettype($name) . ' given');
		}
		if(!self::featureValueIsValid($value)) {
			throw new \InvalidArgumentException('Feature value must be a string or positive integer, ' . gettype($name) . ' given');
		}
		if(isset($array[$name])) {
			throw new InvalidParameterException('Feature ' . $name . ' already inserted into item ' . (string) $this);
		}
	}

	/**
	 * @param array $features
	 *
	 * @return Item $this
	 */
	public function addMultipleFeatures($features) {
		if(!is_array($features)) {
			throw new \InvalidArgumentException('Features must be passed as an array');
		}
		foreach($features as $name => $value) {
			$this->addFeature($name, $value);
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function getFeatures() {
		return $this->features;
	}

	/**
	 * @return array
	 */
	public function getFeaturesDefault() {
		return $this->featuresDefault;
	}

	/**
	 * @return null|string
	 */
	public function getDefaultCode() {
		return $this->defaultCode;
	}

	/**
	 * @param Item $item
	 *
	 * @return Item $this
	 */
	public function addContent(Item $item) {
		$this->content[] = $item;
		return $this;
	}

	/**
	 * @return Item[]
	 */
	public function getContent() {
		return $this->content;
	}

	public function jsonSerialize() {
		$array = [];
		$array['code'] = $this->getCode();
		if(!empty($this->features)) {
			$array['features'] = $this->features;
		}
		if(!empty($this->content)) {
			$array['content'] = $this->content;
		}
		if(!empty($this->featuresDefault)) {
			$array['features_default'] = $this->featuresDefault;
		}
		if($this->defaultCode !== null) {
			$array['default'] = $this->defaultCode;
		}
		if(!empty($this->location)) {
			$array['location'] = array_reverse($this->location);
		}

		return $array;
	}

	public function __toString() {
		if(isset($this->features['type'])) {
			return parent::getCode() . ' (' . $this->features['type'] . ')';
		} else if(isset($this->featuresDefault['type'])) {
			return parent::getCode() . ' (' . $this->featuresDefault['type'] . ')';
		} else {
			return parent::getCode();
		}
	}
}