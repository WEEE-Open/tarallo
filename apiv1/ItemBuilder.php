<?php

namespace WEEEOpen\Tarallo\Server\v1;


use WEEEOpen\Tarallo\Server\Feature;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\ItemIncomplete;

class ItemBuilder {
	/**
	 * Build an Item, return it.
	 *
	 * @param array $input Decoded JSON from the client
	 * @param string|null $code Code for new item, if explicitly set
	 * @param ItemIncomplete|null $parent passed by reference, will contain the direct parent, if set.
	 *
	 * @return Item
	 */
	public static function ofArray(array $input, $code, &$parent) {
		$item = self::ofArrayInternal($input, $code);

		if(isset($input['parent'])) {
			try {
				$parent = new ItemIncomplete($input['parent']);
			} catch(\InvalidArgumentException $e) {
				throw new InvalidPayloadParameterException('parent', $input['parent'], 'Parent: ' . $e->getMessage());
			}
		} else {
			$parent = null;
		}

		return $item;
	}

	/**
	 * @see ofArray
	 *
	 * @param array $input Decoded JSON from the client
	 * @param string|null $code Code for new item, if explicitly set
	 * @param boolean $inner Used for recursion
	 * @TODO: handle brand, model, variant
	 *
	 * @return Item
	 */
	private static function ofArrayInternal(array $input, $code, $inner = false) {
		try {
			$item = new Item($code);
		} catch(\InvalidArgumentException $e) {
			throw new InvalidPayloadParameterException('*', $code, $e->getMessage());
		}

		if($inner && isset($input['parent'])) {
			throw new InvalidPayloadParameterException('parent', $input['parent'], 'Cannot set parent for internal items');
		}

		if(isset($input['features'])) {
			if(!is_array($input['features'])) {
				throw new InvalidPayloadParameterException('features', '',
					'Features must be an array, ' . gettype($input['features']) . ' given');
			}
			foreach($input['features'] as $name => $value) {
				try {
					$item->addFeature(new Feature($name, $value));
				} catch(\Throwable $e) {
					throw new InvalidPayloadParameterException(is_string($name) ? $name : '?', $value, 'Features: ' . $e->getMessage());
				}
			}
		}

		if(isset($input['contents'])) {
			if(!is_array($input['contents'])) {
				throw new InvalidPayloadParameterException('contents', '',
					'Contents must be an array, ' . gettype($input['contents']) . ' given');
			}
			foreach($input['contents'] as $other) {
				$item->addContent(self::ofArrayInternal($other, true));
			}
		}

		return $item;
	}
}
