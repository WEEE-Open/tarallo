<?php


namespace WEEEOpen\Tarallo;


trait ItemTraitContent {
	protected $contents = [];

	/**
	 * Add another item inside
	 *
	 * @param ItemWithCode $item
	 *
	 * @return $this aka the parent item to the one you just added
	 */
	public function addContent(ItemWithCode $item) {
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

	/**
	 * @param ItemWithCode $item
	 */
	public function removeContent(ItemWithCode $item) {
		$key = array_search($item, $this->contents);
		if($key === false) {
			throw new \InvalidArgumentException("Cannot remove item {$item} from {$this}: not here");
		} else {
			unset($this->contents[$key]);
		}
	}

	/**
	 * Get items located inside
	 *
	 * @return Item[]
	 */
	public function getContent(): array {
		return $this->contents;
	}


	/**
	 * Get items located inside, at any depth, flattened to a single array
	 *
	 * @return Item[]
	 */
	public function getFlatContent(): array {
		$result = $this->contents;
		foreach($this->contents as $item) {
			/** @var Item $item */
			$result = array_merge($result, $item->getFlatContent());
		}
		return $result;
	}
}