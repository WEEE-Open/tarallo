<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\Server\ItemWithFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class HddSummarizer implements Summarizer {

	public static function summarize(ItemWithFeatures $item): string {
		$type = $item->getFeature('type');
		$capacity = $item->getFeature('capacity-decibyte');
		$nPorts = $item->getFeature('sata-ports-n');
		$formFactor = $item->getFeature('hdd-form-factor');
		$spinRate = $item->getFeature('spin-rate-rpm');
		$brand = $item->getFeature('brand');
		$family = $item->getFeature('family');
		$model = $item->getFeature('model');
		$os = $item->getFeature('software');

		$hardware = FeaturePrinter::printableValue($type);
		$hardware .= ' ' . FeaturePrinter::printableValue($capacity);
		if($nPorts !== null) {
			if($nPorts->value == 1) {
				$hardware .= ' ' . FeaturePrinter::printableName('sata-ports-n');
			} else {
				$hardware .= ' ' . $nPorts . '×' . FeaturePrinter::printableName('sata-ports-n');
			}
		}

		$hardware .= ' ' . FeaturePrinter::printableValue($formFactor);
		$hardware .= ' ' . FeaturePrinter::printableValue($spinRate);
		return $hardware;
	}
}