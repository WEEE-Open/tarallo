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

	public const PERMISSION_LEVELS = [	self::AUTH_LEVEL_ADMIN => "Admin",
				self::AUTH_LEVEL_RW => "Read/Write",
				self::AUTH_LEVEL_RO => "Read Only"
				];

	public function getLevel(): int
	{
		if ($this->level === null) {
			throw new \LogicException('Level is null');
		}
		return $this->level;
	}

	public static function getPermissionLevel(int $level): string
	{
		return self::PERMISSION_LEVELS[$level] ?? "Unknown";
	}
}
