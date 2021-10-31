<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;

use WEEEOpen\Tarallo\ItemWithFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class CpuSummarizer implements Summarizer
{

	public static function summarize(ItemWithFeatures $item): string
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

		$coreStats = '';
		$coreStats .= $numCores ? ' ' . $numCores . ' ' . FeaturePrinter::printableName('core-n') : '';
		$coreStats .= $numThreads ? ' ' . $numThreads . ' ' . FeaturePrinter::printableName('thread-n') : '';
		if ($coreStats === '') {
			$at = '';
		} else {
			$at = ' @';
		}
		$coreStats .= $frequency ? $at . ' ' . FeaturePrinter::printableValue($frequency) : '';

		$commercial = PartialSummaries::summarizeCommercial($item);

		if ($socket) {
			$socket = FeaturePrinter::printableValue($socket);
			$socket = explode(' (', $socket)[0];
		} else {
			$socket = '';
		}

		if (empty($coreStats) && empty($commercial) && empty($socket)) {
			$pretty = FeaturePrinter::printableValue($type);
		} else {
			$pretty = $architecture;
			if ($coreStats !== '') {
				$pretty .= ",$coreStats";
			}
			if ($socket !== '') {
				$theWordSocketLiterally = str_replace(' (CPU)', '', FeaturePrinter::printableName('cpu-socket'));
				$pretty .= ', ' . $theWordSocketLiterally . ' ' . $socket;
			}
			if ($commercial !== '') {
				$pretty .= ", $commercial";
			}
		}


		return $pretty;
	}
}
