<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;

use WEEEOpen\Tarallo\ItemWithFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class CaseSummarizer implements Summarizer
{
	public static function summarize(ItemWithFeatures $item): array
	{
		$type = $item->getFeature('type');
		$form = $item->getFeature('motherboard-form-factor');
		$color = $item->getFeature('color');

		$type = FeaturePrinter::printableValue($type);
		$type .= $form ? ' ' . FeaturePrinter::printableValue($form) : '';

		$ports = PartialSummaries::summarizePorts($item, false);
		$color = $color ? FeaturePrinter::printableValue($color) : '';
		$commercial = PartialSummaries::summarizeCommercial($item);

		return array_filter([$type, $ports, $color, $commercial]);
	}
}
