<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\Server\ItemWithCodeAndFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class RamSummarizer implements Summarizer {

	public static function summarize(ItemWithCodeAndFeatures $item): string {
		$ecc = $item->getFeature('ram-ecc');
		if($ecc === null) {
			$ecc = '(ECC?)';
		} else if($ecc->value === 'yes') {
			$ecc = 'ECC';
		} else if($ecc->value === 'no') {
			$ecc = null;
		} else {
			$ecc = '(ECC?)';
		}

		$type = $item->getFeature('ram-type');
		$ff = $item->getFeature('ram-form-factor');
		$capacity = $item->getFeature('capacity-byte');
		$freq = $item->getFeature('frequency-hertz');
		$brand = $item->getFeature('brand');
		$model = $item->getFeature('model');

		if($type && $ff && $type->value === 'simm' && $ff->value === 'simm') {
			// Avoid "RAM SIMM SIMM something, ...", one "SIMM" is enough
			$ff = null;
		}

		$technical = FeaturePrinter::printableValue($item->getFeature('type'));
		$technical .= $ecc ? " $ecc" : '';
		$technical .= $type ? ' ' . FeaturePrinter::printableValue($type) : '';
		$technical .= $ff ? ' ' . FeaturePrinter::printableValue($ff) : '';
		$technical .= $capacity ? ' ' . FeaturePrinter::printableValue($capacity) : '';
		$technical .= $freq ? ' ' . FeaturePrinter::printableValue($freq) : '';

		if(!$brand && $model) {
			// To avoid possible confusion with serial numbers...
			$commercial = ' ' . FeaturePrinter::printableName('model') . ': ' . FeaturePrinter::printableValue($model);
		} else {
			$commercial = $brand ? ' ' . FeaturePrinter::printableValue($brand) : '';
			$commercial .= $model ? ' ' . FeaturePrinter::printableValue($model) : '';
		}

		if($technical === 'RAM (ECC?)') {
			// Looks nicer
			$technical = 'RAM';
		}
		if($technical !== '' && $commercial !== '') {
			// $commercial already has a leading space
			$pretty = "$technical,$commercial";
		} else {
			// One is an empty string, so it won't change the output.
			// Actually, $commercial is the only one that can be empty...
			$pretty = "$technical$commercial";
		}

		return $pretty;
	}
}
