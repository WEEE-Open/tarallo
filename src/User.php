<?php

namespace WEEEOpen\Tarallo;

class User
{
	public $uid;
	public $cn;
	protected $level;
	public const AUTH_LEVEL_ADMIN = 0;
	public const AUTH_LEVEL_RW = 2;
	public const AUTH_LEVEL_RO = 3;

	public function getLevel(): int
	{
		if ($this->level === null) {
			throw new \LogicException('Level is null');
		}
		return $this->level;
	}
}
