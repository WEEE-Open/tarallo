<?php


namespace WEEEOpen\Tarallo\Server;


interface ItemWithFeatures extends ItemWithCode {
	public function getFeature(string $name);
	public function getFeatureValue(string $name);
	public function removeFeatureByName(string $featureName);
	public function addFeature($feature);
	public function getFeatures(): array;

	public function addContent(ItemWithCode $item);
	public function removeContent(ItemWithCode $item);
	public function getContent(): array;
}
