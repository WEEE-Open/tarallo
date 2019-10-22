<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\ItemWithFeatures;

interface Summarizer {
	public static function summarize(ItemWithFeatures $item): string;
}
