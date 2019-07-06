<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;


use WEEEOpen\Tarallo\Server\ItemWithCodeAndFeatures;

interface Summarizer {
	public static function summarize(ItemWithCodeAndFeatures $item): string;
}
