<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\ItemWithFeatures;

class Summary {
	public static function peel(ItemWithFeatures $item): ?string {
		$type = $item->getFeature('type');
		switch($type) {
			case null:
				// Otherwise it breaks the summarizers
				return 'Unknown item (set the type)';
			case 'case':
				 return CaseSummarizer::summarize($item);
			case 'motherboard':
				return MotherboardSummarizer::summarize($item);
			case 'cpu':
				return CpuSummarizer::summarize($item);
			case 'graphics-card':
				return GraphicCardSummarizer::summarize($item);
			case 'ram':
				return RamSummarizer::summarize($item);
			case 'hdd':
			case 'ssd':
				return HddSummarizer::summarize($item);
			case 'odd':
				return OddSummarizer::summarize($item);
			case 'external-psu':
			case 'psu':
				return PsuSummarizer::summarize($item);
			case 'audio-card':
			case 'ethernet-card':
			case 'ports-bracket':
			case 'card-reader':
			case 'other-card':
			case 'fan-controller':
			case 'modem-card':
			case 'scsi-card':
			case 'wifi-card':
			case 'bluetooth-card':
			case 'adapter':
			case 'usbhub':
			case 'tv-card':
				return SimplePortsSummarizer::summarize($item);
			case 'monitor':
			 	return MonitorSummarizer::summarize($item);
			// These are default cases, we'd get here anyway
//			case 'printer':
//			case 'scanner':
//			case 'inventoried-object':
//			case 'fdd':
//			case 'zip-drive':
//			case 'mouse':
//			case 'keyboard':
//			case 'network-switch':
//			case 'network-hub':
//			case 'modem-router':
			default:
				return SimpleDeviceSummarizer::summarize($item);
		}
	}
}
