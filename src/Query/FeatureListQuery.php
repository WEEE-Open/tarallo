<?php

namespace WEEEOpen\Tarallo\Query;


use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\InvalidParameterException;

class FeatureListQuery extends AbstractQuery {
	public final function __construct($string) {
	}

	public function run($user, Database $db) {
		if($user === null) {
			throw new InvalidParameterException('Not logged in');
		}

		return $db->featureDAO()->getFeatureList();
	}
}