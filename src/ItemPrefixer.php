<?php

namespace WEEEOpen\Tarallo;

// TODO: use some sort of configuration file, or something that can be set by the user, instead of hardcoding every value
class ItemPrefixer {
	public static function get(Item $item) {
		$features = $item->getCombinedFeatures();
		if(!isset($features['type'])) {
			throw new \InvalidArgumentException('Item has no type, cannot generate code');
		}
		// TODO: more prefixes (NET, ADA, ODD, FL, HDD, AR, RAM, CPU, SA, SG, ETH)
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