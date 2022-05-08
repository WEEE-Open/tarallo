<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;

use WEEEOpen\Tarallo\ItemWithFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class CaseSummarizer implements Summarizer
{
	public static function summarize(ItemWithFeatures $item): string
	{
		$type = $item->getFeature('type');
		$form = $item->getFeature('motherboard-form-factor');
		$color = $item->getFeature('color');

		$type = FeaturePrinter::printableValue($type);
		$type .= $form ? ' ' . FeaturePrinter::printableValue($form) : '';

		$ports = PartialSummaries::summarizePorts($item, false);
		$ports = $ports ? " ($ports)" : '';

		$color = $color ? ', ' . FeaturePrinter::printableValue($color) : '';

		$commercial = PartialSummaries::summarizeCommercial($item);
		$commercial = $commercial ? ", $commercial" : '';

		$pretty = $type . $ports . $color . $commercial;

		return $pretty;
	}
}
