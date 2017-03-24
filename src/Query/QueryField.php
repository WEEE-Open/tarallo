<?php
namespace WEEEOpen\Tarallo\Query;


interface QueryField {
	public function __construct($parameter);
	public function add($parameter);
	public function isDefault();
	public function getContent();
	public function __toString();
}