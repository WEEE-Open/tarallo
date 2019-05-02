<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\Server\ItemFeatures;

interface Summarizer {
	public static function summarize(ItemFeatures $item): string;
}
