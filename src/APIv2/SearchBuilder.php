<?php

namespace WEEEOpen\Tarallo\APIv2;

use WEEEOpen\Tarallo\Feature;
use WEEEOpen\Tarallo\HTTP\InvalidParameterException;
use WEEEOpen\Tarallo\ItemCode;
use WEEEOpen\Tarallo\Search;
use WEEEOpen\Tarallo\SearchException;
use WEEEOpen\Tarallo\SearchTriplet;
use WEEEOpen\Tarallo\ValidationException;

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
					$locations[] = new ItemCode($location);
				}
				unset($location);
			} catch(ValidationException $e) {
				throw new InvalidParameterException('locations', $location, $e->getMessage(), 0, $e);
			}
		} else {
			$locations = null;
		}

		if(isset($input['features'])) {
			try {
				$features = self::getFeatures($input['features'], 'features');
			} catch(\TypeError $e) {
				throw new InvalidParameterException('features', $input['features'], $e->getMessage(), 0, $e);
			}
		} else {
			$features = null;
		}

		if(isset($input['ancestor'])) {
			try {
				$ancestor = self::getFeatures($input['ancestor'], 'ancestor');
			} catch(\TypeError $e) {
				throw new InvalidParameterException('ancestor', $input['ancestor'], $e->getMessage(), 0, $e);
			}
		} else {
			$ancestor = null;
		}

		if(isset($input['sort'])) {
			if(!is_array($input['sort'])) {
				throw new InvalidParameterException('sort', $input['sort'], '"sort" must be an array');
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
				throw new SearchException(null, null, "Elements of $field should be arrays, not " . gettype($triplet));
			}
			if(count($triplet) != 3) {
				throw new SearchException(null, null, "Triplet should contain 3 elements, not " . count($triplet));
			}

			// Create a Feature to convert strings to int/double. Then discard it and recreate it in SearchTriplet.
			// It's a waste but happens with very few features each time, so it's not a major problem.
			$feature = Feature::ofString($triplet[0], $triplet[2]);
			$result[] = new SearchTriplet($triplet[0], $triplet[1], $feature->value);
		}
		return $result;
	}
}
