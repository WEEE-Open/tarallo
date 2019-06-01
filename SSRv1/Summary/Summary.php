<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\Server\ItemFeatures;

class Summary {
	public static function peel(ItemFeatures $item): ?string {
		$type = $item->getFeature('type');
		switch($type) {
			case 'ram':
				return RamSummarizer::summarize($item);
			case 'hdd':
				//return HddSummarizer::summarize($item);
			case 'cpu':
				//return CpuSummarizer::summarize($item);
			default:
				return null;
		}
	}
}
