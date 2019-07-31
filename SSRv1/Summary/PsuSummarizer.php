<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\Server\ItemWithFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class PsuSummarizer implements Summarizer {

	public static function summarize(ItemWithFeatures $item): string {
		$type = FeaturePrinter::printableValue($item->getFeature('type'));
		$power = PartialSummaries::summarizePowerconnectors($item);
		$commercial = PartialSummaries::summarizeCommercial($item);

		// TODO: finish this
		$pretty = "$type ($power), $commercial";

		return $pretty;
	}
}
