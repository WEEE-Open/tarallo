<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\Server\ItemFeatures;

class CpuSummarizer implements Summarizer {

	public static function summarize(ItemFeatures $item): string {
		// TODO: Implement summarize() method.
		// Use these functions to obtain the literal parts of the summary string, so they are translatable:
		// Cores = FeaturePrinter::printableName('cores-n')
		// Threads = FeaturePrinter::printableName('threads-n')
		// Socket (CPU) = FeaturePrinter::printableName('cpu-socket')
		//
		// e.g. "2 Cores" = FeaturePrinter::printableValue(...) . ' ' . FeaturePrinter::printableName('cores-n')
		return '';
	}
}