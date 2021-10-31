<?php

namespace WEEEOpen\Tarallo;

class SessionLocal
{

	public $description;
	public $level;
	public $owner;

	const lengthBefore = 16;
	const lengthAfter = 32;
	public static function generateToken(): string
	{
		try {
			$before = rtrim(strtr(base64_encode(random_bytes(self::lengthBefore)), '+/', '-_'), '=');
			$after = rtrim(strtr(base64_encode(random_bytes(self::lengthAfter)), '+/', '-_'), '=');
		} catch (\Exception $e) {
			throw new EntropyException($e->getMessage(), 0, $e);
		}
		return $before . ':' . $after;
	}
}
