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
	 * @return string[][] Translated group name => [UltraFeature, UltraFeature, ...]
	 *
	 * @deprecated
	 */
	public function getPrintableFeatures(array $features) {
		$groups = [];
		foreach($features as $feature) {
			/** @noinspection PhpUndefinedMethodInspection It's there. */
			$ultra = new UltraFeature($feature, $this->template->data()['lang'] ?? 'en');
			$groups[$ultra->group][] = $ultra;
		}
		ksort($groups);
		foreach($groups as $name => &$group) {
			usort($group, [TemplateUtilities::class, 'featureNameSort']);
		}

		return $groups;
	}

	private static function featureNameSort(UltraFeature $a, UltraFeature $b) {
		return $a->name <=> $b->name;
	}

	/**
	 * Wrap text into paragraphs ("p" tags), to be used in contenteditable elements
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	public function contentEditableWrap(string $html): string {
		$paragraphed = '<div>' . str_replace(["\r\n", "\r", "\n"], '</div><div>', $html) . '</div>';
		// According to the HTML spec, <p></p> should be ignored by browser.
		// When inserting an empty line in a contenteditable element, Firefox adds <p><br></p>, so...
		//return str_replace('<p></p>', '<p><br></p>', $paragraphed);
		return $paragraphed;
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
			$translated = FeaturePrinter::printableEnumValue($feature->name, $value);
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
