<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\Server\ItemFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class RamSummarizer implements Summarizer {

	public static function summarize(ItemFeatures $item): string {
		// TODO: use FeaturePrinter::prettyPrint as much as possible
		$prettySumm = FeaturePrinter::printableValue($item->getFeature('type'));
		$prettySumm = $prettySumm . ' ' . FeaturePrinter::printableValue($item->getFeature('ram-type'));
		$prettySumm = $prettySumm . ' ' . FeaturePrinter::printableValue($item->getFeature('ram-form-factor'));
		$prettySumm = $prettySumm . ' ' . FeaturePrinter::printableValue($item->getFeature('capacity-byte'));
		$prettySumm = $prettySumm . ' ' . FeaturePrinter::printableValue($item->getFeature('frequency-hertz'));
		$prettySumm = $prettySumm . ',';
		$prettySumm = $prettySumm . ' ' . FeaturePrinter::printableValue($item->getFeature('brand'));
		$prettySumm = $prettySumm . ' ' . FeaturePrinter::printableValue($item->getFeature('model'));

		return $prettySumm;
	}
}
