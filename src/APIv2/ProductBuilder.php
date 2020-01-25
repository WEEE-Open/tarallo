<?php

namespace WEEEOpen\Tarallo\APIv2;

use WEEEOpen\Tarallo\Product;
use WEEEOpen\Tarallo\ValidationException;

class ProductBuilder {
	/**
	 * Build an product, return it.
	 *
	 * @param array $input Decoded JSON from the client
	 * @param string $brand
	 * @param string $model
	 * @param string $variant
	 *
	 * @return Product
	 */
	public static function ofArray(array $input, string $brand, string $model, string $variant): Product {
		return self::ofArrayInternal($input, $brand, $model, $variant);
	}

	private static function ofArrayInternal(array $input, string $brand, string $model, string $variant): Product {
		$product = new Product($brand, $model, $variant);

		if(isset($input['features'])) {
			if(!is_array($input['features'])) {
				throw new ValidationException();
			}
			ItemBuilder::addFeatures($input['features'], $product);
		}

		return $product;
	}
}
