<?php

namespace WEEEOpen\Tarallo\APIv1;

use WEEEOpen\Tarallo\Server\HTTP\InvalidPayloadParameterException;
use WEEEOpen\Tarallo\Server\Item;
use WEEEOpen\Tarallo\Server\ItemIncomplete;
use WEEEOpen\Tarallo\Server\Search;
use WEEEOpen\Tarallo\Server\SearchTriplet;

class SearchBuilder {
	/**
	 * Build an Search, return it.
	 *
	 * @param array $input Decoded JSON from the client
	 *
	 * @TODO: support for multiple locations? The rest of the server supports it, in theory...
	 *
	 * @return Search
	 */
	public static function ofArray(array $input) {
		$code = $input['code'] ?? null;

		if(isset($input['locations'])) {
			$locations = [];
			try {
				foreach($input['locations'] as $location) {
					$locations[] = new ItemIncomplete($location);
				}
				unset($location);
			} catch(\InvalidArgumentException $e) {
				throw new InvalidPayloadParameterException('locations', $location, $e->getMessage());
			}
		} else {
			$locations = null;
		}

		if(isset($input['features'])) {
			try {
				$features = self::getFeatures($input['features'], 'features');
			} catch(\TypeError $e) {
				throw new InvalidPayloadParameterException('features', $input['features'], $e->getMessage());
			}
		} else {
			$features = null;
		}

		if(isset($input['ancestor'])) {
			try {
				$ancestor = self::getFeatures($input['ancestor'], 'ancestor');
			} catch(\TypeError $e) {
				throw new InvalidPayloadParameterException('ancestor', $input['ancestor'], $e->getMessage());
			}
		} else {
			$ancestor = null;
		}

		if(isset($input['sort'])) {
			if(!is_array($input['sort'])) {
				throw new InvalidPayloadParameterException('sort', $input['sort'], '"sort" must be an array');
			}
			$sort = $input['sort'];
		} else {
			$sort = null;
		}


		return new Search($code, $features, $ancestor, $locations, $sort);
	}

	private static function getFeatures(array $stuff, string $field): array {
		$result = [];
		foreach($stuff as $triplet) {
			if(!is_array($triplet)) {
				throw new InvalidPayloadParameterException($field, (string) $triplet, "Elements of $field should be arrays, not " . gettype($triplet));
			}
			if(count($triplet) != 3) {
				throw new InvalidPayloadParameterException($field, count($triplet), "Triplet should contain 3 elements, not " . count($triplet));
			}
			$result[] = new SearchTriplet($triplet[0], $triplet[1], $triplet[2]);
		}
		return $result;
	}

	/**
	 * @see ofArray
	 *
	 * @param array $input Decoded JSON from the client
	 * @param string|null $code Code for new item, if explicitly set
	 * @param boolean $inner Used for recursion
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
			throw new InvalidPayloadParameterException('parent', $input['parent'],
				'Cannot set parent for internal items');
		}

		if(isset($input['code'])) {
			if($inner) {
				$item->setCode($input['code']);
			} else {
				throw new InvalidPayloadParameterException('code', $input['code'],
					'Cannot set code for head/root item this way, use the URI');
			}
		}

		if(isset($input['features'])) {
			try {
				self::addFeatures($input['features'], $item);
			} /** @noinspection PhpUndefinedClassInspection */ catch(\TypeError $e) {
				throw new InvalidPayloadParameterException('features', '',
					'Features must be an array, ' . gettype($input['features']) . ' given');
			}
		}

		if(isset($input['contents'])) {
			if(!is_array($input['contents'])) {
				throw new InvalidPayloadParameterException('contents', '',
					'Contents must be an array, ' . gettype($input['contents']) . ' given');
			}
			foreach($input['contents'] as $other) {
				$item->addContent(self::ofArrayInternal($other, null, true));
			}
		}

		return $item;
	}
}
