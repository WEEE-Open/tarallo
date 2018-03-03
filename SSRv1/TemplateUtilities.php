<?php

namespace WEEEOpen\Tarallo\SSRv1;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use WEEEOpen\Tarallo\Server\Feature;

class TemplateUtilities implements ExtensionInterface {
	public $template;

	public function __construct() {

	}

	public function register(Engine $engine) {
		$engine->registerFunction('u', 'rawurlencode');
		$engine->registerFunction('getPrintableFeatures', [$this, 'getPrintableFeatures']);
		$engine->registerFunction('contentEditableWrap', [$this, 'contentEditableWrap']);
		$engine->registerFunction('getOptions', [$this, 'getOptions']);
	}

	/**
	 * @param Feature[] $features
	 *
	 * @return string[][] Translated group name => [Translated feature name => UltraFeature, ...]
	 */
	public function getPrintableFeatures(array $features) {
		$result = [];
		foreach($features as $feature) {
			/** @noinspection PhpUndefinedMethodInspection It's there. */
			$ultra = new UltraFeature($feature, $this->template->data()['lang'] ?? 'en');
			$result[$ultra->group][$ultra->name] = $ultra;
		}
		ksort($result);
		foreach($result as &$group) {
			ksort($group);
		}

		return $result;
	}

	public function contentEditableWrap(string $html) {
		return '<p>' . str_replace(["\r\n", "\r", "\n"], '</p><p>', $html) . '</p>';
	}

	public function getOptions(Feature $feature) {
		$options = Feature::getOptions($feature);
		foreach($options as $value => &$translated) {
			$translated = Localizer::printableValue($feature->name, $value);
		}
		asort($options);
		return $options;
	}
}
