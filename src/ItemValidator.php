<?php

namespace WEEEOpen\Tarallo;


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
		$type = $item->getFeatureValue('type');
		$parentType = $container->getFeatureValue('type');

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
	 * @param ItemWithFeatures $item The root item being placed
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
			throw new ValidationException(null, null, 'Cannot find case in items');
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
	 * @param ItemWithFeatures $item Item to be checked
	 *
	 * @throws ValidationException if item contains invalid features
	 * @TODO: make this thing work for PATCH requests...
	 */
	public static function validateFeatures(ItemWithFeatures $item) {
		$type = $item->getFeatureValue('type');

		switch($type) {
			case 'case':
				self::validateFeaturesCase($item);
				break;
			case 'monitor':
				self::validateFeaturesMonitor($item);
				break;
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
	 * @param ItemWithFeatures|ItemWithCode $item Item to be checked
	 * @param ItemWithFeatures|ItemWithCode|null $container Its container
	 *
	 */
	private static function checkNesting(ItemWithFeatures $item, ?ItemWithFeatures $container): void {
		$type = $item->getFeatureValue('type');
		$containerType = $container->getFeatureValue('type');

		if($type === null || $containerType == null) {
			return;
		}

		if($type === 'case' && $containerType !== 'location') {
			throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, 'Cases should be inside a location');
		} else {
			if(self::shouldBeInMotherboard($type)) {
				if($containerType !== 'case' && $containerType !== 'location' && $containerType !== 'motherboard') {
					throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, 'RAMs, CPUs and expansion cards cards should be inside a case, location or motherboard');
				}
			} else {
				if($type === 'location' && $containerType !== 'location') {
					throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, 'Locations should be inside other locations or nothing');
				} else {
					if($containerType !== 'case' && $containerType !== 'location') {
						throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, 'Normal items can be placed only inside cases and locations');
					}
				}
			}
		}

		if($type === 'cpu' && $containerType === 'motherboard') {
			if(!self::compareFeature($item, $container, 'cpu-socket')) {
				$itemValue = $item->getFeatureValue('cpu-socket');
				$parentValue = $container->getFeatureValue('cpu-socket');
				throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, "Incompatible socket: CPU is $itemValue, motherboard is $parentValue");
			}
		}

		if($type === 'ram' && $containerType === 'motherboard') {
			if(!self::compareFeature($item, $container, 'ram-form-factor')) {
				$itemValue = $item->getFeatureValue('ram-form-factor');
				$parentValue = $container->getFeatureValue('ram-form-factor');
				throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, "Incompatible form factor: RAM is $itemValue, motherboard is $parentValue");
			}
			if(!self::compareFeature($item, $container, 'ram-type')) {
				$itemValue = $item->getFeatureValue('ram-type');
				$parentValue = $container->getFeatureValue('ram-type');
				throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, "Incompatible standard: RAM is $itemValue, motherboard is $parentValue");
			}
		}

		if($containerType === 'case' && $container->getFeatureValue('motherboard-form-factor') === 'proprietary-laptop') {
			if($type === 'psu') {
				$ff = $item->getFeatureValue('psu-form-factor');
				if($ff !== null && $ff !== 'proprietary') {
					throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, "Incompatible items: cannot place an internal PSU inside a laptop case");
				}
			} else if($type === 'hdd') {
				if($item->getFeatureValue('hdd-form-factor') === '3.5') {
					throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, 'A 3.5" HDD is too large for a laptop');
				}
			} else if($type === 'odd') {
				if($item->getFeatureValue('odd-form-factor') === '5.25') {
					throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, 'A 5.25" ODD is too large for a laptop');
				}
			} else if(self::isExpansionCard($type)) {
				if($item->getFeatureValue('pcie-sockets-n') !== null || $item->getFeatureValue('pci-sockets-n') !== null || $item->getFeatureValue('isa-sockets-n') !== null) {
					throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, 'A full size PCI(e) card cannot fit into a laptop');
				}
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
		$type = $item->getFeatureValue('type');
		if($type !== null && $type !== 'location') {
			throw new ValidationException($item->peekCode(), null, 'Set a location for this item or mark it as a location itself, this type cannot be a root item');
		}
	}

	private static function shouldBeInMotherboard(string $type): bool {
		return $type === 'cpu' || $type === 'ram' || self::isExpansionCard($type);
	}

	private static function isExpansionCard(string $type) {
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
		$itemFeature = $item->getFeatureValue($feature);
		$parentFeature = $container->getFeatureValue($feature);

		if($itemFeature !== null && $parentFeature !== null) {
			return $itemFeature === $parentFeature;
		}

		return true;
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
			if($maybe->getFeatureValue('type') === $type) {
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
			if($maybe->getFeatureValue('type') === $type) {
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
	public static function getProductDefaultFeatures(string $type): array {
		switch($type) {
			case 'case':
				return [
					'usb-ports-n', 'firewire-ports-n', 'mini-jack-ports-n', 'motherboard-form-factor',
					'psu-form-factor', 'power-connector', 'psu-volt', 'psu-ampere', 'color'
				];
			case 'motherboard':
				return [
					'motherboard-form-factor', 'color', 'key-bios-setup', 'key-boot-menu',
					'cpu-socket', 'ram-form-factor', 'ram-type', 'agp-sockets-n', 'pci-sockets-n', 'pcie-sockets-n',
					'sata-ports-n', 'ide-ports-n', 'jae-ports-n', 'game-ports-n', 'serial-ports-n', 'parallel-ports-n',
					'usb-ports-n', 'firewire-ports-n', 'mini-firewire-ports-n', 'ethernet-ports-1000m-n',
					'ethernet-ports-100m-n', 'mac', 'rj11-ports-n', 'ps2-ports-n', 'integrated-graphics-brand',
					'integrated-graphics-model', 'vga-ports-n', 'dvi-ports-n', 's-video-ports-n',
					's-video-7pin-ports-n', 'mini-jack-ports-n', 'psu-connector-cpu', 'psu-connector-motherboard',
				];
			case 'cpu':
				return [
					'core-n', 'thread-n', 'isa', 'frequency-hertz',	'cpu-socket',
					'integrated-graphics-brand', 'integrated-graphics-model'
				];
			case 'ram':
				return [
					'family', 'ram-type', 'ram-form-factor', 'frequency-hertz',
					'capacity-byte', 'ram-timings', 'ram-ecc', 'color'
				];
			case 'hdd':
				return [
					'family', 'capacity-decibyte', 'hdd-form-factor', 'spin-rate-rpm', 'mini-ide-ports-n',
					'sata-ports-n', 'ide-ports-n', 'scsi-sca2-ports-n', 'scsi-db68-ports-n'
				];
			case 'ssd':
				return [
					'capacity-byte', 'hdd-form-factor', 'sata-ports-n',
				];
			case 'odd':
				return [
					'odd-type', 'ide-ports-n', 'jae-ports-n', 'sata-ports-n', 'odd-form-factor', 'color'
				];
			case 'fdd':
				return ['color'];
			case 'graphics-card':
				return [
					'brand-manufacturer', 'capacity-byte', 'vga-ports-n', 'dvi-ports-n',
					'dms-59-ports-n', 's-video-ports-n', 's-video-7pin-ports-n', 'agp-sockets-n', 'pcie-sockets-n',
					'pcie-power-pin-n','color'
				];
			case 'psu':
				return [
					'brand-manufacturer', 'power-connector',
					'power-rated-watt', 'psu-connector-cpu', 'psu-connector-motherboard', 'psu-form-factor',
					'psu-12v-rail-ampere', 'pcie-power-pin-n', 'sata-power-n', 'color'
				];
			case 'external-psu':
				return [
					'brand-manufacturer', 'power-connector', 'psu-volt', 'psu-ampere'
				];
			case 'ethernet-card':
				return [
					'pcie-sockets-n', 'pci-sockets-n', 'ethernet-ports-1000m-n',
					'ethernet-ports-100m-n', 'ethernet-ports-10m-n', 'ethernet-ports-10base2-bnc-n',
					'ethernet-ports-10base5-aui-n'
				];
			case 'audio-card':
				return [
					'mini-jack-ports-n', 'pcie-sockets-n', 'pci-sockets-n', 'color',
				];
				break;
			case 'modem-card':
				return [
					'rj11-ports-n', 'pcie-sockets-n', 'pci-sockets-n', 'color',
				];
				break;
			case 'other-card':
			case 'tv-card':
				return ['pcie-sockets-n', 'pci-sockets-n', 'color'];
			case 'storage-card':
				return ['sata-ports-n', 'ide-ports-n', 'scsi-sca2-ports-n',
					'scsi-db68-ports-n', 'pcie-sockets-n', 'sas-sff-8087-ports-n', 'sas-sff-8088-ports-n',
					'sas-sata-ports-n', 'pci-sockets-n', 'color'
				];
			case 'bluetooth-card':
			case 'wifi-card':
				return [
					'pcie-sockets-n', 'pci-sockets-n', 'mini-pcie-sockets-n',
					'mini-pci-sockets-n', 'color'
				];
			case 'network-switch':
			case 'network-hub':
			case 'modem-router':
				return [
					'ethernet-ports-1000m-n', 'ethernet-ports-100m-n',
					'ethernet-ports-10m-n', 'ethernet-ports-10base2-bnc-n', 'ethernet-ports-10base5-aui-n',
					'power-connector', 'psu-volt', 'psu-ampere',
				];
			case 'keyboard':
			case 'mouse':
				return ['brand-manufacturer', 'ps2-ports-n', 'usb-ports-n', 'color'];
			case 'monitor':
				return [
					'diagonal-inch', 'vga-ports-n', 'dvi-ports-n', 'hdmi-ports-n', 's-video-ports-n',
					'usb-ports-n', 'power-connector', 'psu-volt', 'psu-ampere', 'color'
				];
			case 'ports-bracket':
				return ['usb-ports-n', 'serial-ports-n', 'game-ports-n', 'firewire-ports-n', 'color'];
			default:
				return [];
		}
	}


	/**
	 * Get default feature names (i.e. most common ones) for an item type
	 *
	 * @param string $type Item type
	 *
	 * @return string[]
	 */
	public static function getItemDefaultFeatures(string $type): array {
		switch($type) {
			case 'case':
			case 'monitor':
				return [
					'cib-qr', 'cib', 'cib-old', 'other-code', 'os-license-version', 'os-license-code',
					'brand', 'model', 'variant', 'working', 'sn', 'arrival-batch', 'owner', 'notes',
				];
			default: // Includes all these cases
//			case 'cpu':
//			case 'ram':
//			case 'motherboard':
//			case 'odd':
//			case 'fdd':
//			case 'graphics-card':
//			case 'psu':
//			case 'external-psu':
//			case 'audio-card':
//			case 'modem-card':
//			case 'other-card':
//			case 'tv-card':
//			case 'storage-card':
//			case 'network-switch':
//			case 'network-hub':
//			case 'keyboard':
//			case 'mouse':
				return [
					'brand', 'model', 'variant', 'working', 'sn', 'owner'
				];
			case 'ethernet-card':
			case 'bluetooth-card':
			case 'wifi-card':
			case 'modem-router':
				return [
					'brand', 'model', 'variant', 'working', 'mac', 'sn', 'owner', 'notes'
				];
			case 'hdd':
			case 'ssd':
				return [
					'brand', 'model', 'variant', 'working', 'sn', 'wwn',
					'data-erased', 'surface-scan', 'smart-data', 'software', 'owner',
				];
			case 'ports-bracket':
				// These rarely ever have a brand or a model or anything
				return ['usb-ports-n', 'serial-ports-n', 'game-ports-n', 'firewire-ports-n', 'color', 'owner'];
			case 'location':
				return ['notes'];
		}
	}

	public static function defaultFeaturesLastModified(): int {
		return filemtime(__FILE__);
	}

	private static function pushdownFeatures(ItemWithFeatures $item) {
		$type = $item->getFeatureValue('type');

		// Move laptop usb ports from the case to the motherboard
		if($type === 'case') {
			$ff = $item->getFeatureValue('motherboard-form-factor');
			if($ff === 'proprietary-laptop') {
				if($item->getFeatureValue('usb-ports-n') !== null) {
					$content = $item->getContent();
					$mobo = self::findByType($content, 'motherboard');
					if($mobo !== null && !$mobo->getFeatureValue('usb-ports-n') !== null) {
						// TODO: this will end badly when products are implemented...
						$mobo->addFeature(new Feature('usb-ports-n', $item->getFeatureValue('usb-ports-n')));
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
		$type = $item->getFeatureValue('type');
		if($type === null) {
			return $item;
		}
		foreach(self::getItemDefaultFeatures($type) as $feature) {
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
		$type = $item->getFeatureValue('type');

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
				$ff = $item->getFeatureValue('motherboard-form-factor');
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

	private static function validateFeaturesCase(ItemWithFeatures $case) {
		$motherboardFF = $case->getFeatureValue('motherboard-form-factor');
		switch($motherboardFF) {
			case 'proprietary-laptop':
				// It's a laptop, reject features that make sense only in desktops.
				if($case->getFeatureValue('usb-ports-n') !== null) {
					throw new FeatureValidationException('usb-ports-n', $case->getFeatureValue('usb-ports-n'), null, $case->peekCode(), 'A laptop does not have USB ports on the case, but on the motherboard only. Remove the "USB ports" feature from the case or change the motherboard form factor.');
				}
				if($case->getFeatureValue('psu-form-factor') !== null) {
					if($case->getFeatureValue('psu-form-factor') !== 'proprietary') {
						// Well, it may contain a power supply, if it's something really old... but it won't be standard anyway.
						throw new FeatureValidationException('psu-form-factor', $case->getFeatureValue('psu-form-factor'), null, $case->peekCode(), 'A laptop does not have a standard internal PSU. Remove the "PSU form factor" feature from the case or change the motherboard form factor.');
					}
				}
				break;
			default:
				// It's a desktop, reject features that make sense only in laptops
				if($case->getFeatureValue('power-connector') !== null) {
					throw new FeatureValidationException('power-connector', $case->getFeatureValue('power-connector'), null, $case->peekCode(), 'A desktop computer case does not have any power connector. Remove that feature or change the motherboard form factor.');
				}
				if($case->getFeatureValue('psu-volt') !== null) {
					throw new FeatureValidationException('psu-volt', $case->getFeatureValue('psu-volt'), null, $case->peekCode(), 'A desktop computer case does not require a laptop PSU with a single voltage. Remove the "Power supply voltage" feature or change the motherboard form factor.');
				}
				if($case->getFeatureValue('psu-ampere') !== null) {
					throw new FeatureValidationException('psu-ampere', $case->getFeatureValue('psu-ampere'), null, $case->peekCode(), 'A desktop computer case does not require a laptop PSU with a single voltage. Remove the "Power supply current" feature or change the motherboard form factor.');
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

	private static function validateFeaturesMonitor(ItemWithFeatures $monitor) {
		$power = $monitor->getFeatureValue('power-connector');
		if($power === 'c13' || $power === 'c19') {
			if($monitor->getFeatureValue('psu-volt') !== null) {
				throw new FeatureValidationException('psu-volt', $monitor->getFeatureValue('psu-volt'), null, $monitor->peekCode(), 'A monitor with a 220 V power connector should not require specific voltages and currents. Remove "Power supply voltage" or select another power connector type.');
			} else if($monitor->getFeatureValue('psu-ampere') !== null) {
				throw new FeatureValidationException('psu-ampere', $monitor->getFeatureValue('psu-ampere'), null, $monitor->peekCode(), 'A monitor with a 220 V power connector should not require specific voltages and currents. Remove "Power supply current" or select another power connector type.');
			}
		}
	}
}
