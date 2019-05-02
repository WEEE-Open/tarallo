<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\Server\ItemFeatures;

class RamSummarizer implements Summarizer {

	public static function summarize(ItemFeatures $item): string {
		// TODO: use FeaturePrinter::prettyPrint as much as possible
		return 'RAM ...';
	}
}
