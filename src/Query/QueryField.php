<?php
namespace WEEEOpen\Tarallo\Query;


interface QueryField {
	public function allowMultipleFields();
	public function isKVP();
	public function parse($parameter);
	public function isParsed();
	public function validate();
	public function getContent();
}