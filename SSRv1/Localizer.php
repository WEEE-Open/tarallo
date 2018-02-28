<?php
namespace WEEEOpen\Tarallo\SSRv1;


class Localizer {
	/**
	 * @param string $locale E.g. en_US, es, etc...
	 */
	public static function localize(string $locale) {
		$locale = explode('-', $locale, 2);
		if(count($locale) === 1) {
			$locale = $locale[0];
		} else {
			$locale = $locale[0] . '_' . strtoupper($locale[1]);
		}

		putenv("LC_ALL=$locale.UTF-8");
		setlocale(LC_ALL, "$locale.UTF-8");

		bindtextdomain("tarallo", __DIR__ . DIRECTORY_SEPARATOR . "locale");
		textdomain("tarallo");
	}
}
