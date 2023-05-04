<?php

namespace WEEEOpen\Tarallo;

use WEEEOpen\Tarallo\HTTP\InvalidParameterException;

/**
 * Handle search diffs
 * Op: {"key": int|null, "value": <TypeValue>|null}
 * Diff: {"code": [Op], "feature": [Op], "c_feature": [Op], "location": [Op], "sort": [Op]}
 */
class SearchDiff
{
	public $added = [];
	public $updated = [];
	public $deleted = [];
	private $sortOnly = true;

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
