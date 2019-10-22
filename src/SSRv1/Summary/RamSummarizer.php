<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\ItemWithFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class RamSummarizer implements Summarizer {

	public static function summarize(ItemWithFeatures $item): string {
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

		$commercial = PartialSummaries::summarizeCommercial($item);

		if($technical === 'RAM (ECC?)') {
			// Looks nicer
			$technical = 'RAM';
		}
		if($technical !== '' && $commercial !== '') {
			$pretty = "$technical, $commercial";
		} else if($technical !== '') {
			$pretty = $technical;
		} else {
			$pretty = $commercial;
		}

		return $pretty;
	}
}
