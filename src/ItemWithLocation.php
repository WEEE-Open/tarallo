<?php


namespace WEEEOpen\Tarallo;


interface ItemWithLocation {
	public function addAncestors(array $ancestors);
	public function getPath(): array;
	public function getParent(): ?ItemWithCode;
}
