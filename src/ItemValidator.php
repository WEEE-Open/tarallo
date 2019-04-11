<?php

namespace WEEEOpen\Tarallo\Server;


class ItemValidator {
	/**
	 * Move item (or sub-items) to the correct place, if possible. Move features around, if possible.
	 * All fixes are applied to the entire item tree starting from $item, so limit the depth if it's a move operation...
	 *
	 * @param Item $item The item being placed in a location
	 * @param Item|null $parent The location
	 *
	 * @return Item|null Correct parent (or the given one if unchanged, or null if null was given)
	 */
	public static function fixupLocation(Item $item, ?Item $parent): ?Item {
		$parent = self::reparentAll($item, $parent);

		return $parent;
	}

	public static function fixupFeatures(Item $item) {
		// This prevents any accidental modification to the parent, since that's
		// an item already in the database and not modified in the last request.
		// Editing an item shouldn't have side effects on other items around it,
		// basically.
		self::moveFeaturesAll($item /*, null*/);
	}

	/**
	 * Correctly move RAMs and CPUs from cases to motherboards, if case contains a motherboard.
	 *
	 * @param Item $item The item being placed
	 * @param Item $parent Its location (case or anything else)
	 *
	 * @return Item Correct parent (given one or a motherboard)
	 */
	private static function reparent(Item $item, Item $parent): Item {
		$type = self::getOrNull($item, 'type');
		$parentType = self::getOrNull($parent, 'type');

		if($type === null || $parentType == null) {
			return $parent;
		}

		if($parentType === 'case') {
			if($type === 'cpu' || $type === 'ram' || self::isExpansionCard($type)) {
				$mobo = self::findMobo($parent);
				if($mobo !== null) {
					return $mobo;
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
	private static function reparentAll(Item $item, ?Item $parent) {
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

	private static function moveFeaturesAll(Item $item) {
		$type = self::getOrNull($item, 'type');

		if($type !== null) {
			if($type === 'case') {
				$ff = self::getOrNull($item, 'motherboard-form-factor');
				if($ff === 'proprietary-laptop') {
					if(self::has($item, 'usb-ports-n')) {
						$mobo = self::findMobo($item);
						if(!self::has($mobo, 'usb-ports-n')) {
							// TODO: this will end badly when products are implemented...
							$mobo->addFeature($item->getFeature('usb-ports-n'));
							$item->removeFeature('usb-ports-n');
						}
					}
				}
			}
		}

		foreach($item->getContents() as $subitem) {
			self::moveFeaturesAll($subitem);
		}
	}

	/**
	 * Check that item features make sense
	 *
	 * @param Item $item Item to be checked
	 *
	 * @throws ValidationException if item contains invalid features
	 * @TODO: make this thing work for PATCH requests... Or don't?
	 */
	public static function validateFeatures(Item $item) {
		$type = self::getOrNull($item, 'type');
		if($type !== null) {
			if($type === 'case') {
				$motherboardFF = self::getOrNull($item, 'motherboard-form-factor');
				switch($motherboardFF) {
					case 'proprietary-laptop':
						// It's a laptop, reject features that make sense only in desktops.
						if(self::has($item, 'usb-ports-n')) {
							throw new ValidationException(
								'A laptop does not have USB ports on the case, but on the motherboard only. Remove the "USB ports" feature from the case or change the motherboard form factor.'
							);
						}
						if(self::has($item, 'psu-form-factor')) {
							if($item->getFeature('psu-form-factor') !== 'proprietary') {
								// Well, it may contain a power supply, if it's something really old... but it won't be standard anyway.
								throw new ValidationException(
									'A laptop does not have a standard internal PSU. Remove the "PSU form factor" feature from the case or change the motherboard form factor.'
								);
							}
						}
						break;
					default:
						// It's a desktop, reject features that make sense only in laptops
						if(self::has($item, 'power-connector')) {
							throw new ValidationException(
								"A desktop computer case does not have any power connector. Remove that feature or change the motherboard form factor."
							);
						}
						if(self::has($item, 'psu-volt')) {
							throw new ValidationException(
								'A desktop computer case does not require a laptop PSU with a single voltage. Remove the "Power supply voltage" feature or change the motherboard form factor.'
							);
						}
						if(self::has($item, 'psu-ampere')) {
							throw new ValidationException(
								'A desktop computer case does not require a laptop PSU with a single voltage. Remove the "Power supply current" feature or change the motherboard form factor.'
							);
						}
						break;
					case null:
					case 'proprietary':
					case 'miniitx':
						// Unknown form factor or one that can contain any feature, do nothing
						// See also: https://github.com/WEEE-Open/tarallo/issues/63#issue-388831295
						break;
				}
			}
		}

		foreach($item->getContents() as $subitem) {
			self::validateFeatures($subitem);
		}
	}

	/**
	 * Check that item nesting makes sense (e.g. no CPUs inside HDDs)
	 *
	 * @param Item $item Item to be checked
	 * @param Item $parent Its parent, or none if a root item
	 *
	 * @throws ItemNestingException if items are invalidly nested
	 * @throws ValidationException if item cannot be a root item and has a null parent
	 */
	public static function validateLocation(Item $item, ?Item $parent) {
		if($parent === null) {
			self::checkRoot($item);
		} else {
			self::checkNesting($item, $parent);
		}
	}

	/**
	 * Check that item nesting makes sense (e.g. no CPUs inside HDDs)
	 *
	 * @param Item $item Item to be checked
	 * @param Item $parent Its parent
	 *
	 * @throws ItemNestingException if items are invalidly nested
	 */
	private static function checkNesting(Item $item, ?Item $parent): void {
		$type = self::getOrNull($item, 'type');
		$parentType = self::getOrNull($parent, 'type');

		if($type === null || $parentType == null) {
			return;
		}

		if($type === 'case' && $parentType !== 'location') {
			throw new ItemNestingException(
				'Cases should be inside a location',
				$item->hasCode() ? $item->getCode() : '', $parent->hasCode() ? $parent->getCode() : ''
			);
		} else if($type === 'ram' || $type === 'cpu' || self::isExpansionCard($type)) {
			if($parentType !== 'case' && $parentType !== 'location' && $parentType !== 'motherboard') {
				throw new ItemNestingException(
					'RAMs, CPUs and expansion cards cards should be inside a case, location or motherboard',
					$item->hasCode() ? $item->getCode() : '', $parent->hasCode() ? $parent->getCode() : ''
				);
			}
		} else {
			if($parentType !== 'case' && $parentType !== 'location') {
				throw new ItemNestingException(
					'Normal items can be placed only inside cases and locations',
					$item->hasCode() ? $item->getCode() : '', $parent->hasCode() ? $parent->getCode() : ''
				);
			}
		}

		if($type === 'cpu' && $parentType === 'motherboard') {
			if(!self::compareFeature($item, $parent, 'cpu-socket')) {
				$itemValue = $item->getFeature('cpu-socket');
				$parentValue = $parent->getFeature('cpu-socket');
				throw new ItemNestingException(
					"Incompatible socket: CPU is $itemValue, motherboard is $parentValue",
					$item->hasCode() ? $item->getCode() : '', $parent->hasCode() ? $parent->getCode() : ''
				);
			}
		}

		if($type === 'ram' && $parentType === 'motherboard') {
			if(!self::compareFeature($item, $parent, 'ram-form-factor')) {
				$itemValue = $item->getFeature('ram-form-factor');
				$parentValue = $parent->getFeature('ram-form-factor');
				throw new ItemNestingException(
					"Incompatible form factor: RAM is $itemValue, motherboard is $parentValue",
					$item->hasCode() ? $item->getCode() : '', $parent->hasCode() ? $parent->getCode() : ''
				);
			}
			if(!self::compareFeature($item, $parent, 'ram-type')) {
				$itemValue = $item->getFeature('ram-type');
				$parentValue = $parent->getFeature('ram-type');
				throw new ItemNestingException(
					"Incompatible standard: RAM is $itemValue, motherboard is $parentValue",
					$item->hasCode() ? $item->getCode() : '', $parent->hasCode() ? $parent->getCode() : ''
				);
			}
		}

		foreach($item->getContents() as $subitem) {
			self::checkNesting($subitem, $item);
		}
	}

	/**
	 * Check that an item could be a root item (no parent)
	 *
	 * @param Item $item Item to be checked
	 *
	 * @throws ValidationException
	 */
	public static function checkRoot(Item $item) {
		$type = self::getOrNull($item, 'type');
		if($type !== null && $type !== 'location') {
			throw new ValidationException(
				'Set a location for this item or mark it as a location itself, this type cannot be a root item'
			);
		}
	}

	private static function isExpansionCard($type) {
		return strlen($type) > 5 && substr($type, -5) === '-card';
	}

	/**
	 * Check feature for "equality", or rather compatibility.
	 * If one is null (doesn't exist), they're considered compatible (failsafe), even though they're different
	 *
	 * @param Item $item
	 * @param Item $parent
	 * @param string $feature
	 *
	 * @return bool If the feature is the same or one of them is null
	 */
	private static function compareFeature(Item $item, Item $parent, string $feature) {
		$itemFeature = self::getOrNull($item, $feature);
		$parentFeature = self::getOrNull($parent, $feature);

		if($itemFeature !== null && $parentFeature !== null) {
			return $itemFeature === $parentFeature;
		}

		return true;
	}

	private static function has(Item $item, string $feature): bool {
		// There's a similar method in ItemPrefixer that also checks that the value is not 0.
		// This one is more strict.
		return $item->getFeature($feature) !== null;
	}

	private static function getOrNull(Item $item, string $featureName) {
		$feature = $item->getFeature($featureName);
		if($feature === null) {
			return null;
		} else {
			return $feature->value;
		}
	}

	/**
	 * Find motherboard inside an item (possibly a case).
	 * Search is only one level deep.
	 *
	 * @param Item $item
	 *
	 * @return Item|null Motherboard, or null if not found
	 */
	private static function findMobo(Item $item) {
		foreach($item->getContents() as $maybeMobo) {
			if(self::getOrNull($maybeMobo, 'type') === 'motherboard') {
				return $maybeMobo;
			}
		}
		return null;
	}
}
