<?php

namespace WEEEOpen\Tarallo;

use mysql_xdevapi\Exception;

class Search implements \JsonSerializable
{
	public $searchCode = null;
	public $searchFeatures = [];
	public $searchAncestors = [];
	public $searchLocations = [];
	public $sorts = [];

	private $code = null;
	private $owner = null;
	public $results = 0;

	/**
	 * @param string|null $code Filter by code (% and _ are allowed, % is appended at the end anyway)
	 * @param SearchTriplet[]|null $features Search by feature values in ancestor items
	 * @param SearchTriplet[]|null $ancestors Search by ancestor features
	 * @param ItemCode[]|null $locations Only descendants of these items will be searched
	 * @param string[]|null $sorts Map (associative array) from feature name to order (+ or -)
	 */
	public function __construct(
		string $code = null,
		array $features = [],
		array $ancestors = [],
		array $locations = [],
		array $sorts = []
	) {
		$this->code = $code;

		foreach ($features as $f) {
			$this->addFeature($f);
		}

		foreach ($ancestors as $a) {
			$this->addAncestor($a);
		}

		foreach ($locations as $l) {
			$this->addLocation($l);
		}

		foreach ($sorts as $s => $dir) {
			$this->addSort($s, $dir);
		}
	}

	/**
	 * @param array $feature
	 */
	private function addFeature(array $feature)
	{
		try {
			if ($feature[2] === null) {
				$valueOfTheCorrectType = null;
			} else {
				// Create a Feature to convert strings to int/double. Then discard it and recreate it in SearchTriplet.
				// It's a waste but happens with very few features each time, so it's not a major problem.
				$f = Feature::ofString($feature[0], trim($feature[2]));
				$valueOfTheCorrectType = $f->value;
			}

			$this->searchFeatures[] = new SearchTriplet($feature[0], $feature[1], $valueOfTheCorrectType);
		} catch (\TypeError $e) {
			throw new SearchException("Error parsing feature $feature", 0, $e);
		}
	}

	/**
	 * @param array $ancestor
	 */
	private function addAncestor(array $ancestor)
	{
		try {
			if ($ancestor[2] === null) {
				$valueOfTheCorrectType = null;
			} else {
				// Create a Feature to convert strings to int/double. Then discard it and recreate it in SearchTriplet.
				// It's a waste but happens with very few features each time, so it's not a major problem.
				$f = Feature::ofString($ancestor[0], trim($ancestor[2]));
				$valueOfTheCorrectType = $f->value;
			}

			$this->searchAncestors[] = new SearchTriplet($ancestor[0], $ancestor[1], $valueOfTheCorrectType);
		} catch (\TypeError $e) {
			throw new SearchException("Error parsing feature $ancestor", 0, $e);
		}
	}

	/**
	 * @param string $location
	 */
	private function addLocation(string $location)
	{
		try {
			$location = new ItemCode($location);
		} catch (ValidationException $e) {
			throw new SearchException("Invalid location $location", 0, $e);
		}

		$this->searchLocations[] = $location;
	}

	/**
	 * @param string $sort
	 */
	private function addSort(string $sort, string $dir)
	{
		if ($this->sorts) {
			throw new SearchException("Sorting by more than one field is currently unsupported");
		}

		$this->sorts[$sort] = $dir;
	}

	/**
	 * @return string|null
	 */
	public function getCode(): ?string
	{
		return $this->code;
	}

	public function setCode(string $code)
	{
		$this->code = $code;
	}

	public function getOwner(): ?string
	{
		return $this->owner;
	}

	public function setOwner(string $user)
	{
		$this->owner = $user;
	}

	/**
	 * If this search should only be applied as a refinement to another search since it contains only a sorting thing
	 *
	 * @return bool
	 */
	public function isSortOnly(): bool
	{
		return $this->sorts && empty(array_filter([$this->searchCode, $this->searchFeatures, $this->searchAncestors, $this->searchLocations]));
	}

	public function applyDiff(SearchDiff $diff): Search
	{
		$new = clone $this;
		foreach ($diff as $op) {
			switch ($op["type"]) {
				case "code":
					if ($op["key"]) {
						$new->searchCode = null;
					}
					if ($op["value"]) {
						if ($new->searchCode) {
							throw new SearchException("Can't add more than one condition on Code");
						}
						$new->searchCode = $op["value"];
					}
					break;
				case "features":
					if ($op["key"]) {
						unset($this->searchFeatures[$op["key"]]);
					}
					if ($op["value"]) {
						$this->addFeature($op["value"]);
					}
					break;
				case "ancestor":
					if ($op["key"]) {
						unset($this->searchAncestors[$op["key"]]);
					}
					if ($op["value"]) {
						$this->addAncestor($op["value"]);
					}
					break;
				case "locations":
					if ($op["key"]) {
						unset($this->searchLocations[$op["key"]]);
					}
					if ($op["value"]) {
						$this->addLocation($op["value"]);
					}
					break;
				case "sorts":
					if ($op["key"]) {
						unset($this->sorts[$op["key"]]);
					}
					if ($op["value"]) {
						$this->addSort($op["key"], $op["value"]);
					}
					break;
			}
		}

		return $new;
	}

	public function jsonSerialize()
	{
		return array_filter(["code" => $this->code, "features" => $this->searchFeatures, "ancestor" => $this->searchAncestors, "sort" => $this->sorts]);
	}

	/**
	 * @param array $array Decoded JSON
	 *
	 * @return static
	 */
	public static function fromJson(array $array): self
	{
		return new Search($array['code'] ?? null, $array['features'] ?? [], $array['ancestor'] ?? [], $array['locations'] ?? [], $array['sort'] ?? []);
	}
}
