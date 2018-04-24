<?php

namespace WEEEOpen\Tarallo\Server;


class ItemLocationValidator {
	/**
	 * Correctly move RAMs and CPUs from cases to motherboards, if case contains a motherboard.
	 *
	 * @param Item $item The item being places
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
	 * Check that item nesting makes sense (e.g. no CPUs inside HDDs)
	 *
	 * @param Item $item Item to be checked
	 * @param Item $parent Its parent
	 *
	 * @throws ItemNestingException if items are invalidly nested
	 *
	 * @TODO use when creating new items, too
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
	}

	private static function isExpansionCard($type) {
		return strlen($type) > 5 && substr($type, -5) === '-card';
	}
}
