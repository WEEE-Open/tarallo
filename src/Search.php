<?php

namespace WEEEOpen\Tarallo;

use http\Exception\InvalidArgumentException;

class Search implements \JsonSerializable
{
	// Ideally these would be an enum
	public $filters = ["code" => [], "feature" => [], "c_feature" => [], "location" => [], "sort" => []];

	private $id = null;
	private $owner = null;

	// TODO: Implement multi-sorting
	// TODO: Fix tests

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
		if ($code) {
			$this->filters["code"][0] = $code;
		}

		foreach ($features as $f) {
			$this->filters["feature"][] = $this->createFilter("feature", $f);
		}

		foreach ($ancestors as $a) {
			$this->filters["c_feature"][] = $this->createFilter("c_feature", $a);
		}

		foreach ($locations as $l) {
			$this->filters["location"][] = $this->createFilter("location", $l);
		}

		foreach ($sorts as $s => $dir) {
			$this->filters["sort"][] = $this->createFilter("sort", $s);
		}
	}

	private function createFilter(string $type, $value)
	{
		switch ($type) {
			case "code":
				return $value;
			case "feature":
			case "c_feature":
				try {
					if ($value[2] === null) {
						$valueOfTheCorrectType = null;
					} else {
						// Create a Feature to convert strings to int/double. Then discard it and recreate it in SearchTriplet.
						// It's a waste but happens with very few features each time, so it's not a major problem.
						$f = Feature::ofString($value[0], trim($value[2]));
						$valueOfTheCorrectType = $f->value;
					}

					return new SearchTriplet($value[0], $value[1], $valueOfTheCorrectType);
				} catch (\TypeError $e) {
					throw new SearchException("Error parsing feature $value", 0, $e);
				}
			case "location":
				try {
					$location = new ItemCode($value);
				} catch (ValidationException $e) {
					throw new SearchException("Invalid location $value", 0, $e);
				}

				return $location;
			case "sort":
				return $value;
			default:
				throw new SearchException("Invalid filter type");
		}
	}

	public function getFiltersByType(string $type)
	{
		switch ($type) {
			case "code":
				return $this->filters["code"];
			case "feature":
				return $this->filters["feature"];
			case "c_feature":
				return $this->filters["c_feature"];
			case "location":
				return $this->filters["location"];
			case "sort":
				return $this->filters["sort"];
			default:
				throw new InvalidArgumentException("Unknown filter type $type");
		}
	}

	public function getOwner(): ?string
	{
		return $this->owner;
	}

	public function setOwner(string $user)
	{
		$this->owner = $user;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function setId(int $id)
	{
		$this->id = $id;
	}

	/**
	 * If this search should only be applied as a refinement to another search since it contains only a sorting thing
	 *
	 * @return bool
	 */
	public function isSortOnly(): bool
	{
		return !empty($this->filters["sort"]) && empty($this->filters["code"]) && empty($this->filters["feature"]) && empty($this->filters["c_feature"] && empty($this->filters["location"]));
	}

	public function applyDiff(SearchDiff $diff): Search
	{
		$new = clone $this;

		foreach ($diff->deleted as ["type" => $type, "key" => $key]) {
			unset($new->filters[$type][$key]);
		}

		error_log(json_encode($diff->updated));
		foreach ($diff->updated as ["type" => $type, "key" => $key, "value" => $value]) {
			$new->filters[$type][$key] = $new->createFilter($type, $value);
		}

		foreach ($diff->added as ["type" => $type, "value" => $value]) {
			$new->filters[$type][] = $new->createFilter($type, $value);
		}

		// Reindex filters array after a deletion
		if (count($diff->deleted) > 0) {
			foreach ($new->filters as &$f) {
				$f = array_values($f);
			}
		}

		return $new;
	}

	private function addKeys()
	{
		return array_map(function ($e) {
			$ret = [];
			$idx = 0;
			foreach ($e as $value) {
				$ret[] = ["key" => $idx++, "value" => $value];
			}
			return $ret;
		}, $this->filters);
	}

	public function jsonSerialize()
	{
		return $this->addKeys();
	}

	/**
	 * @param array $array Decoded JSON
	 *
	 * @return static
	 */
	public static function fromJson(array $array): self
	{
		return (new Search())->applyDiff(new SearchDiff($array));
	}
}
