<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\ItemWithFeatures;

class Summary {
	public static function peel(ItemWithFeatures $item): ?string {
		$type = $item->getFeature('type');
		switch($type) {
			//case 'location':
			// Not implemented

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

			case 'mouse':
			case 'keyboard':
			case 'network-switch':
			case 'network-hub':
			case 'modem-router':
				return SimpleDeviceSummarizer::summarize($item);
			 case 'monitor':
				return MonitorSummarizer::summarize($item);

			// case 'fdd':
			// case 'zip-drive':
				// return SomeSummarizer::summarize($item);
			// case 'external-psu':
				// return SomeSummarizer::summarize($item);

			// case 'printer':
			// case 'scanner':
			// Don't even bother

			// case 'inventoried-object':
				// How do you even implement this?

			default:
				// TODO: return SimpleDeviceSummarizer::summarize($item); when everything else is summarized
				return null;
		}
	}
}
