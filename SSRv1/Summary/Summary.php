<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\Server\ItemWithFeatures;

class Summary {
	public static function peel(ItemWithFeatures $item): ?string {
		$type = $item->getFeature('type');
		switch($type) {
			case 'ram':
				return RamSummarizer::summarize($item);
			case 'hdd':
				return HddSummarizer::summarize($item);
			case 'cpu':
				return CpuSummarizer::summarize($item);
			default:
				return null;
		}
	}
}
