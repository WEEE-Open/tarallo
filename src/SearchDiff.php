<?php

namespace WEEEOpen\Tarallo;

/**
 * Handle search diffs
 * @internal
 * TypeValue:
 *  code -> string
 *  feature -> SearchTriplet
 *  c_feature -> SearchTriplet
 *  location -> string
 *  sort -> ["feature" => <FeatureName>, "direction" => '+'|'-'
 *
 * Op:
 *  ["key" => ?int, "value" => ?<TypeValue>]
 *
 * $diff:
 * [
 *  ?"code" => [Op]
 *  ?"feature" => [Op]
 *  ?"c_feature" => [Op]
 *  ?"location" => [Op]
 *  ?"sort" => [Op]
 * ]
 */
class SearchDiff
{
	/**
	 * @var array new elements of form ["type" => <Type>, "value" => <value>]
	 */
	public $added = [];
	/**
	 * @var array updated elements of form ["type" => <Type>, "key" => int, "value" => <value>]
	 */
	public $updated = [];
	/**
	 * @var array deleted elements of form ["type" => <Type>, "key" => int]
	 */
	public $deleted = [];
	private $sortOnly = true;


	/**
	 * @param array $diff
	 * @see SearchDiff for description of $diff
	 */
	public function __construct(array $diff)
	{
		foreach (Search::FIELDS as $type) {
			if (!array_key_exists($type, $diff)) {
				$diff[$type] = [];
			}
		}

		foreach ($diff as $type => $ops) {
			foreach ($ops as $op) {
				if ($type !== "sort") {
					$this->sortOnly = false;
				}

				if ($op["key"] === null) {
					$this->added[] = ["type" => $type, "value" => $op["value"]];
				} elseif ($op["value"] === null) {
					$this->deleted[] = ["type" => $type, "key" => $op["key"]];
				} else {
					$this->updated[] = ["type" => $type, "key" => $op["key"], "value" => $op["value"]];
				}
			}
		}
	}

	public function isSortOnly(): bool
	{
		return $this->sortOnly;
	}

	public function isNewOnly(): bool
	{
		return empty($this->updated) && empty($this->deleted);
	}
}
