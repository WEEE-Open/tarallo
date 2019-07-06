<?php


namespace WEEEOpen\Tarallo\Server;

/**
 * TODO: merge this into ItemWithCodeAndFeatures? Nothing uses it alone...
 * Also: ItemIncomplete still implements ItemWithCode...
 *
 * @deprecated Use ItemWithCodeAndFeatures instead?
 * @package WEEEOpen\Tarallo\Server
 */
interface ItemWithFeatures {
	public function getFeature(string $name);
	public function removeFeatureByName(string $featureName);
	public function addFeature($feature);
	public function getFeatures(): array;
}