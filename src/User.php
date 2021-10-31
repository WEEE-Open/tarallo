<?php

namespace WEEEOpen\Tarallo;

class User
{
	public $uid;
	public $cn;
	protected $level;
	const AUTH_LEVEL_ADMIN = 0;
	const AUTH_LEVEL_RW = 2;
	const AUTH_LEVEL_RO = 3;

	public function getLevel(): int
	{
		if ($this->level === null) {
			throw new \LogicException('Level is null');
		}
		return $this->level;
	}
}
