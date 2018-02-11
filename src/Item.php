<?php

namespace WEEEOpen\Tarallo\Server;

/**
 * Regular items
 *
 * @package WEEEOpen\Tarallo
 */
class Item extends ItemIncomplete implements \JsonSerializable {
	protected $features = [];
	protected $contents = [];
	private $location = [];
	protected $product = null;

	/**
	 * Create an Item
	 *
	 * @param string|null $code Item code, or null if not yet known
	 * @param Product $product Product referenced by Item, default null
	 */
	public function __construct($code, Product $product = null) {
		if($code === null) {
			$this->code = null;
		} else {
			try {
				parent::__construct($code);
			} catch(\InvalidArgumentException $e) {
				throw new \InvalidArgumentException("Item code must be a non-empty string or null");
			}
		}
		$this->product = $product;
	}

	/**
	 * Set code, unless it has already been set
	 *
	 * @param string $code Item code
	 */
	public function setCode($code) {
		if($this->code !== null) {
			throw new \LogicException('Cannot change code for item ' . $this->getCode() . ' since it\'s already set');
		}

		parent::__construct($code);
	}

	/**
	 * Get code, if it's set
	 *
	 * @return string
	 * @see setCode
	 */
	public function getCode() {
		if($this->code === null) {
			throw new \LogicException('Trying to read code from an Item without code');
		}

		return parent::getCode();
	}

	/**
	 * @param Feature $feature
	 *
	 * @return Item $this
	 */
	public function addFeature(Feature $feature) {
		if(isset($this->features[$feature->name])) {
			throw new \InvalidArgumentException('Feature ' . $feature->name . ' already added to ' . (string) $this);
		}
		$this->features[$feature->name] = $feature;

		return $this;
	}

	/**
	 * @param string $name
	 *
	 * @return Item $this
	 */
	public function deleteFeature($name) {
		unset($this->features[$name]);

		return $this;
	}

	/**
	 * Add ancestor to location.
	 *
	 * @param int $distance 1 for direct parent, etc...
	 * @param string $code ancestor code
	 *
	 * @throws InvalidParameterException if code is invalid
	 * @throws \InvalidArgumentException if distance is less than 1
	 */
	public function addAncestor($distance, $code) {
		$distance = (int) $distance;
		if($distance < 1) {
			throw new \InvalidArgumentException('Ancestor distance too small: ' . $code . ' is at distance ' . $distance . ' from its descendant ' . $this->getCode());
		}

		$this->location[--$distance] = new ItemIncomplete($code);
	}

	/**
	 * Get ancestor of an item from its "location", if set.
	 * Null if not set.
	 *
	 * @param int $distance 1 for direct parent, 2 for parent's parent, etc... 0 or less is invalid.
	 *
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
	 * @param array $features
	 *
	 * @return Item $this
	 */
	public function addMultipleFeatures(array $features) {
		foreach($features as $feature) {
			$this->addFeature($feature);
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
	 * Get features and default features, all in one.
	 * Some features may override default features.
	 *
	 * @return array
	 */
	public function getCombinedFeatures() {
		return array_merge($this->product->getFeatures(), $this->getFeatures());
	}

	/**
	 * Get product referenced by this item
	 *
	 * @return null|Product
	 */
	public function getProduct() {
		return $this->product;
	}

	/**
	 * Add another item inside
	 *
	 * @param Item $item
	 *
	 * @return Item $this
	 */
	public function addContent(Item $item) {
		$this->contents[] = $item;

		return $this;
	}

	/**
	 * Get items located inside
	 *
	 * @return Item[]
	 */
	public function getContents() {
		return $this->contents;
	}

	public function jsonSerialize() {
		$array = [];
		$array['code'] = $this->getCode();
		if(!empty($this->features)) {
			$array['features'] = $this->features;
		}
		if(!empty($this->contents)) {
			$array['contents'] = $this->contents;
		}
		if(!empty($this->location)) {
			ksort($this->location);
			$array['location'] = array_reverse($this->location);
		}

		return $array;
	}

	public function __toString() {
		if(isset($this->features['type'])) {
			return parent::getCode() . ' (' . $this->features['type'] . ')';
		} else if($this->product !== null && isset($this->product->getFeatures()['type'])) {
			return parent::getCode() . ' (' . $this->product->getFeatures()['type'] . ')';
		} else {
			return parent::getCode();
		}
	}
}