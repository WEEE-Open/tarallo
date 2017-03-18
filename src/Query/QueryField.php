<?php
namespace WEEEOpen\Tarallo\Query;


interface QueryField {
	public function parse($parameter);
	public function validate();
	public function isDefault();
	public function getContent();
	public function __toString();
}