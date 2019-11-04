<?php


namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\ItemWithFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class OddSummarizer implements Summarizer {

	public static function summarize(ItemWithFeatures $item): string {
		$type = $item->getFeature('type');
		$oddType = $item->getFeature('odd-type');
		$formFactor = $item->getFeature('odd-form-factor');
		$color = $item->getFeature('color');

		$type = FeaturePrinter::printableValue($type);
		$type .= $formFactor ? ' ' . FeaturePrinter::printableValue($formFactor) : '';
		$type .= $oddType ? ' ' . FeaturePrinter::printableValue($oddType) : '';

		$color = $color ? ', ' . FeaturePrinter::printableValue($color) : '';

		$commercial = PartialSummaries::summarizeCommercial($item);
		$commercial = $commercial ? ", $commercial" : '';

		$ports = PartialSummaries::summarizePorts($item, true, ' ');
		$ports = $ports ? ", $ports" : '';

		$pretty = $type . $ports . $color . $commercial;
		return $pretty;
	}
}