<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\Server\ItemWithFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class SimplePortsSummarizer implements Summarizer {

	public static function summarize(ItemWithFeatures $item): string {
		$type = FeaturePrinter::printableValue($item->getFeature('type'));
		$ports = PartialSummaries::summarizePorts($item, false, ' ');
		$sockets = PartialSummaries::summarizeSockets($item, true, ' ');
		$commercial = PartialSummaries::summarizeCommercial($item);
		$color = $item->getFeature('color');

		$pieces = [$type];
		if($ports !== '') {
			$pieces[] = $ports;
		}
		if($sockets !== '') {
			$pieces[] = $sockets;
		}
		if($color !== null) {
			$pieces[] = FeaturePrinter::printableValue($color);
		}
		if($commercial !== '') {
			$pieces[] = $commercial;
		}
		$pretty = implode(', ', $pieces);

		return $pretty;
	}
}
