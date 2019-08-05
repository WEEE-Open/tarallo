<?php

namespace WEEEOpen\Tarallo\Server;


class ItemValidator {
	/**
	 * Move item (or sub-items) to the correct place in their subtree, then in the items tree.
	 * Items are only pushed toward leafs in their subtree and the container changed, they cannot be pushed towards
	 * the root or the container.
	 *
	 * @param ItemWithFeatures $item The item or subtree being placed in a location
	 * @param ItemWithFeatures|null $parent The location
	 *
	 * @return ItemWithFeatures|null Correct location (or the given one if unchanged, or null if null was given)
	 */
	public static function fixupLocation(ItemWithFeatures $item, ?ItemWithFeatures $parent): ?ItemWithFeatures {
		$parent = self::reparentAll($item, $parent);

		return $parent;
	}

	/**
	 * Execute the fixup procedure on an item.
	 * Features will be changed inside this item and pushed down to sub-items if $subtree is true, which is the right
	 * thing to do when adding new items. If $subtree is false, features won't be pushed around: this is useful when
	 * editing features for a single item that may contain more items.
	 * Features are never pushed up.
	 *
	 * This is needed to avoid modifications to items that weren't open in the editor. Otherwise it may confuse the
	 * user, isn't visible to audit and seems a bad idea in general.
	 *
	 * @param ItemWithFeatures $item The item
	 * @param bool $pushdown Push features down to sub-items or not
	 * @param bool $pushup Push features up to ancestor items or not
	 */
	public static function fixupFeatures(ItemWithFeatures $item, bool $pushdown = true, bool $pushup = false) {
		if($pushdown) {
			$lowerLimit = null;
		} else {
			$lowerLimit = $item;
		}
		if($pushup) {
			$upperLimit = null;
		} else {
			$upperLimit = $item;
		}
		self::fixFeaturesRecursively($item, $lowerLimit, $upperLimit);
	}

	/**
	 * Correctly move RAMs and CPUs from cases to motherboards, if case contains a motherboard, and the like.
	 *
	 * @param ItemWithFeatures $item The item being placed
	 * @param ItemWithFeatures $container Its location (case or anything else)
	 *
	 * @return ItemWithFeatures Correct parent (given one or a motherboard)
	 */
	private static function reparent(ItemWithFeatures $item, ItemWithFeatures $container): ItemWithFeatures {
		$type = self::getOrNull($item, 'type');
		$parentType = self::getOrNull($container, 'type');

		if($type === null || $parentType == null) {
			return $container;
		}

		$shouldBeInMobo = self::shouldBeInMotherboard($type);
		if($parentType === 'case' && $shouldBeInMobo) {
			$content = $container->getContent();
			$mobo = self::findByType($content, 'motherboard');
			if($mobo !== null) {
				return $mobo;
			}
		}

		return $container;
	}

