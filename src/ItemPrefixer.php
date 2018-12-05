<?php

namespace WEEEOpen\Tarallo\Server;

class ItemPrefixer {
	/**
	 * Generate a prefix for an item.
	 *
	 * Long term project: move this data to the database or a configuration file,
	 * to allow other people to use their own prefixes.
	 *
	 * @param Item $item
	 *
	 * @return string
	 * @throws \InvalidArgumentException if required features for type generation are not present
	 */
	public static function get(Item $item) {
		$features = $item->getCombinedFeatures();
		if(!isset($features['type'])) {
			throw new \InvalidArgumentException('Item has no type, cannot generate code');
		}
		switch($features['type']->value) {
			case 'mouse':
				return 'M';
			case 'keyboard':
				return 'T';
			case 'motherboard':
				return 'B';
			case 'cpu':
				return 'C';
			case 'graphics-card':
				return 'SG';
			case 'ram':
				return 'R';
			case 'hdd':
				if(self::has('sata-ports-n', $features)) {
					return 'S';
				} elseif(self::has('ide-ports-n', $features) || self::has('mini-ide-ports-n', $features)) {
					return 'H';
				} elseif(self::has('scsi-sca2-ports-n', $features) || self::has('scsi-db68-ports-n', $features)) {
					return 'SC';
				} else {
					throw new \InvalidArgumentException('No or unknown hard drive connector, cannot generate a code');
				}
				//return 'HDD';
			case 'odd':
				return 'ODD';
			case 'psu':
			case 'external-psu':
				if(self::is('power-connector', 'da-2', $features)) {
					return 'AD';
				}
				return 'A';
			case 'monitor':
				return 'V';
			case 'fdd':
			case 'zip-drive':
				return 'FL';
			case 'ports-bracket':
			case 'other-card':
			case 'scsi-card':
			case 'tv-card':
				return 'SP';
			case 'audio-card':
				return 'SA';
			case 'wifi-card':
			case 'bluetooth-card':
				return 'W';
			case 'modem-card':
			case 'ethernet-card':
			case 'network-switch':
			case 'network-hub':
			case 'modem-router':
				return 'NET';
			case 'case':
				return '';
			default:
				throw new \InvalidArgumentException('No prefix found for type ' . $features['type']);
		}
	}

	private static function has(string $name, array $features): bool {
		$type = Feature::getType($name);
		if($type === Feature::INTEGER || $type === Feature::DOUBLE) {
			return isset($features[$name]) && $features[$name]->value > 0;
		} else {
			return isset($features[$name]);
		}
	}

	private static function is($name, $value, $features) {
		return isset($features[$name]) && $features[$name]->value === $value;
	}
}
