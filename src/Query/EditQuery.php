<?php

namespace WEEEOpen\Tarallo\Query;


use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\InvalidParameterException;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\ItemDefault;
use WEEEOpen\Tarallo\ItemIncomplete;

class EditQuery extends PostJSONQuery implements \JsonSerializable {
	private $newItems = [];
	private $updateItems = [];
	private $deleteItems = [];

    protected function parseContent($array) {
        foreach($array as $op => $itemsArray) {
        	if(!is_string($op)) {
		        throw new InvalidParameterException('Action identifiers should be strings, ' . gettype($op) . ' given');
	        }
	        if($itemsArray === null) {
        		continue;
	        }
	        if(!is_array($itemsArray)) {
		        throw new InvalidParameterException('Action parameters should be objects or null, ' . gettype($op) . ' given');
	        }
        	switch($op) {
		        case 'create':
		        	foreach($itemsArray as $itemCode => $itemPieces) {
		        		$pair = $this->buildItem($itemCode, $itemPieces);
		        		$this->newItems[$pair[0]][] = $pair[1]; // parent => item
			        }
		        	break;
		        case 'update':
		        	break;
		        default:
		        	throw new InvalidParameterException('Unknown action ' . $op);
	        }
        }
    }

    public function run($user, Database $db)
    {
        // TODO: Implement run() method.
    }

    function jsonSerialize()
    {
        // TODO: Implement jsonSerialize() method.
    }

	private function buildItem($itemCode, $itemPieces, $outer = true) {
    	$parent = null;
		if($this->isDefaultItem($itemPieces)) {
			if(isset($itemPieces['default'])) {
				throw new InvalidParameterException('Default items cannot point to other default items ("is_default": true and "default": "' . $itemPieces['default'] . '")');
			}
			$item = new ItemDefault($itemCode);
		} else {
			if(isset($itemPieces['default'])) {
				$item = new Item($itemCode, $itemPieces['default']);
			} else {
				$item = new Item($itemCode);
			}
		}
		if($this->isArrayIgnoreNull($itemCode, $itemPieces)) {
			foreach($itemPieces as $key => $value) {
				switch($key) {
					case 'is_default':
					case 'default':
						continue;
						break; // purely for decoration
					case 'parent':
						if($outer) {
							if(is_string($parent) || is_int($parent)) {
								$parent = $value;
							} else {
								throw new InvalidParameterException('"parent" must be a string or integer, ' . gettype($parent) . ' given');
							}
						} else {
							throw new InvalidParameterException('Inner items cannot set their parent item explicitly');
						}
						break;
					case 'features':
						if($this->isArrayIgnoreNull($key, $value)) {
							foreach($value as $featureName => $featureValue) {
								$item->addFeature($featureName, $featureValue);
							}
						}
						break;
					case 'features_default':
						throw new InvalidParameterException('Cannot set default features explicitly, they are read-only');
					case 'content':
						if($this->isArrayIgnoreNull($key, $value)) {
							if($item instanceof ItemDefault) {
								throw new InvalidParameterException('Default items cannot contain other items, found ' . $key . ' inside ' . $item);
							}
							foreach($value as $featureName => $featureValue) {
								// yay recursion!
								$pair = $this->buildItem($featureName, $featureValue, false);
								$item->addChild($pair[1]);
							}
						}
						break;
					default:
						throw new InvalidParameterException('Unknown field ' . $key);
						break;
				}
			}
		}
		return [$parent, $item];
	}

	private static function isDefaultItem($pieces) {
    	if(!is_array($pieces)) {
    		return false;
	    }
		if(isset($pieces['is_default']) && $pieces['is_default'] === true) {
			return true;
		}
		return false;
	}

	private static function isArrayIgnoreNull($key, $value) {
		if($value === null) {
			return false;
		}
		if(is_array($value)) {
			return true;
		}
		throw new InvalidParameterException('"' . $key . '" should be an object or null, ' . gettype($value) . ' given');
	}
}