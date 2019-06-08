<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\Server\ItemFeatures;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class CpuSummarizer implements Summarizer {

	public static function summarize(ItemFeatures $item): string {

		$type = $item->getFeature('type');
		$isa = $item->getFeature('isa');
		$numCores = $item->getFeature('core-n');
		$numThreads = $item->getFeature('thread-n');
		$frequency = $item->getFeature('frequency-hertz');
		$brand = $item->getFeature('brand');
		$model = $item->getFeature('model');
		$socket = $item->getFeature('cpu-socket');

		$architecture = FeaturePrinter::printableValue($type);
		$architecture .= $isa ? ' ' . FeaturePrinter::printableValue($isa) : ' (architecture?)';

		$coreStats = '';
		$coreStats .= $numCores ? ' ' . $numCores . ' ' . FeaturePrinter::printableName('core-n') : '';
		$coreStats .= $numThreads ? ' ' . $numThreads . ' ' . FeaturePrinter::printableName('thread-n') : '';
		$coreStats .= $frequency ? ' ' . '@ ' . FeaturePrinter::printableValue($frequency) : '';

		if(!$brand && $model) {
			// To avoid possible confusion with serial numbers...
			$commercial = ' ' . FeaturePrinter::printableValue($model);
		} else {
			$commercial = $brand ? ' ' . FeaturePrinter::printableValue($brand) : '';
			$commercial .= $model ? ' ' . FeaturePrinter::printableValue($model) : '';
		}

		if($socket) {
			$socket = FeaturePrinter::printableValue($socket);
			$socketOnly = explode(' ', $socket);
		} else
			$socket = '';

		if(empty($coreStats) && empty($commercial) && empty($socket))
			$pretty = FeaturePrinter::printableValue($type);
		else {
			$pretty = $architecture;
			if($coreStats !== '')
				$pretty .= ",$coreStats";
			if($commercial !== '')
				$pretty .= ",$commercial";
			$pretty .= !empty($socketOnly) ? ', ' . FeaturePrinter::printableName(
					'cpu-socket'
				) . ' ' . $socketOnly[0] : '';
		}


		return $pretty;
	}
}