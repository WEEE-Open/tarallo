<?php

namespace WEEEOpen\Tarallo;

use WEEEOpen\Tarallo\SSRv1\Summary\Summary;

/**
 * Products, once called "default items".
 *
 * @package WEEEOpen\Tarallo
 */
class Product extends ProductCode implements \JsonSerializable, ItemWithFeatures
{
	use ItemTraitFeatures;

	public static function fromItem(Item $item): Product
	{
		$brand = $item->getFeatureValue('brand');
		$model = $item->getFeatureValue('model');
		$variant = $item->getFeatureValue('variant');
		$product = new Product($brand, $model, $variant);
		foreach ($item->getOwnFeatures() as $feature) {
			if (!isset(BaseFeature::ITEM_ONLY_FEATURES[$feature->name])) {
				$product->addFeature($feature);
			}
		}
		return $product;
	}

	public function getSummary(): ?array
	{
		return Summary::peel($this);
	}

	public function jsonSerialize()
	{
		$array = [];
		$array['brand'] = $this->brand;
		$array['model'] = $this->model;
		$array['variant'] = $this->variant;
		$array['features'] = [];
		if (!empty($this->features)) {
			foreach ($this->features as $features) {
				$array['features'][$features->name] = $features->value;
			}
		}
		$array['summary'] = $this->getSummary();
		return $array;
	}

	public function __toString()
	{
		if ($this->variant === self::DEFAULT_VARIANT) {
			return $this->brand . ' ' . $this->model;
		} else {
			return $this->brand . ' ' . $this->model . ' (' . $this->variant . ')';
		}
	}
}
