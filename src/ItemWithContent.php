<?php


namespace WEEEOpen\Tarallo\Server;


interface ItemWithContent extends ItemWithFeatures {
	// Returns $this, BTW
	public function addContent(ItemWithCode $item);
	public function removeContent(ItemWithCode $item);
	public function getContent(): array;
}