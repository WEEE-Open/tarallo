<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\Server\ItemFeatures;

class Summary {
	public static function peel(ItemFeatures $item): string {
		$type = $item->getFeature('type');
		switch($type) {
			case 'ram':
				return RamSummarizer::summarize($item);
			case 'hdd':
				//return HddSummarizer::summarize($item);
				return 'foo';
			default:
				throw new \InvalidArgumentException("Cannot generate summary for items of type $type");
		}
	}
}
