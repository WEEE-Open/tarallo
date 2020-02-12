<?php

namespace WEEEOpen\Tarallo\APIv2;

use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\FeatureValidationException;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\ItemCode;
use WEEEOpen\Tarallo\ItemWithFeatures;
use WEEEOpen\Tarallo\ValidationException;

class ItemBuilder {
	/**
	 * Build an Item, return it.
	 *
	 * @param array $input Decoded JSON from the client
	 * @param string|null $code Code for new item, if explicitly set
	 * @param ItemCode|null $parent passed by reference, will contain the direct parent, if set.
	 *
	 * @return Item
	 */
	public static function ofArray(array $input, ?string $code, &$parent) {
		$item = self::ofArrayInternal($input, $code, [0]);

		if(isset($input['parent'])) {
			try {
				$parent = new ItemCode($input['parent']);
			} catch(ValidationException $e) {
				$e->setItemPath([]);
				throw $e;
			}
		} else {
			$parent = null;
		}

		return $item;
	}

	/**
	 * @param array $input Decoded JSON from the client
	 * @param string|null $code Code for new item, if supplied outside of item
	 * @param int[] $path
	 * @param boolean $inner Used for recursion
	 *
	 * @return Item
	 * @see ofArray
	 */
	private static function ofArrayInternal(array $input, ?string $code, array $path, $inner = false) {
		try {
			$item = new Item($code);
		} catch(ValidationException $e) {
			$e->setItemPath($path);
			throw $e;
		}

		if(isset($input['code'])) {
			if($inner) {
				try {
					$item->setCode($input['code']);
				} catch(ValidationException $e) {
					$e->setItemPath($path);
					throw $e;
				}
				$code = $item->getCode();
			} else {
				throw new ValidationException($code, $path, 'Cannot set code for head/root item this way, use the URI');
			}
		}

		if($inner && isset($input['parent'])) {
			throw new ValidationException($code, $path, 'Cannot set parent for internal items');
		}

		if(isset($input['features'])) {
			if(!is_array($input['features'])) {
				throw new ValidationException($code, $path, 'Features must be an array, ' . gettype($input['features']) . ' given');
			}
			try {
				self::addFeatures($input['features'], $item);
			} catch(FeatureValidationException $e) {
				$e->setItem($code);
				$e->setItemPath($path);
				throw $e;
			}
		}

		if(isset($input['contents'])) {
			if(!is_array($input['features'])) {
				throw new ValidationException($code, $path, 'Contents must be an array, ' . gettype($input['features']) .	' given');
			}
			$id = 0;
			foreach($input['contents'] as $other) {
				$item->addContent(self::ofArrayInternal($other, null, array_merge($path, [$id]), true));
				$id++;
			}
		}

		return $item;
	}

	/**
	 * Process features to be added
	 *
	 * @param string[] $features The usual key-value pair for features
	 * @param ItemWithFeatures $item Where to add those features
	 *
	 * @see addFeaturesDelta
	 */
	public static function addFeatures(array $features, ItemWithFeatures $item) {
		foreach($features as $name => $value) {
			try {
				$item->addFeature(Feature::ofString($name, trim($value)));
			} catch(\Throwable $e) {
				throw new FeatureValidationException($name, $value, null, null, $e->getMessage());
			}
		}
	}

	/**
	 * Processes feature to be added AND removed
	 *
	 * @param string[] $features The usual key-value pair for features
	 * @param ItemWithFeatures $item Where to add those features
	 *
	 * @return string[] Features to be removed
	 *
	 * @see addFeatures
	 */
	public static function addFeaturesDelta(array $features, ItemWithFeatures $item) {
		$delete = [];
		$add = [];

		foreach($features as $name => $value) {
			if($value === null) {
				$delete[] = $name;
			} else {
				$add[$name] = $value;
			}
		}

		self::addFeatures($add, $item);

		return $delete;
	}
}
