<?php

namespace WEEEOpen\Tarallo;

use WEEEOpen\Tarallo\HTTP\InvalidParameterException;

/**
 * Handle search diffs of form
 * [["type" => "code|locs|feats|c_feats|sorts", "key" => int|null, "value" => <>|null ],...]
 */
class SearchDiff
{
	public $added = [];
	public $updated = [];
	private $sortOnly = true;

	public function __construct(array $diff)
	{
		foreach ($diff as $e) {
			if ($e["type"] !== "sorts") {
				$this->sortOnly = false;
			}

			if ($e["key"] === null) {
				$this->added[] = $e;
			} else {
				$this->updated[] = $e;
			}
		}
	}

	public function isSortOnly(): bool
	{
		return $this->sortOnly;
	}

	public function isNewOnly(): bool
	{
		return !$this->updated;
	}
}