	/**
	 * Reparent all items, recursively.
	 *
	 * @param ItemWithFeatures $item The root item being places
	 * @param ItemWithFeatures|null $container Its location (case or anything else)
	 *
	 * @return ItemWithFeatures|null Correct parent for root item or null if was null
	 * @see reparent
	 */
	private static function reparentAll(ItemWithFeatures $item, ?ItemWithFeatures $container): ?ItemWithFeatures {
		if($container !== null) {
			$container = self::reparent($item, $container);
		}

		$fixups = [];
		foreach($item->getContent() as $subitem) {
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

		return $container;
	}

	/**
	 * Move all features that should be moved to the right item.
	 *
	 * @param ItemWithFeatures $item
	 * @param ItemWithFeatures|null $lowerLimit
	 * @param ItemWithFeatures|null $upperLimit
	 */
	private static function fixFeaturesRecursively(
		ItemWithFeatures $item,
		?ItemWithFeatures $lowerLimit,
		?ItemWithFeatures $upperLimit
	) {
		if($lowerLimit !== $item) {
			self::pushdownFeatures($item);
		}

//		if($upperLimit !== $item) {
//			self::pushupFeatures($item);
//		}

		foreach($item->getContent() as $subitem) {
			self::fixFeaturesRecursively($subitem, $lowerLimit, $upperLimit);
		}

//		self::fixFeatures($item);
	}

	/**
	 * @param ItemWithFeatures[]|ItemIncomplete[] $items
	 *
	 * @return ItemWithFeatures|ItemIncomplete
	 */
	public static function treeify(array $items) {
		$case = self::findByType($items, 'case', true);
		if($case === null) {
			throw new ValidationException('Cannot find case in items');
		}
		foreach($items as $item) {
			$case->addContent($item);
		}

		self::fixupLocation($case, null);
		self::fixupFeatures($case, true, true);
		return $case;
	}

	/**
	 * Check that item features make sense
	 *
	 * @param Item $item Item to be checked
	 *
	 * @throws ValidationException if item contains invalid features
	 * @TODO: make this thing work for PATCH requests...
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

		foreach($item->getContent() as $subitem) {
			self::validateFeatures($subitem);
		}
	}

	/**
	 * Check that item nesting makes sense (e.g. no CPUs inside HDDs)
	 *
	 * @param ItemWithFeatures $item Item to be checked
	 * @param ItemWithFeatures $parent Its parent, or none if a root item
	 *
	 * @throws ItemNestingException if items are invalidly nested
	 * @throws ValidationException if item cannot be a root item and has a null parent
	 */
	public static function validateLocation(ItemWithFeatures $item, ?ItemWithFeatures $parent) {
		if($parent === null) {
			self::checkRoot($item);
		} else {
			self::checkNesting($item, $parent);
		}
	}

	/**
	 * Check that item nesting makes sense (e.g. no CPUs inside HDDs)
	 *
	 * @param ItemWithFeatures $item Item to be checked
	 * @param ItemWithFeatures|null $container Its container
	 *
	 */
	private static function checkNesting(ItemWithFeatures $item, ?ItemWithFeatures $container): void {
		$type = self::getOrNull($item, 'type');
		$parentType = self::getOrNull($container, 'type');

		if($type === null || $parentType == null) {
			return;
		}

		if($type === 'case' && $parentType !== 'location') {
			throw new ItemNestingException(
				'Cases should be inside a location',
				$item->hasCode() ? $item->getCode() : '', $container->hasCode() ? $container->getCode() : ''
			);
		} else if(self::shouldBeInMotherboard($type)) {
			if($parentType !== 'case' && $parentType !== 'location' && $parentType !== 'motherboard') {
				throw new ItemNestingException(
					'RAMs, CPUs and expansion cards cards should be inside a case, location or motherboard',
					$item->hasCode() ? $item->getCode() : '', $container->hasCode() ? $container->getCode() : ''
				);
			}
		} else {
			if($parentType !== 'case' && $parentType !== 'location') {
				throw new ItemNestingException(
					'Normal items can be placed only inside cases and locations',
					$item->hasCode() ? $item->getCode() : '', $container->hasCode() ? $container->getCode() : ''
				);
			}
		}

		if($type === 'cpu' && $parentType === 'motherboard') {
			if(!self::compareFeature($item, $container, 'cpu-socket')) {
				$itemValue = $item->getFeature('cpu-socket');
				$parentValue = $container->getFeature('cpu-socket');
				throw new ItemNestingException(
					"Incompatible socket: CPU is $itemValue, motherboard is $parentValue",
					$item->hasCode() ? $item->getCode() : '', $container->hasCode() ? $container->getCode() : ''
				);
			}
		}

		if($type === 'ram' && $parentType === 'motherboard') {
			if(!self::compareFeature($item, $container, 'ram-form-factor')) {
				$itemValue = $item->getFeature('ram-form-factor');
				$parentValue = $container->getFeature('ram-form-factor');
				throw new ItemNestingException(
					"Incompatible form factor: RAM is $itemValue, motherboard is $parentValue",
					$item->hasCode() ? $item->getCode() : '', $container->hasCode() ? $container->getCode() : ''
				);
			}
			if(!self::compareFeature($item, $container, 'ram-type')) {
				$itemValue = $item->getFeature('ram-type');
				$parentValue = $container->getFeature('ram-type');
				throw new ItemNestingException(
					"Incompatible standard: RAM is $itemValue, motherboard is $parentValue",
					$item->hasCode() ? $item->getCode() : '', $container->hasCode() ? $container->getCode() : ''
				);
			}
		}

		foreach($item->getContent() as $subitem) {
			self::checkNesting($subitem, $item);
		}
	}

	/**
	 * Check that an item could be a root item (no parent)
	 *
	 * @param ItemWithFeatures $item Item to be checked
	 *
	 * @throws ValidationException
	 */
	public static function checkRoot(ItemWithFeatures $item) {
		$type = self::getOrNull($item, 'type');
		if($type !== null && $type !== 'location') {
			throw new ValidationException(
				'Set a location for this item or mark it as a location itself, this type cannot be a root item'
			);
		}
	}

	private static function shouldBeInMotherboard($type): bool {
		return $type === 'cpu' || $type === 'ram' || self::isExpansionCard($type);
	}

	private static function isExpansionCard($type) {
		return strlen($type) > 5 && substr($type, -5) === '-card';
	}

	/**
	 * Check feature for "equality", or rather compatibility.
	 * If one is null (doesn't exist), they're considered compatible (failsafe), even though they're different
	 *
	 * @param ItemWithFeatures $item
	 * @param ItemWithFeatures $container
	 * @param string $feature
	 *
	 * @return bool If the feature is the same or one of them is null
	 */
	private static function compareFeature(ItemWithFeatures $item, ItemWithFeatures $container, string $feature) {
		$itemFeature = self::getOrNull($item, $feature);
		$parentFeature = self::getOrNull($container, $feature);

		if($itemFeature !== null && $parentFeature !== null) {
			return $itemFeature === $parentFeature;
		}

		return true;
	}

	private static function has(ItemWithFeatures $item, string $feature): bool {
		// There's a similar method in ItemPrefixer that also checks that the value is not 0.
		// This one is more strict.
		return $item->getFeature($feature) !== null;
	}

	private static function getOrNull(ItemWithFeatures $item, string $featureName) {
		$feature = $item->getFeature($featureName);
		if($feature === null) {
			return null;
		} else {
			if($feature instanceof Feature) {
				return $feature->value;
			} else {
				return null;
			}
		}
	}

	/**
	 * Find item by type inside another one, e.g. motherboard inside a case.
	 * Search is only one level deep.
	 *
	 * @param ItemWithFeatures[] $items
	 * @param string $type
	 * @param bool $remove Remove item from array
	 *
	 * @return Item|null Item, or null if not found
	 */
	private static function findByType(array &$items, string $type, bool $remove = false) {
		foreach($items as $k => $maybe) {
			if(self::getOrNull($maybe, 'type') === $type) {
				if($remove) {
					unset($items[$k]);
				}
				return $maybe;
			}
		}
		return null;
	}

	/**
	 * Find items by type inside another one, e.g. RAMs inside a motherboard.
	 * Search is only one level deep.
	 *
	 * @param ItemWithFeatures[] $items
	 * @param string $type
	 *
	 * @return Item[] Item, or empty array if not found
	 */
	private static function findAllByType(array &$items, string $type): array {
		$found = [];
		foreach($items as $maybe) {
			if(self::getOrNull($maybe, 'type') === $type) {
				$found[] = $maybe;
			}
		}
		return $found;
	}

	/**
	 * Get default feature names (i.e. most common ones) for an item type
	 *
	 * @param string $type Item type
	 *
	 * @return string[]
	 */
	public static function getDefaultFeatures(string $type): array {
		switch($type) {
			case 'case':
				return [
					'cib-qr', 'cib', 'cib-old', 'other-code', 'os-license-version', 'os-license-code', 'brand', 'model',
					'working', 'sn', 'usb-ports-n', 'firewire-ports-n', 'mini-jack-ports-n', 'motherboard-form-factor',
					'psu-form-factor', 'power-connector', 'psu-volt', 'psu-ampere', 'arrival-batch', 'owner',
					'color', 'notes',
				];
			case 'motherboard':
				return [
					'brand', 'model', 'working', 'sn', 'motherboard-form-factor', 'key-bios-setup', 'key-boot-menu',
					'cpu-socket', 'ram-form-factor', 'ram-type', 'agp-sockets-n', 'pci-sockets-n', 'pcie-sockets-n',
					'sata-ports-n', 'ide-ports-n', 'jae-ports-n', 'game-ports-n', 'serial-ports-n', 'parallel-ports-n',
					'usb-ports-n', 'firewire-ports-n', 'mini-firewire-ports-n', 'ethernet-ports-1000m-n',
					'ethernet-ports-100m-n', 'mac', 'rj11-ports-n', 'ps2-ports-n', 'integrated-graphics-brand',
					'integrated-graphics-model', 'vga-ports-n', 'dvi-ports-n', 's-video-ports-n',
					's-video-7pin-ports-n', 'mini-jack-ports-n', 'psu-connector-cpu', 'psu-connector-motherboard',
					'color', 'owner', 'notes',
				];
			case 'cpu':
				return [
					'brand', 'model', 'working', 'variant', 'core-n', 'thread-n', 'isa', 'frequency-hertz',
					'cpu-socket',
					'integrated-graphics-brand', 'integrated-graphics-model', 'owner',
				];
			case 'ram':
				return [
					'brand', 'model', 'working', 'sn', 'family', 'ram-type', 'ram-form-factor', 'frequency-hertz',
					'capacity-byte', 'ram-timings', 'ram-ecc', 'color', 'owner', 'notes',
				];
			case 'hdd':
				return [
					'brand', 'model', 'working', 'sn', 'wwn', 'family', 'capacity-decibyte', 'hdd-form-factor',
					'spin-rate-rpm', 'mini-ide-ports-n', 'sata-ports-n', 'ide-ports-n', 'scsi-sca2-ports-n',
					'scsi-db68-ports-n', 'data-erased', 'surface-scan', 'smart-data', 'software', 'owner',
				];
			case 'ssd':
				return [
					'brand', 'model', 'working', 'sn', 'family', 'capacity-byte', 'hdd-form-factor', 'sata-ports-n',
					'data-erased', 'surface-scan', 'smart-data', 'software', 'owner',
				];
			case 'odd':
				return [
					'brand', 'model', 'working', 'sn', 'odd-type', 'ide-ports-n', 'jae-ports-n',
					'sata-ports-n', 'odd-form-factor', 'color', 'owner',
				];
			case 'fdd':
				return ['brand', 'model', 'working', 'sn', 'color', 'owner'];
			case 'graphics-card':
				return [
					'brand', 'brand-manufacturer', 'model', 'working', 'capacity-byte', 'vga-ports-n', 'dvi-ports-n',
					'dms-59-ports-n', 's-video-ports-n', 's-video-7pin-ports-n', 'agp-sockets-n', 'pcie-sockets-n',
					'pcie-power-pin-n', 'sn', 'color', 'owner',
				];
			case 'psu':
				return [
					'brand', 'brand-manufacturer', 'model', 'working', 'working', 'sn', 'power-connector',
					'power-rated-watt', 'psu-connector-cpu', 'psu-connector-motherboard', 'psu-form-factor',
					'psu-12v-rail-ampere', 'pcie-power-pin-n', 'sata-power-n', 'color', 'owner',
				];
			case 'external-psu':
				return [
					'brand', 'brand-manufacturer', 'model', 'working', 'sn', 'power-connector', 'psu-volt',
					'psu-ampere', 'owner', 'notes',
				];
			case 'ethernet-card':
				return [
					'brand', 'model', 'working', 'sn', 'pcie-sockets-n', 'pci-sockets-n', 'ethernet-ports-1000m-n',
					'ethernet-ports-100m-n', 'ethernet-ports-10m-n', 'ethernet-ports-10base2-bnc-n',
					'ethernet-ports-10base5-aui-n', 'mac', 'color', 'owner',
				];
			case 'audio-card':
			case 'other-card':
			case 'modem-card':
			case 'tv-card':
				return ['brand', 'model', 'working', 'pcie-sockets-n', 'pci-sockets-n', 'sn', 'color', 'owner'];
			case 'storage-card':
				return ['brand', 'model', 'working', 'sata-ports-n', 'ide-ports-n', 'scsi-sca2-ports-n',
					'scsi-db68-ports-n', 'pcie-sockets-n', 'sas-sff-8087-ports-n', 'sas-sff-8088-ports-n',
					'sas-sata-ports-n', 'pci-sockets-n', 'sn', 'color', 'owner'
				];
			case 'bluetooth-card':
			case 'wifi-card':
				return [
					'brand', 'model', 'working', 'pcie-sockets-n', 'pci-sockets-n', 'mini-pcie-sockets-n',
					'mini-pci-sockets-n', 'mac', 'sn', 'color', 'owner',
				];
			case 'network-switch':
			case 'network-hub':
			case 'modem-router':
				return [
					'brand', 'model', 'working', 'ethernet-ports-1000m-n', 'ethernet-ports-100m-n',
					'ethernet-ports-10m-n', 'ethernet-ports-10base2-bnc-n', 'ethernet-ports-10base5-aui-n',
					'power-connector', 'psu-volt', 'psu-ampere', 'color', 'owner', 'notes',
				];
			case 'keyboard':
			case 'mouse':
				return ['brand', 'brand-manufacturer', 'model', 'working', 'sn', 'ps2-ports-n', 'usb-ports-n', 'color',
					'owner'];
			case 'monitor':
				return [
					'cib-qr', 'cib', 'cib-old', 'other-code', 'brand', 'model', 'working', 'sn', 'diagonal-inch',
					'vga-ports-n', 'dvi-ports-n', 'hdmi-ports-n', 's-video-ports-n', 'usb-ports-n', 'power-connector',
					'psu-volt', 'psu-ampere', 'color', 'owner', 'notes',
				];
			case 'ports-bracket':
				return ['usb-ports-n', 'serial-ports-n', 'game-ports-n', 'firewire-ports-n', 'color', 'owner'];
			case 'location':
				return ['notes'];
			default:
				return ['brand', 'model', 'owner', 'notes'];
		}
	}

	public static function defaultFeaturesLastModified(): int {
		return filemtime(__FILE__);
	}


	private static function pushdownFeatures(ItemWithFeatures $item) {
		$type = self::getOrNull($item, 'type');

		// Move laptop usb ports from the case to the motherboard
		if($type === 'case') {
			$ff = self::getOrNull($item, 'motherboard-form-factor');
			if($ff === 'proprietary-laptop') {
				if(self::has($item, 'usb-ports-n')) {
					$content = $item->getContent();
					$mobo = self::findByType($content, 'motherboard');
					if($mobo !== null && !self::has($mobo, 'usb-ports-n')) {
						// TODO: this will end badly when products are implemented...
						$mobo->addFeature($item->getFeature('usb-ports-n'));
						$item->removeFeatureByName('usb-ports-n');
					}
				}
			}
		}
	}

//	private static function pushupFeatures(ItemWithFeatures $item) {
//	}
//
//	private static function fixFeatures(ItemWithFeatures $item) {
//	}

	public static function fillWithDefaults(ItemIncomplete $item): ItemIncomplete {
		$type = self::getOrNull($item, 'type');
		if($type === null) {
			return $item;
		}
		foreach(self::getDefaultFeatures($type) as $feature) {
			$item->addFeature(new BaseFeature($feature));
		}
		return $item;
	}

	public static function fixupFromPeracotta(ItemIncomplete $item) {
		self::fixupFromPeracottaRecursive($item);
	}

	private static function fixupFromPeracottaRecursive(ItemIncomplete $item) {
		self::doFixupFromPeracotta($item);

		foreach($item->getContent() as $subitem) {
			self::fixupFromPeracottaRecursive($subitem);
		}

		self::doFixupFromPeracotta($item);
	}

	private static function doFixupFromPeracotta(ItemIncomplete $item) {
		$type = self::getOrNull($item, 'type');

		switch($type) {
//			case 'motherboard':
//				// TODO: do it on the client side?
//				$ramType = self::getOrNull($item, 'ram-type');
//				$ramSize = self::getOrNull($item, 'ram-form-factor');
//				if($ramType === null || $ramSize === null) {
//					$inside = $item->getContent();
//					$rams = self::findAllByType($inside, 'ram');
//					if(count($rams) > 0) {
//
//					}
//				}
//				break;
			case 'case':
				$ff = self::getOrNull($item, 'motherboard-form-factor');
				if($ff === 'proprietary-laptop') {
					$item->removeFeatureByName('psu-form-factor');
				} else {
					$item->removeFeatureByName('psu-volt');
					$item->removeFeatureByName('psu-ampere');
					$item->removeFeatureByName('power-connector');
				}
				break;
		}
		return $type;
	}

}
