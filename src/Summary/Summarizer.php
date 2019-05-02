<?php

namespace WEEEOpen\Tarallo\Server\Summary;


use WEEEOpen\Tarallo\Server\ItemFeatures;

interface Summarizer {
	public static function summarize(ItemFeatures $item): string;
}
