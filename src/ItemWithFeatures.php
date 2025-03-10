<?php

namespace WEEEOpen\Tarallo;

interface ItemWithFeatures
{
	public function getFeature(string $name);
	public function getFeatureValue(string $name);
	public function removeFeatureByName(string $featureName);
	public function addFeature($feature);
	public function getFeatures(): array;
	public function getOwnFeatures(): array;
	public function getSummary(): ?array;
	public function getTypeForIcon(): string;
}
