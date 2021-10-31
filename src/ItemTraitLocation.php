<?php

namespace WEEEOpen\Tarallo;

trait ItemTraitLocation
{
	protected $location = [];

	/**
	 * Add all ancestors
	 *
	 * @param ItemWithCode[] $ancestors in order from direct parent from most distant one
	 *
	 * @see TreeDAO::getPathTo() function to obtain the needed array
	 */
	public function addAncestors(array $ancestors)
	{
		foreach ($ancestors as $ancestor) {
			$this->location[] = $ancestor;
		}
	}

	/**
	 * Get full path to item.
	 * Work for non-head items, too.
	 *
	 * @return ItemWithCode[] first item is the tree root, last item is direct parent
	 */
	public function getPath(): array
	{
		$result = [];
		$last = $this;
		do {
			foreach ($last->location as $ancestor) {
				// array_merge returns a new array, not very efficient if called multiple times...
				$result[] = $ancestor;
			}
			$last = end($result);
		} while ($last !== false && $last instanceof Item && count($last->location) > 0);

		// TODO: apply memoization ($this->location = $result)?

		return $result;
	}

	/**
	 * Get parent of this item (or null if none)
	 *
	 * @return null|ItemWithCode
	 */
	public function getParent(): ?ItemWithCode
	{
		$path = $this->getPath();
		if (count($path) === 0) {
			return null;
		}
		return $path[count($path) - 1];
	}
}
