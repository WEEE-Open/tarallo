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

		$domain = 'tarallo';
		$directory = __DIR__ . DIRECTORY_SEPARATOR . 'locale';

		// putenv is probably not necessary
		putenv("LC_ALL=$locale.utf8");
		setlocale(LC_ALL, "$locale.utf8");

		bindtextdomain($domain, $directory);
		bind_textdomain_codeset($domain, 'UTF-8');
		textdomain($domain);
	}
}
