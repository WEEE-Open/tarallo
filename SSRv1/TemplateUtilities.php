<?php

namespace WEEEOpen\Tarallo\SSRv1;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use WEEEOpen\Tarallo\Server\BaseFeature;
use WEEEOpen\Tarallo\Server\Feature;

class TemplateUtilities implements ExtensionInterface {
	public $template;

	public function __construct() {

	}

	public function register(Engine $engine) {
		$engine->registerFunction('u', 'rawurlencode');
		$engine->registerFunction('getUltraFeatures', [$this, 'getUltraFeatures']);
		$engine->registerFunction('getGroupedFeatures', [$this, 'getGroupedFeatures']);
		$engine->registerFunction('printFeature', [$this, 'printFeature']);
		$engine->registerFunction('contentEditableWrap', [$this, 'contentEditableWrap']);
		$engine->registerFunction('getOptions', [$this, 'getOptions']);
		$engine->registerFunction('asTextContent', [$this, 'asTextContent']);
	}

	/**
	 * @param Feature[] $features
	 *
	 * @return UltraFeature[]
	 */
	public function getUltraFeatures(array $features) {
		$result = [];

		foreach($features as $feature) {
			/** @noinspection PhpUndefinedMethodInspection It's there. */
			$ultra = UltraFeature::fromFeature($feature, $this->template->data()['lang'] ?? 'en');
			$result[] = $ultra;
		}
		return $result;

	}

	/**
	 * @param UltraFeature[] $ultraFeatures
	 * @return UltraFeature[][] Translated group name => [UltraFeature, UltraFeature, ...]
	 */
	public function getGroupedFeatures(array $ultraFeatures) {
		$groups = [];
		$groupsPrintable = [];

		foreach($ultraFeatures as $ultra) {
			$groups[BaseFeature::getGroup($ultra->feature->name)][] = $ultra;
		}

		// Group IDs are numbered, that's the order, so it has to be sorted HERE
		// rather than later, when FeaturePrinter::printableGroup has translated
		// IDs to human-readable names
		ksort($groups);

		foreach($groups as $groupId => &$ultraFeatures) {
			usort($ultraFeatures, [TemplateUtilities::class, 'featureNameSort']);
			$groupsPrintable[FeaturePrinter::printableGroup($groupId)] = $ultraFeatures;
		}

		return $groupsPrintable;
	}

	private static function featureNameSort(UltraFeature $a, UltraFeature $b) {
		return $a->name <=> $b->name;
	}

	/**
	 * Print a single feature, if you have its parts (useful for statistics).
	 * Use UltraFeature::printableValue directly if you have the entire feature.
	 *
	 * @see UltraFeature::printableValue
	 *
	 * @param string $feature Feature name
	 * @param int|double|string $value Feature value
	 * @param string|null $lang Page language code
	 *
	 * @return string nice printable value
	 */
	public function printFeature(string $feature, $value, ?string $lang): string {
		return UltraFeature::printableValue(new Feature($feature, $value), $lang ?? 'en');
	}

	/**
	 * Wrap text into paragraphs ("div" tags), to be used in contenteditable elements
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	public function contentEditableWrap(string $html): string {
		$paragraphed = '<div>' . str_replace(["\r\n", "\r", "\n"], '</div><div>', $html) . '</div>';
		// According to the HTML spec, <div></div> should be ignored by browser.
		// Firefox used to insert <p><br></p> for empty lines, for <div>s it does absolutely nothing but still displays them, soooo...
		return str_replace('<div></div>', '<div><br></div>', $paragraphed);
		// Or replace with '' to remove empty lines: cool, huh?
		//return $paragraphed;
	}

	/**
	 * Get all options for an enum feature
	 *
	 * @param string $name Feature name
	 *
	 * @return string[] Internal feature name => translated feature name
	 */
	public function getOptions(string $name) {
		$options = BaseFeature::getOptions($name);
		foreach($options as $value => &$translated) {
			$translated = FeaturePrinter::printableEnumValue($name, $value);
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
