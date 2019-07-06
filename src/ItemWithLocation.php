<?php


namespace WEEEOpen\Tarallo\Server;


interface ItemWithLocation {
	public function addAncestors(array $ancestors);
	public function getPath(): array;
	public function getParent(): ?ItemWithCode;
}