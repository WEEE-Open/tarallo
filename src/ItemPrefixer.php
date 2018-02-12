<?php

namespace WEEEOpen\Tarallo\Server;

class ItemPrefixer {
	/**
	 * Generate a prefix for an item.
	 *
	 * @param Item $item
	 *
	 * @return string
	 * @throws \InvalidArgumentException if required features for type generation are not present ("features" means "type" and that's it, others are currently unused)
	 */
	public static function get(Item $item) {
		$features = $item->getCombinedFeatures();
		if(!isset($features['type'])) {
			throw new \InvalidArgumentException('Item has no type, cannot generate code');
		}
		// TODO: more prefixes (NET, ADA, ODD, FL, HDD, AR, R, C, B, SA, SG)
		switch($features['type']) {
			case 'mouse':
				return 'M';
			case 'keyboard':
				return 'T';
			case 'psu':
				if(isset($features['brand']) && isset($features['model']) && $features['brand'] === 'Dell' && $features['model'] === 'DA-2') {
					return 'AD';
				}

				return 'A';
			case 'case':
				return '';
			default:
				throw new \InvalidArgumentException('No prefix found for type ' . $features['type']);
		}
	}
}