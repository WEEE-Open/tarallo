<?php


namespace WEEEOpen\Tarallo;


interface ItemWithContent {
	public function addContent(ItemWithCode $item);
	public function removeContent(ItemWithCode $item);
	public function getContent(): array;
	public function getFlatContent(): array;
}