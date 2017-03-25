<?php
namespace WEEEOpen\Tarallo\Query\Field;


interface QueryField {
	public function __construct($parameter);
	public function add($parameter);
	public function getContent();
	public function __toString();
}