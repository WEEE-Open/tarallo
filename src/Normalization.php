<?php

namespace WEEEOpen\Tarallo;

class Normalization
{
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
	public static function fixupLocation(ItemWithFeatures $item, ?ItemWithFeatures $parent): ?ItemWithFeatures
	{
		return self::reparentAll($item, $parent);
	}

	/**
	 * Correctly move RAMs and CPUs from cases to motherboards, if case contains a motherboard, and the like.
	 *
	 * @param ItemWithFeatures $item The item being placed
	 * @param ItemWithFeatures $container Its location (case or anything else)
	 *
	 * @return ItemWithFeatures Correct parent (given one or a motherboard)
	 */
	private static function reparent(ItemWithFeatures $item, ItemWithFeatures $container): ItemWithFeatures
	{
		assert($container instanceof ItemWithContent);
		$type = $item->getFeatureValue('type');
		$parentType = $container->getFeatureValue('type');

		if ($type === null || $parentType == null) {
			return $container;
		}

		$shouldBeInMobo = self::shouldBeInMotherboard($type);
		if (self::isCase($parentType) && $shouldBeInMobo) {
			$content = $container->getContent();
			$mobo = self::findByType($content, 'motherboard');
			if ($mobo !== null) {
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
	private static function reparentAll(ItemWithFeatures $item, ?ItemWithFeatures $container): ?ItemWithFeatures
	{
		assert($item instanceof ItemWithContent);
		if ($container !== null) {
			$container = self::reparent($item, $container);
		}

		$fixups = [];
		foreach ($item->getContent() as $subitem) {
			$newParent = self::reparentAll($subitem, $item);
			if ($newParent !== $item) {
				// Avoid changing arrays while foreachs are iterating over them
				$fixups[] = [$subitem, $item, $newParent];
			}
		}

		if (!empty($fixups)) {
			foreach ($fixups as $row) {
				/** @var Item[] $row */
				$row[1]->removeContent($row[0]);
				$row[2]->addContent($row[0]);
			}
		}

		return $container;
	}

	/**
	 * Check that item features make sense
	 *
	 * @param ItemWithFeatures $item Item to be checked
	 *
	 * @throws ValidationException if item contains invalid features
	 */
	public static function validateFeatures(ItemWithFeatures $item)
	{
		$type = $item->getFeatureValue('type');

		switch ($type) {
			case 'case':
			case 'smartphone-tablet':
				self::validateFeaturesCase($item);
				break;
			case 'monitor':
				self::validateFeaturesMonitor($item);
				break;
		}

		if ($item instanceof ItemWithContent) {
			foreach ($item->getContent() as $subitem) {
				self::validateFeatures($subitem);
			}
		}
	}

	/**
	 * Check that item nesting makes sense (e.g. no CPUs inside HDDs)
	 *
	 * @param ItemWithFeatures $item Item to be checked
	 * @param ItemWithFeatures|null $parent Its parent, or none if a root item
	 *
	 * @throws ItemNestingException if items are invalidly nested
	 * @throws ValidationException if item cannot be a root item and has a null parent
	 */
	public static function validateLocation(ItemWithFeatures $item, ?ItemWithFeatures $parent)
	{
		if ($parent === null) {
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
	private static function checkNesting(ItemWithFeatures $item, ?ItemWithFeatures $container): void
	{
		$type = $item->getFeatureValue('type');
		$containerType = $container->getFeatureValue('type');

		if ($type === null || $containerType == null) {
			return;
		}

		if (self::isCase($type) && $containerType !== 'location') {
			throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, 'Cases should be inside a location');
		} else {
			if (self::shouldBeInMotherboard($type)) {
				if (!self::isCase($containerType) && $containerType !== 'location' && $containerType !== 'motherboard') {
					throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, 'RAMs, CPUs and expansion cards cards should be inside a case, location or motherboard');
				}
			} else {
				if ($type === 'location' && $containerType !== 'location') {
					throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, 'Locations should be inside other locations or nothing');
				} else {
					if (!self::isCase($containerType) && $containerType !== 'location') {
						throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, 'Normal items can be placed only inside cases and locations');
					}
				}
			}
		}

		if ($type === 'cpu' && $containerType === 'motherboard') {
			if (!self::compareFeature($item, $container, 'cpu-socket')) {
				$itemValue = $item->getFeatureValue('cpu-socket');
				$parentValue = $container->getFeatureValue('cpu-socket');
				if ($itemValue !== null && $parentValue !== null) {
					if (
						!($itemValue === 'am2' && $parentValue === 'am2plus')
						&& !($itemValue === 'am3' && $parentValue === 'am3plus')
						//&& !($itemValue === 'am4' && $parentValue === 'am4plus')
						&& !($itemValue === 'fm2' && $parentValue === 'fm2plus')
					) {
						throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, "Incompatible socket: CPU is $itemValue, motherboard is $parentValue");
					}
				}
			}
		}

		if ($type === 'ram' && $containerType === 'motherboard') {
			if (!self::compareFeature($item, $container, 'ram-form-factor')) {
				$itemValue = $item->getFeatureValue('ram-form-factor');
				$parentValue = $container->getFeatureValue('ram-form-factor');
				throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, "Incompatible form factor: RAM is $itemValue, motherboard is $parentValue");
			}
			if (!self::compareFeature($item, $container, 'ram-type')) {
				$itemValue = $item->getFeatureValue('ram-type');
				$parentValue = $container->getFeatureValue('ram-type');
				throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, "Incompatible standard: RAM is $itemValue, motherboard is $parentValue");
			}
		}

		if (self::isCase($containerType) && $container->getFeatureValue('motherboard-form-factor') === 'proprietary-laptop') {
			if ($type === 'psu') {
				$ff = $item->getFeatureValue('psu-form-factor');
				if ($ff !== null && $ff !== 'proprietary') {
					throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, "Incompatible items: cannot place an internal PSU inside a laptop case");
				}
			} elseif ($type === 'hdd') {
				if ($item->getFeatureValue('hdd-form-factor') === '3.5') {
					throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, 'A 3.5" HDD is too large for a laptop');
				}
			} elseif ($type === 'odd') {
				if ($item->getFeatureValue('odd-form-factor') === '5.25') {
					throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, 'A 5.25" ODD is too large for a laptop');
				}
			} elseif (self::isExpansionCard($type)) {
				if ($item->getFeatureValue('pcie-sockets-n') !== null || $item->getFeatureValue('pci-sockets-n') !== null || $item->getFeatureValue('isa-sockets-n') !== null) {
					throw new ItemNestingException($item->peekCode(), $container->peekCode(), null, 'A full size PCI(e) card cannot fit into a laptop');
				}
			}
		}

		assert($item instanceof ItemWithContent);
		foreach ($item->getContent() as $subitem) {
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
	public static function checkRoot(ItemWithFeatures $item)
	{
		$type = $item->getFeatureValue('type');
		if ($type !== null && $type !== 'location') {
			$code = $item instanceof ItemWithCode ? $item->peekCode() : null;
			throw new ValidationException($code, null, 'Set a location for this item or mark it as a location itself, this type cannot be a root item');
		}
	}

	private static function shouldBeInMotherboard(string $type): bool
	{
		return $type === 'cpu' || $type === 'ram' || self::isExpansionCard($type);
	}

	private static function isCase(string $type): bool
	{
		return $type === 'case' || $type === 'smartphone-tablet';
	}

	private static function isExpansionCard(string $type): bool
	{
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
	private static function compareFeature(ItemWithFeatures $item, ItemWithFeatures $container, string $feature): bool
	{
		$itemFeature = $item->getFeatureValue($feature);
		$parentFeature = $container->getFeatureValue($feature);

		if ($itemFeature !== null && $parentFeature !== null) {
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
	 * @return ItemWithFeatures|null Item, or null if not found
	 */
	private static function findByType(array &$items, string $type, bool $remove = false): ?ItemWithFeatures
	{
		foreach ($items as $k => $maybe) {
			if ($maybe->getFeatureValue('type') === $type) {
				if ($remove) {
					unset($items[$k]);
				}
				return $maybe;
			}
		}
		return null;
	}

	/**
	 * Get default feature names (i.e. most common ones) for a product type
	 *
	 * @param string $type Item type
	 *
	 * @return string[]
	 */
	public static function getProductDefaultFeatures(string $type): array
	{
		switch ($type) {
			case 'case':
				return [
					'usb-ports-n', 'firewire-ports-n', 'mini-jack-ports-n', 'motherboard-form-factor',
					'psu-form-factor', 'power-connector', 'psu-volt', 'psu-ampere', 'color'
				];
			case 'smartphone-tablet':
				return [
					'usb-ports-n', 'usb-c-ports-n', 'mini-jack-ports-n', 'power-connector', 'psu-volt', 'psu-ampere',
					'diagonal-inch', 'color'
				];
			case 'motherboard':
				return [
					'motherboard-form-factor', 'color', 'key-bios-setup', 'key-boot-menu',
					'cpu-socket', 'ram-form-factor', 'ram-type', 'agp-sockets-n', 'pci-sockets-n', 'pcie-sockets-n',
					'sata-ports-n', 'ide-ports-n', 'jae-ports-n', 'game-ports-n', 'serial-ports-n', 'parallel-ports-n',
					'usb-ports-n', 'firewire-ports-n', 'mini-firewire-ports-n', 'ethernet-ports-1000m-n',
					'ethernet-ports-100m-n', 'rj11-ports-n', 'ps2-ports-n', 'integrated-graphics-brand',
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
					'integrated-graphics-brand', 'integrated-graphics-model',
					'capacity-byte', 'vga-ports-n', 'dvi-ports-n',
					'dms-59-ports-n', 's-video-ports-n', 's-video-7pin-ports-n', 'composite-video-ports-n',
					'agp-sockets-n', 'pcie-sockets-n', 'pcie-power-pin-n', 'pci-low-profile', 'color'
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
					'ethernet-ports-10base5-aui-n', 'pci-low-profile'
				];
			case 'audio-card':
				return [
					'mini-jack-ports-n', 'pcie-sockets-n', 'pci-sockets-n','pci-low-profile', 'color',
				];
			case 'modem-card':
				return [
					'rj11-ports-n', 'pcie-sockets-n', 'pci-sockets-n','pci-low-profile', 'color',
				];
			case 'other-card':
			case 'tv-card':
				return ['pcie-sockets-n', 'pci-sockets-n','pci-low-profile', 'color'];
			case 'storage-card':
				return ['sata-ports-n', 'ide-ports-n', 'scsi-sca2-ports-n',
					'scsi-db68-ports-n', 'pcie-sockets-n', 'sas-sff-8087-ports-n', 'sas-sff-8088-ports-n',
					'sas-sata-ports-n', 'pci-sockets-n', 'pci-low-profile', 'color'
				];
			case 'bluetooth-card':
			case 'wifi-card':
				return [
					'pcie-sockets-n', 'pci-sockets-n', 'mini-pcie-sockets-n',
					'mini-pci-sockets-n', 'pci-low-profile', 'color'
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
				return ['brand-manufacturer', 'ps2-ports-n', 'usb-ports-n', 'wireless-receiver', 'color'];
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
	public static function getItemDefaultFeatures(string $type): array
	{
		// Brand, Model, Variant, Type are already there by default for every item
		switch ($type) {
			case 'case':
			case 'smartphone-tablet':
				return [
					'cib-qr', 'cib', 'cib-old', 'other-code', 'os-license-version', 'os-license-code',
					'working', 'sn', 'arrival-batch', 'owner', 'notes',
				];
			case 'monitor':
				return [
					'cib-qr', 'cib', 'cib-old', 'other-code',
					'working', 'sn', 'arrival-batch', 'owner', 'notes',
				];
			case 'cpu':
				return [
					'working', 'owner'
				];
			case 'keyboard':
			case 'mouse':
				return [
					'wireless-receiver', 'working', 'sn', 'owner'
				];
			case 'graphics-card':
			case 'audio-card':
			case 'modem-card':
			case 'other-card':
			case 'tv-card':
			case 'storage-card':
				return [
					'pci-low-profile', 'working', 'sn', 'owner',
				];
			default: // Includes all these cases
//			case 'ram':
//			case 'odd':
//			case 'fdd':
//			case 'psu':
//			case 'external-psu':
//			case 'network-switch':
//			case 'network-hub':
				return [
					'working', 'sn', 'owner'
				];
			case 'motherboard':
			case 'ethernet-card':
			case 'bluetooth-card':
			case 'wifi-card':
			case 'modem-router':
				return [
					'working', 'mac', 'sn', 'owner', 'notes'
				];
			case 'hdd':
			case 'ssd':
				return [
					'working', 'sn', 'wwn',
					'data-erased', 'surface-scan', 'smart-data', 'software', 'owner',
				];
			case 'ports-bracket':
				// These rarely ever have a brand or a model or anything
				return ['usb-ports-n', 'serial-ports-n', 'game-ports-n', 'firewire-ports-n', 'color', 'owner'];
			case 'location':
				return ['notes'];
		}
	}

	public static function fileLastModified(): int
	{
		return filemtime(__FILE__);
	}

	private static function validateFeaturesCase(ItemWithFeatures $case)
	{
		$motherboardFF = $case->getFeatureValue('motherboard-form-factor');
		$code = null;
		if ($case instanceof ItemWithCode) {
			$code = $case->peekCode();
		}
		switch ($motherboardFF) {
			case 'proprietary-laptop':
				// It's a laptop, reject features that make sense only in desktops.
				if ($case->getFeatureValue('usb-ports-n') !== null) {
					throw new FeatureValidationException('usb-ports-n', $case->getFeatureValue('usb-ports-n'), null, $code, 'A laptop does not have USB ports on the case, but on the motherboard only. Remove the "USB ports" feature from the case or change the motherboard form factor.');
				}
				if ($case->getFeatureValue('psu-form-factor') !== null) {
					if ($case->getFeatureValue('psu-form-factor') !== 'proprietary') {
						// Well, it may contain a power supply, if it's something really old... but it won't be standard anyway.
						throw new FeatureValidationException('psu-form-factor', $case->getFeatureValue('psu-form-factor'), null, $code, 'A laptop does not have a standard internal PSU. Remove the "PSU form factor" feature from the case or change the motherboard form factor.');
					}
				}
				break;
			default:
				// It's a desktop, reject features that make sense only in laptops
				if ($case->getFeatureValue('power-connector') !== null) {
					throw new FeatureValidationException('power-connector', $case->getFeatureValue('power-connector'), null, $code, 'A desktop computer case does not have any power connector. Remove that feature or change the motherboard form factor.');
				}
				if ($case->getFeatureValue('psu-volt') !== null) {
					throw new FeatureValidationException('psu-volt', $case->getFeatureValue('psu-volt'), null, $code, 'A desktop computer case does not require a laptop PSU with a single voltage. Remove the "Power supply voltage" feature or change the motherboard form factor.');
				}
				if ($case->getFeatureValue('psu-ampere') !== null) {
					throw new FeatureValidationException('psu-ampere', $case->getFeatureValue('psu-ampere'), null, $code, 'A desktop computer case does not require a laptop PSU with a single voltage. Remove the "Power supply current" feature or change the motherboard form factor.');
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

	private static function validateFeaturesMonitor(ItemWithFeatures $monitor)
	{
		$power = $monitor->getFeatureValue('power-connector');
		if ($power === 'c13' || $power === 'c19') {
			if ($monitor->getFeatureValue('psu-volt') !== null) {
				$code = $monitor instanceof ItemWithCode ? $monitor->peekCode() : null;
				throw new FeatureValidationException('psu-volt', $monitor->getFeatureValue('psu-volt'), null, $code, 'A monitor with a 220 V power connector should not require specific voltages and currents. Remove "Power supply voltage" or select another power connector type.');
			} elseif ($monitor->getFeatureValue('psu-ampere') !== null) {
				$code = $monitor instanceof ItemWithCode ? $monitor->peekCode() : null;
				throw new FeatureValidationException('psu-ampere', $monitor->getFeatureValue('psu-ampere'), null, $code, 'A monitor with a 220 V power connector should not require specific voltages and currents. Remove "Power supply current" or select another power connector type.');
			}
		}
	}

	/**
	 * Add variants to all items (not recursively)
	 *
	 * @param Item[] $items all items
	 *
	 * @see Item::getFlatContent()
	 */
	public static function addAllVariants(array $items): void
	{
		foreach ($items as $item) {
			if ($item->getFeature('variant') === null) {
				$item->addFeature(new Feature('variant', ProductCode::DEFAULT_VARIANT));
			}
		}
	}
}
