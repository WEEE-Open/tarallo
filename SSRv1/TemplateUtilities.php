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
		$engine->registerFunction('asTextContent', [$this, 'asTextContent']);
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

	/**
	 * Wrap text into paragraphs ("p" tags), to be used in contenteditable elements
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	public function contentEditableWrap(string $html): string {
		$paragraphed = '<p>' . str_replace(["\r\n", "\r", "\n"], '</p><p>', $html) . '</p>';
		// According to the HTML spec, <p></p> should be ignored by browser.
		// When inserting an empty line in a contenteditable element, Firefox adds <p><br></p>, so...
		return str_replace('<p></p>', '<p><br></p>', $paragraphed);
	}

	/**
	 * Get all options for an enum feature
	 *
	 * @param Feature $feature
	 *
	 * @return string[] Internal feature name => translated feature name
	 */
	public function getOptions(Feature $feature) {
		$options = Feature::getOptions($feature);
		foreach($options as $value => &$translated) {
			$translated = Localizer::printableValue($feature->name, $value);
		}
		asort($options);
		return $options;
	}

	/**
	 * Convert a string into the representation that textContent would give. That is, remove newlines.
	 *
	 * @param string $something
	 *
	 * @return string
	 */
	public function asTextContent(string $something): string {
		return str_replace(["\r\n", "\r", "\n"], '', $something);
	}
}
