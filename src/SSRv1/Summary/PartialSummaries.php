<?php

namespace WEEEOpen\Tarallo\SSRv1\Summary;

use WEEEOpen\Tarallo\BaseFeature;
use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\ItemWithFeatures;
use WEEEOpen\Tarallo\ProductCode;
use WEEEOpen\Tarallo\SSRv1\FeaturePrinter;

class PartialSummaries
{
	public static function summarizeCommercial(ItemWithFeatures $item)
	{
		$brand = $item instanceof ProductCode ? $item->getBrand() : $item->getFeatureValue('brand');
		$manufacturer = $item->getFeatureValue('brand-manufacturer');
		$family = $item->getFeatureValue('family');
		$model = $item instanceof ProductCode ? $item->getModel() : $item->getFeatureValue('model');
		$variant = $item instanceof ProductCode ? $item->getVariant() : $item->getFeatureValue('variant');
		$internal = $item->getFeatureValue('internal-name');

		$parts = [];
		if ($brand !== null) {
			$parts[] = $brand;
		}
		if ($family !== null) {
			if ($model === null || strpos($model, $family) === false) {
				$parts[] = $family;
			}
		}
		if ($internal === null) {
			if ($manufacturer !== null) {
				$parts[] = "($manufacturer)";
			}
			if ($model !== null) {
				$parts[] = $model;
			}
		} else {
			if ($model !== null) {
				$parts[] = $model;
			}
			if ($manufacturer !== null) {
				$parts[] = "($manufacturer)";
			}
			$parts[] = "($internal)";
		}
		if ($variant !== null && $variant !== ProductCode::DEFAULT_VARIANT) {
			$parts[] = "$variant";
		}
		if (count($parts) <= 0) {
			return '';
		}
		$concat = implode(' ', $parts);
		$concat = str_replace(') (', ' ', $concat); // Asus (Nvidia) (GF17) => Asus (Nvidia GF17)
		return $concat;
	}

	public static function summarizePorts(ItemWithFeatures $item, bool $compact = false, string $glue = ', '): string
	{
		$filtered = self::getFeaturesInGroup($item, BaseFeature::GROUP_PORTS);
		$sequence = self::summarizeSequence($compact, $filtered);

		return implode($glue, $sequence);
	}

	public static function summarizeSockets(ItemWithFeatures $item, bool $compact = false, string $glue = ', '): string
	{
		$filtered = self::getFeaturesInGroup($item, BaseFeature::GROUP_SOCKETS);
		unset($filtered['cpu-socket']);
		$sequence = self::summarizeSequence($compact, $filtered);

		return implode($glue, $sequence);
	}

	public static function summarizePowerconnectors(ItemWithFeatures $item, bool $compact = false, string $glue = ', '): string
	{
		$filtered = self::getFeaturesInGroup($item, BaseFeature::GROUP_POWERCONNECTORS);
		$sequence = self::summarizeSequence($compact, $filtered);

		return implode($glue, $sequence);
	}

	/**
	 * @param ItemWithFeatures $item
	 * @param int $group
	 *
	 * @return Feature[]
	 */
	private static function getFeaturesInGroup(ItemWithFeatures $item, int $group): array
	{
		$filtered = [];
		foreach ($item->getFeatures() as $feature) {
			// Skip BaseFeature(s) from ItemIncomplete, these cannot be summarized
			if (
				$feature instanceof Feature
				&& isset(BaseFeature::GROUPS[$feature->name])
				&& BaseFeature::GROUPS[$feature->name] === $group
			) {
				$filtered[$feature->name] = $feature;
			}
		}
		return $filtered;
	}

	/**
	 * Summarize features in a sequence, useful for ports and the like
	 *
	 * @param bool $compact True to hide 1× when the value is 1 (there is 1 port of that type), false to always print 1×
	 * @param array $features Features to print
	 *
	 * @return array Pretty printed features & values, implode them with a comma or a space or whatever
	 */
	private static function summarizeSequence(bool $compact, array $features): array
	{
		$sequence = [];
		foreach ($features as $feature) {
			$value = (string) FeaturePrinter::printableValue($feature);
			$name = FeaturePrinter::printableName($feature->name);
			if ($compact && $value === '1') {
				$sequence["3$name"] = $name;
			} elseif (is_numeric($value)) {
				$sequence["3$name"] = "${value}× $name";
			} elseif ($feature->name === 'psu-connector-cpu') {
				// It's hardcoded, yes, it's identical in every human language, yes, it's a hack, yes
				$sequence["2$name"] = "$value CPU";
			} elseif ($feature->name === 'psu-connector-motherboard') {
				$sequence["1$name"] = "$value Mobo";
			} else {
				$sequence["0$name"] = "$value"; // e.g. "ATX 24 pin", it's obvious what it is an enum feature
			}
		}
		ksort($sequence);
		// This saves memory and provides a nicer result but it could be useless.
		// If you get here hunting for slow code with a profiler, consider removing it.
		$sequence = array_values($sequence);
		return $sequence;
	}
}
