<?php

namespace WEEEOpen\Tarallo\Server;

use WEEEOpen\Tarallo\Server\Database\TreeDAO;

/**
 * Regular items
 *
 * @package WEEEOpen\Tarallo
 */
class Item extends ItemFeatures implements \JsonSerializable {
	protected $contents = [];
	private $location = [];
	protected $product = null;
	public $token = null; // TODO: change to protected sometimes in the future (this is for adding items with a token, not selecting items with a token)
	protected $deletedAt = null;
	protected $lostAt = null;

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
	 * Add all ancestors
	 *
	 * @param ItemIncomplete[] $ancestors in order from direct parent from most distant one
	 *
	 * @see TreeDAO::getPathTo() function to obtain the needed array
	 */
	public function addAncestors(array $ancestors) {
		foreach($ancestors as $ancestor) {
			$this->location[] = $ancestor;
		}
	}

	/**
	 * Get full path to item.
	 * Work for non-head items, too.
	 *
	 * @return ItemIncomplete[] first item is the tree root, last item is direct parent
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
	 * Get parent of this item (or null if none)
	 *
	 * @return null|ItemIncomplete
	 */
	public function getParent(): ?ItemIncomplete {
		$path = $this->getPath();
		if(count($path) === 0) {
			return null;
		}
		return $path[count($path) - 1];
	}

	/**
	 * Get features and default features, all in one.
	 * Some features may override default features.
	 *
	 * @return Feature[]
	 * @deprecated
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
	 * @return Item $this parent item (itself)
	 */
	public function addContent(Item $item) {
		$this->contents[] = $item;
		// This was cool, but caused problems during serialization. Serious problems.
		// It made the serialization fail because of recursion.
		//if(empty($this->location)) {
		//	$item->location[] = $this;
		//} else {
		//	$item->location = array_merge([$this], $this->getPath());
		//}

		return $this;
	}

	public function removeContent(Item $item) {
		$key = array_search($item, $this->contents);
		if($key === false) {
			throw new \InvalidArgumentException('Cannot remove item ' . $item . ' from ' . $this . ': not here');
		} else {
			unset($this->contents[$key]);
		}
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
			foreach($this->features as $feature) {
				/** @var Feature $feature */
				$name = $feature->name;
				$value = $feature->value;
				$array['features'][$name] = $value;
			}
		}
		if(!empty($this->contents)) {
			$array['contents'] = $this->contents;
		}
		if(!empty($this->location)) {
			$array['location'] = $this->getPath();
		}
		if($this->deletedAt !== null) {
			$array['deleted_at'] = $this->deletedAt;
		}
		if($this->lostAt !== null) {
			$array['lost_at'] = $this->deletedAt;
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

	// TODO: consider the builder pattern as an alternative
	public function setDeletedAt(\DateTime $when) {
		$this->deletedAt = $when;
	}

	public function setLostAt(\DateTime $when) {
		$this->lostAt = $when;
	}

	public function getDeletedAt(): ?\DateTime {
		return $this->deletedAt;
	}

	public function getLostAt(): ?\DateTime {
		return $this->lostAt;
	}
}
