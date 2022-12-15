<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;

use WEEEOpen\Tarallo\ItemWithFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class CpuSummarizer implements Summarizer
{
	public static function summarize(ItemWithFeatures $item): array
	{

		$type = $item->getFeature('type');
		$isa = $item->getFeature('isa');
		$numCores = $item->getFeature('core-n');
		$numThreads = $item->getFeature('thread-n');
		$frequency = $item->getFeature('frequency-hertz');
		$socket = $item->getFeature('cpu-socket');

		$architecture = FeaturePrinter::printableValue($type);
		if ($isa) {
			$architecture .= ' ' . FeaturePrinter::printableValue($isa);
		} else {
			$architecture .= ' (' . FeaturePrinter::printableName('isa') . '?)';
		}

		$coreStats = [$numCores ? "$numCores " . FeaturePrinter::printableName('core-n') : ''];
		$coreStats[] = $numThreads ? "$numThreads " . FeaturePrinter::printableName('thread-n') : '';
		$coreStats = implode(' ', array_filter($coreStats));
		if ($coreStats === '') {
			$at = '';
		} else {
			$at = ' @ ';
		}
		$coreStats .= $frequency ? $at . FeaturePrinter::printableValue($frequency) : '';

		$commercial = PartialSummaries::summarizeCommercial($item);

		if ($socket) {
			$socket = FeaturePrinter::printableValue($socket);
			$socket = explode(' (', $socket)[0];
		}

		if (empty($coreStats) && empty($commercial) && empty($socket)) {
			return [FeaturePrinter::printableValue($type)];
		}

		return array_filter([$architecture, $coreStats, $socket ? "Socket $socket" : '', $commercial ]);
	}
}
