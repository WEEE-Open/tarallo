<?php

namespace WEEEOpen\Tarallo;

class SessionLocal
{

	public $description;
	public $level;
	public $owner;

	protected const LENGTH_BEFORE = 16;
	protected const LENGTH_AFTER = 32;
	public static function generateToken(): string
	{
		try {
			$before = rtrim(strtr(base64_encode(random_bytes(self::LENGTH_BEFORE)), '+/', '-_'), '=');
			$after = rtrim(strtr(base64_encode(random_bytes(self::LENGTH_AFTER)), '+/', '-_'), '=');
		} catch (\Exception $e) {
			throw new EntropyException($e->getMessage(), 0, $e);
		}
		return $before . ':' . $after;
	}
}
