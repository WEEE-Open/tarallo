<?php


namespace WEEEOpen\Tarallo\Server;


interface ItemWithFeatures extends ItemWithCode {
	public function getFeature(string $name);
	public function removeFeatureByName(string $featureName);
	public function addFeature($feature);
	public function getFeatures(): array;
}
