<?php

namespace WEEEOpen\Tarallo\Server;

use WEEEOpen\Tarallo\Server\Database\TreeDAO;

/**
 * Regular items
 *
 * @package WEEEOpen\Tarallo
 */
class Item extends ItemIncomplete implements \JsonSerializable {
	use ItemFeatures;
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
				throw new \InvalidArgumentException("Item code must be a non-empty alphanumeric string or null");
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
	 * Has code already been set? Do we need to generate it?
	 *
	 * @return bool
	 *
	 * @see setCode to set it
	 */
	public function hasCode() {
		return $this->code !== null;
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
	 * Add ancestor to location.
	 *
	 * @param int $distance 1 for direct parent, etc...
	 * @param ItemIncomplete $ancestor
	 *
	 * @throws InvalidParameterException if code is invalid
	 * @throws \InvalidArgumentException if distance is less than 1
	 *
	 * @deprecated make private to avoid holes?
	 */
	public function addAncestor($distance, ItemIncomplete $ancestor) {
		$distance = (int) $distance;
		if($distance < 1) {
			throw new \InvalidArgumentException('Ancestor distance too small: ' . $ancestor->getCode() . ' is at distance ' . $distance . ' from its descendant ' . $this->getCode());
		}

		$this->location[--$distance] = $ancestor;
	}

	/**
	 * Add all ancestors
	 *
	 * @param ItemIncomplete[] $ancestors in order from direct parent from most distant one
	 *
	 * @see TreeDAO::getPathTo() function to obtain the needed array
	 */
	public function addAncestors(array $ancestors) {
		$d = 1;
		foreach($ancestors as $ancestor) {
			$this->addAncestor($d, $ancestor);
			$d++;
		}
	}

	/**
	 * Get full path to item.
	 * Work for non-head items, too.
	 *
	 * @throws \InvalidArgumentException if distance is less than 1
	 * @return ItemIncomplete[] first item is direct parent, second is parent's parent, ad so on
	 */
	public function getPath() {
		$result = [];
		$last = $this;
		do {
			foreach($last->location as $ancestor) {
				// array_merge returns a new array, not very efficient if called multiple times...
				$result[] = $ancestor;
			}
			$last = end($result);
		} while($last !== false && $last instanceof Item && count($last->location) > 0);

		// TODO: apply memoization ($this->location = $result)?

		return $result;
	}

	/**
	 * Get features and default features, all in one.
	 * Some features may override default features.
	 *
	 * @return array
	 */
	public function getCombinedFeatures() {
		if($this->product === null) {
			return $this->getFeatures();
		} else {
			return array_merge($this->product->getFeatures(), $this->getFeatures());
		}
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
		if(empty($this->location)) {
			$item->addAncestor(1, $this);
		} else {
			$item->location = array_merge([$this], $this->getPath());
		}

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
			$array['location'] = $this->getPath();
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