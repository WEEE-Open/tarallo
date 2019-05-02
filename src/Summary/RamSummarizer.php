<?php

namespace WEEEOpen\Tarallo\Server\Summary;


use WEEEOpen\Tarallo\Server\ItemFeatures;

class RamSummarizer implements Summarizer {

	public static function summarize(ItemFeatures $item): string {
		return 'Kingston KVR123456/1G 400 MHz 1 GiB';
	}
}
