<?php

namespace WEEEOpen\Tarallo;

class ItemPrefixer
{
	/**
	 * Generate a prefix for an item.
	 *
	 * Long term project: move this data to the database or a configuration file,
	 * to allow other people to use their own prefixes.
	 *
	 * @param ItemWithFeatures $item
	 *
	 * @return string
	 */
	public static function get(ItemWithFeatures $item)
	{
		$features = $item->getFeatures();
		if (!isset($features['type'])) {
			throw new ItemPrefixerException(null, 'Item has no type, cannot generate code');
		}
		switch ($features['type']->value) {
			case 'mouse':
				return 'M';
			case 'keyboard':
				return 'T';
			case 'motherboard':
				return 'B';
			case 'cpu':
				return 'C';
			case 'graphics-card':
				return 'G';
			case 'ram':
				return 'R';
			case 'hdd':
			case 'ssd':
				if (self::has('sata-ports-n', $features)) {
					return 'S';
				} elseif (self::has('ide-ports-n', $features) || self::has('mini-ide-ports-n', $features)) {
					return 'H';
				} elseif (self::has('scsi-sca2-ports-n', $features) || self::has('scsi-db68-ports-n', $features)) {
					return 'SC';
				}  elseif (self::has('m2-connectors-n', $features)) {
					return 'SM';
				} else {
					throw new ItemPrefixerException(null, 'No or unknown hard drive connector, cannot generate a code');
				}
			case 'odd':
				return 'D';
			case 'psu':
			case 'external-psu':
				if (self::is('power-connector', 'da-2', $features)) {
					return 'AD';
				}
				return 'A';
			case 'monitor':
				return 'V';
			case 'fdd':
			case 'zip-drive':
				return 'FL';
			case 'card-reader':
			case 'ports-bracket':
			case 'other-card':
			case 'storage-card':
			case 'tv-card':
				return 'Q';
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
			case 'smartphone-tablet':
				return '';
			default:
				throw new ItemPrefixerException(null, 'No prefix found for type ' . $features['type']);
		}
	}

	private static function has(string $name, array $features): bool
	{
		$type = BaseFeature::getType($name);
		if ($type === BaseFeature::INTEGER || $type === BaseFeature::DOUBLE) {
			return isset($features[$name]) && $features[$name]->value > 0;
		} else {
			return isset($features[$name]);
		}
	}

	private static function is($name, $value, $features)
	{
		return isset($features[$name]) && $features[$name]->value === $value;
	}
}
