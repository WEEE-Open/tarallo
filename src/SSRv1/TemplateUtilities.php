<?php

namespace WEEEOpen\Tarallo\SSRv1;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use WEEEOpen\Tarallo\BaseFeature;
use WEEEOpen\Tarallo\Feature;

class TemplateUtilities implements ExtensionInterface
{
	public $template;

	public function __construct()
	{
	}

	public function register(Engine $engine)
	{
		$engine->registerFunction('u', 'rawurlencode');
		$engine->registerFunction('getUltraFeatures', [$this, 'getUltraFeatures']);
		$engine->registerFunction('getGroupedFeatures', [$this, 'getGroupedFeatures']);
		$engine->registerFunction('printFeature', [$this, 'printFeature']);
		$engine->registerFunction('printExplanation', [$this, 'printExplanation']);
		$engine->registerFunction('colorToHtml', [$this, 'colorToHtml']);
		$engine->registerFunction('contentEditableWrap', [$this, 'contentEditableWrap']);
		$engine->registerFunction('getOptions', [$this, 'getOptions']);
		$engine->registerFunction('asTextContent', [$this, 'asTextContent']);
		$engine->registerFunction('prettyPrintJson', [$this, 'prettyPrintJson']);
	}

	/**
	 * @param Feature[] $features
	 *
	 * @return UltraFeature[]
	 */
	public function getUltraFeatures(array $features): array
	{
		$result = [];

		foreach ($features as $feature) {
			$ultra = UltraFeature::fromFeature($feature, $this->template->data()['lang'] ?? 'en');
			$result[] = $ultra;
		}
		return $result;
	}

	/**
	 * @param UltraFeature[] $ultraFeatures
	 * @return UltraFeature[][] Translated group name => [UltraFeature, UltraFeature, ...]
	 */
	public function getGroupedFeatures(array $ultraFeatures): array
	{
		$groups = [];
		$groupsPrintable = [];

		// $group has the group ID as key, the human-radable name comes later
		foreach ($ultraFeatures as $ultra) {
			$groups[BaseFeature::getGroup($ultra->name)][] = $ultra;
		}

		// Group IDs are numbered, that's the order, so it has to be sorted HERE
		// rather than later, when FeaturePrinter::printableGroup has translated
		// IDs to human-readable names
		ksort($groups);

		foreach ($groups as $groupId => &$ultraFeatures) {
			usort($ultraFeatures, [TemplateUtilities::class, 'featureNameSort']);
			$groupsPrintable[FeaturePrinter::printableGroup($groupId)] = $ultraFeatures;
		}

		return $groupsPrintable;
	}

	private static function featureNameSort(UltraFeature $a, UltraFeature $b): int
	{
		return $a->pname <=> $b->pname;
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
	public function printFeature(string $feature, $value, ?string $lang = null): string
	{
		return UltraFeature::printableValue(new Feature($feature, $value), $lang ?? 'en');
	}

	public function printExplanation(UltraFeature $ultra, ?string $lang = null): string
	{
		return $ultra->printableExplanation($lang ?? 'en') ?? '';
	}

	public function colorToHtml(string $color): string
	{
		$from = ['sip-brown', 'brown', 'darkgrey', 'orange', 'copper', 'golden', 'yellowed', 'weeerde', '-'];
		$to   = ['#CB8', 'saddlebrown', 'dimgrey', 'darkorange', 'sandybrown', 'gold', 'lightyellow', '#00983a', ''];
		return str_replace($from, $to, $color);
	}

	/**
	 * Wrap text into paragraphs ("div" tags), to be used in contenteditable elements
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	public function contentEditableWrap(string $html): string
	{
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
	public function getOptions(string $name): array
	{
		$options = BaseFeature::getOptions($name);
		foreach ($options as $value => &$translated) {
			$translated = FeaturePrinter::printableEnumValue($name, $value);
		}
		asort($options);
		return $options;
	}

	/**
	 * Convert a string into the representation that textContent would give. That is, remove newlines.
	 * Null is converted to an empty string.
	 *
	 * @param string|null $something
	 *
	 * @return string
	 */
	public function asTextContent(?string $something): string
	{
		if ($something === null) {
			return '';
		} else {
			return str_replace(["\r\n", "\r", "\n"], '', $something);
		}
	}

	/**
	 * Formats JSON in a readable form (in case of minfied ones)
	 *
	 * @param string $json JSON as a string
	 *
	 * @return string
	 */
	public function prettyPrintJson(string $json): string
	{
		$result = '';
		$level = 0;
		$in_quotes = false;
		$in_escape = false;
		$ends_line_level = null;
		$json_length = strlen($json);

		for ($i = 0; $i < $json_length; $i++) {
			$char = $json[$i];
			$new_line_level = null;
			$post = "";
			if ($ends_line_level !== null) {
				$new_line_level = $ends_line_level;
				$ends_line_level = null;
			}
			if ($in_escape) {
				$in_escape = false;
			} else {
				if ($char === '"') {
					$in_quotes = !$in_quotes;
				} else {
					if (!$in_quotes) {
						switch ($char) {
							case '}':
							case ']':
								$level--;
								$ends_line_level = null;
								$new_line_level = $level;
								break;

							case '{':
							/** @noinspection PhpMissingBreakStatementInspection */
							case '[':
								$level++;
								// no break
							case ',':
								$ends_line_level = $level;
								break;

							case ':':
								$post = " ";
								break;

							case " ":
							case "\t":
							case "\n":
							case "\r":
								$char = "";
								$ends_line_level = $new_line_level;
								$new_line_level = null;
								break;
						}
					} else {
						if ($char === '\\') {
							$in_escape = true;
						}
					}
				}
			}
			if ($new_line_level !== null) {
				$result .= "\n" . str_repeat("  ", $new_line_level);
			}
			$result .= $char . $post;
		}

		return $result;
	}
}
