<?php

namespace WEEEOpen\Tarallo\Server;


class Search {
	private $code = null;
	public $results = 0;
	public $searchCode;  
	public $searchFeatures;  
	public $searchAncestors;  
	public $searchLocations;  
	public $sort;

	/**
	 * @param string|null $code Filter by code (% and _ are allowed, % is appended at the end anyway)
	 * @param SearchTriplet[]|null $features Search by feature values in ancestor items
	 * @param SearchTriplet[]|null $ancestors Search by ancestor features
	 * @param ItemIncomplete[]|null $locations Only descendants of these items will be searched
	 * @param string[]|null $sorts Map (associative array) from feature name to order (+ or -)
	 */
	public function __construct($code = null, array $features = null, array $ancestors = null, array $locations = null, array $sorts = null) {
		$this->filter($code, $features, $ancestors, $locations);
		$this->sort($sorts);
	}

	public function reFilter(Search $other) {
		// TODO: merge other search. So the Adapter can create a new Search when new filtering parameters are received, apply it via DAO, then merge here. If that's needed.
	}

	public function reSort(array $sorts) {
		// Replaces previous sorts
		$this->sort($sorts);
	}


	private function filter($code = null, array $features = null, array $ancestors = null, array $locations = null) {
		$this->validateGlobally($code, $features, $ancestors, $locations);
		$this->searchCode = $code;
		$this->searchFeatures = $features;
		$this->searchAncestors = $ancestors;
		$this->searchLocations = $locations;
	}

	/**
	 * Set search code
	 *
	 * @param int $code
	 */
	public function setCode($code) {
		if($this->code === null) {
			if(!is_int($code)) {
				throw new \InvalidArgumentException('Search code must be an integer');
			}
			$this->code = $code;
		} else {
			throw new \LogicException('Cannot set search code twice');
		}
	}

	/**
	 * @return string|null
	 */
	public function getCode() {
		return $this->code;
	}

	/**
	 * @param string[]|null $sorts Map (associative array) from feature name to order (+ or -)
	 */
	private function sort(array $sorts = null) {
		$this->sort = $sorts;
	}

	/**
	 * Validate that there's something to search, so the search in its entirety makes sense
	 *
	 * @see filter
	 *
	 * @param string|null $code
	 * @param SearchTriplet[]|null $features
	 * @param SearchTriplet[]|null $ancestors
	 * @param ItemIncomplete[]|null $locations
	 */
	private static function validateGlobally($code, array $features = null, array $ancestors = null, array $locations = null) {
		$searchSomething = false;

		if($code !== null) {
			if(!is_string($code)) {
				throw new \InvalidArgumentException('Code filter should be a string or null, ' . gettype($code) . ' given');
			}
			$searchSomething = true;
		}

		if($features !== null) {
			$searchSomething = true;
		}

		if($ancestors !== null) {
			$searchSomething = true;
		}

		if($locations !== null) {
			$searchSomething = true;
		}

		if(!$searchSomething) {
			throw new \InvalidArgumentException('Nothing to search');
		}
	}
}
