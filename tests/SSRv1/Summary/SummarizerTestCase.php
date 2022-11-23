<?php

namespace WEEEOpen\TaralloTest\SSRv1\Summary;

use PHPUnit\Framework\TestCase;

class SummarizerTestCase extends TestCase
{
	final public function assertArrayEquals(array $expected, array $actual): void
	{
		$this->assertTrue(array_values($expected) === array_values($actual), print_r($expected, true) . "!=\n" . print_r($actual, true) . "diff=\n" . print_r(array_diff($expected, $actual), true));
	}
}
