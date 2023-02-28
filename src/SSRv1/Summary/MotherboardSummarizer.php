<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;

use WEEEOpen\Tarallo\ItemWithFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class MotherboardSummarizer implements Summarizer
{
	public static function summarize(ItemWithFeatures $item): array
	{
		$type = $item->getFeature('type');
		$socket = $item->getFeature('cpu-socket');
		$formFactor = $item->getFeature('motherboard-form-factor');
		$color = $item->getFeature('color');

		$type = FeaturePrinter::printableValue($type);
		$type .= $formFactor ? ' ' . FeaturePrinter::printableValue($formFactor) : '';

		if ($socket) {
			$value = FeaturePrinter::printableValue($socket);
			if (strcmp($value, "Socket", strlen(6))) {
				$value = "Socket $value";
			}

			$type .= " $value";
		}

		$ports = PartialSummaries::summarizePorts($item, false, ', ');

		$sockets = PartialSummaries::summarizeSockets($item, false, ', ');

		$color = $color ? FeaturePrinter::printableValue($color) : '';

		$commercial = PartialSummaries::summarizeCommercial($item);

		return array_filter([$type, $sockets, $ports, $color, $commercial]);
	}
}
