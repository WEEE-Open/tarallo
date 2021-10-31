<?php

namespace WEEEOpen\Tarallo;

interface ItemWithCode
{
	public function getCode(): string;
	public function peekCode(): ?string;
	public function hasCode(): bool;
	public function compareCode(ItemWithCode $other): int;
}
