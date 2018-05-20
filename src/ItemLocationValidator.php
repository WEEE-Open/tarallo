<?php

namespace WEEEOpen\Tarallo\Server;


class ItemLocationValidator {
	/**
	 * Correctly move RAMs and CPUs from cases to motherboards, if case contains a motherboard.
	 *
	 * @param Item $item The item being placed
	 * @param Item $parent Its location (case or anything else)
	 *
	 * @return Item Correct parent (given one or a motherboard)
	 */
	public static function reparent(Item $item, Item $parent) {
		$type = $item->getFeature('type');
		$parentType = $parent->getFeature('type');

		if($type === null || $parentType == null) {
			return $parent;
		}

		$type = $type->value;
		$parentType = $parentType->value;

		if($parentType === 'case') {
			if($type === 'cpu' || $type === 'ram' || self::isExpansionCard($type)) {
				foreach($parent->getContents() as $maybeMobo) {
					$maybeType = $maybeMobo->getFeature('type');
					if($maybeType !== null && $maybeType->value === 'motherboard') {
						return $maybeMobo;
					}
				}
			}
		}

		return $parent;
	}

	/**
	 * Reparent all items, recursively.
	 *
	 * @see reparent
	 *
	 * @param Item $item The root item being places
	 * @param Item|null $parent Its location (case or anything else)
	 *
	 * @return Item|null Correct parent for root item or null if was null
	 */
	public static function reparentAll(Item $item, Item $parent = null) {
		if($parent !== null) {
			$parent = self::reparent($item, $parent);
		}

		$fixups = [];
		foreach($item->getContents() as $subitem) {
			$newParent = self::reparentAll($subitem, $item);
			if($newParent !== $item) {
				// Avoid changing arrays while foreachs are iterating over them
				$fixups[] = [$subitem, $item, $newParent];
			}
		}

		if(!empty($fixups)) {
			foreach($fixups as $row) {
				/** @var Item[] $row */
				$row[1]->removeContent($row[0]);
				$row[2]->addContent($row[0]);
			}
		}

		return $parent;
	}

	/**
	 * Check that item nesting makes sense (e.g. no CPUs inside HDDs)
	 *
	 * @param Item $item Item to be checked
	 * @param Item $parent Its parent
	 *
	 * @throws ItemNestingException if items are invalidly nested
	 */
	public static function checkNesting(Item $item, Item $parent) {
		$type = $item->getFeature('type');
		$parentType = $parent->getFeature('type');

		if($type === null || $parentType == null) {
			return;
		}

		$type = $type->value;
		$parentType = $parentType->value;

		if($type === 'case' && $parentType !== 'location') {
			throw new ItemNestingException('Cases should be inside a location',
				$item->hasCode() ? $item->getCode() : '', $parent->hasCode() ? $parent->getCode() : '');
		} else if($type === 'ram' || $type === 'cpu' || self::isExpansionCard($type)) {
			if($parentType !== 'case' && $parentType !== 'location' && $parentType !== 'motherboard') {
				throw new ItemNestingException('RAMs, CPUs and expansion cards cards should be inside a case, location or motherboard',
					$item->hasCode() ? $item->getCode() : '', $parent->hasCode() ? $parent->getCode() : '');
			}
		} else {
			if($parentType !== 'case' && $parentType !== 'location') {
				throw new ItemNestingException('Normal items can be placed only inside cases and locations',
					$item->hasCode() ? $item->getCode() : '', $parent->hasCode() ? $parent->getCode() : '');
			}
		}

		if($type === 'cpu' && $parentType === 'motherboard') {
			if(!self::compareFeature($item, $parent, 'cpu-socket')) {
				$itemValue = $item->getFeature('ram-form-factor');
				$parentValue = $parent->getFeature('ram-form-factor');
				throw new ItemNestingException("Incompatible socket: CPU is $itemValue, motherboard is $parentValue",
						$item->hasCode() ? $item->getCode() : '', $parent->hasCode() ? $parent->getCode() : '');
			}
		}

		if($type === 'ram' && $parentType === 'motherboard') {
			if(!self::compareFeature($item, $parent, 'ram-form-factor')) {
				$itemValue = $item->getFeature('ram-form-factor');
				$parentValue = $parent->getFeature('ram-form-factor');
				throw new ItemNestingException("Incompatible form factor: RAM is $itemValue, motherboard is $parentValue",
					$item->hasCode() ? $item->getCode() : '', $parent->hasCode() ? $parent->getCode() : '');
			}
			if(!self::compareFeature($item, $parent, 'ram-type')) {
				$itemValue = $item->getFeature('ram-type');
				$parentValue = $parent->getFeature('ram-type');
				throw new ItemNestingException("Incompatible standard: RAM is $itemValue, motherboard is $parentValue",
					$item->hasCode() ? $item->getCode() : '', $parent->hasCode() ? $parent->getCode() : '');
			}
		}
	}

	private static function isExpansionCard($type) {
		return strlen($type) > 5 && substr($type, -5) === '-card';
	}

	private static function compareFeature(Item $item, Item $parent, string $feature) {
		$itemFeature = $item->getFeature($feature);
		$parentFeature = $parent->getFeature($feature);
		if($itemFeature !== null && $parentFeature !== null) {
			return $itemFeature->value === $parentFeature->value;
		}

		return true;
	}
}
