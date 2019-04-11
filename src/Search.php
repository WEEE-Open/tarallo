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
	private $sortOnly = false;

	/**
	 * @param string|null $code Filter by code (% and _ are allowed, % is appended at the end anyway)
	 * @param SearchTriplet[]|null $features Search by feature values in ancestor items
	 * @param SearchTriplet[]|null $ancestors Search by ancestor features
	 * @param ItemIncomplete[]|null $locations Only descendants of these items will be searched
	 * @param string[]|null $sorts Map (associative array) from feature name to order (+ or -)
	 */
	public function __construct(
		string $code = null,
		array $features = null,
		array $ancestors = null,
		array $locations = null,
		array $sorts = null
	) {
		$this->filter($code, $features, $ancestors, $locations);
		$this->sort($sorts);
		$this->validate();
	}

	private function filter(
		string $code = null,
		array $features = null,
		array $ancestors = null,
		array $locations = null
	) {
		$this->searchCode = $code;
		$this->searchFeatures = $features;
		$this->searchAncestors = $ancestors;
		$this->searchLocations = $locations;
	}

	/**
	 * Set search code
	 *
	 * @param int $code
	 *
	 * @deprecated
	 */
	public function setCode(int $code) {
		if($this->code === null) {
			$this->code = $code;
		} else {
			throw new \LogicException('Cannot set search code twice');
		}
	}

	/**
	 * @return string|null
	 * @deprecated
	 */
	public function getCode() {
		return $this->code;
	}

	/**
	 * @param string[]|null $sorts Map (associative array) from feature name to order (+ or -)
	 */
	private function sort(array $sorts = null) {
		if($sorts !== null) {
			if(count($sorts) > 1) {
				throw new \InvalidArgumentException('Sorting by more than one field is currently unsupported');
			} else if(count($sorts) === 0) {
				$sorts = null;
			}
		}
		$this->sort = $sorts;
	}

	/**
	 * If this search should only be applied as a refinement to another search since it contains only a sorting thing
	 *
	 * @return bool
	 */
	public function isSortOnly(): bool {
		return $this->sortOnly;
	}

	/**
	 * Validate that there's something to search, so the search in its entirety makes sense
	 *
	 * @see filter
	 */
	private function validate() {
		$searchSomething = false;

		if($this->searchCode !== null) {
			$searchSomething = true;
		}

		if($this->searchFeatures !== null) {
			$searchSomething = true;
		}

		if($this->searchAncestors !== null) {
			$searchSomething = true;
		}

		if($this->searchLocations !== null) {
			$searchSomething = true;
		}

		if(!$searchSomething) {
			if($this->sort === null) {
				throw new \InvalidArgumentException('Nothing to search');
			} else {
				$this->sortOnly = true;
			}
		}
	}
}
