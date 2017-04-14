<?php

namespace WEEEOpen\Tarallo\Query;


use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\InvalidParameterException;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\ItemDefault;
use WEEEOpen\Tarallo\ItemIncomplete;
use WEEEOpen\Tarallo\ItemUpdate;

class EditQuery extends PostJSONQuery implements \JsonSerializable {
	private $newItems = [];
	private $updateItems = [];
	private $deleteItems = [];
	private $notes = null;

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
		        		// TODO: parent = null gets cast to empty string, do something! (store "path" into Item, implement getParent and getPath?)
		        		$this->newItems[$pair[0]][] = $pair[1]; // parent => item
			        }
		        	break;
		        case 'update':
			        foreach($itemsArray as $itemCode => $itemPieces) {
			        	if($itemPieces === null) {
			        		$this->deleteItems[] = $itemCode;
				        } else if(is_array($itemPieces)) {
					        $this->updateItems[$itemCode] = $this->buildItemUpdate($itemCode, $itemPieces);
				        } else {
					        throw new InvalidParameterException($itemCode . ' should be null or object/array, ' . gettype($itemPieces) . ' given');
				        }
			        }
		        	break;
		        case 'notes':
		        	if($itemsArray !== null) {
				        $this->notes = (string) $itemsArray;
			        }
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

    function jsonSerialize() {
        $result = [];
        if(!empty($this->newItems)) {
        	foreach($this->newItems as $parent => $items) {
        		foreach($items as $item) {
        			/** @var Item $item */
			        $serialItemzed = $item->jsonSerialize();
			        if($parent !== null && $parent !== '') {
				        $serialItemzed['parent'] = $parent;
			        }
			        $result['create'][$item->getCode()] = $serialItemzed;
		        }
	        }
        }
	    if(!empty($this->updateItems)) {
		    $result['update'] = $this->updateItems;
	    }
	    if(!empty($this->deleteItems)) {
        	foreach($this->deleteItems as $item) {
		        $result['update'][$item] = null;
	        }
	    }
	    if($this->notes !== null) {
        	$result['notes'] = $this->notes;
	    }
	    return $result;
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
								$item->addContent($pair[1]);
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

	private function buildItemUpdate($itemCode, $itemPieces) {
		$item = new ItemUpdate($itemCode);
		foreach($itemPieces as $key => $value) {
			switch($key) {
				case 'is_default':
					$item->setIsDefault($value);
					break;
				case 'default':
					$item->setDefaultCode($value);
					break;
				case 'parent':
					$item->setParent(new ItemIncomplete($value));
					break;
				case 'features':
					if($this->isArrayIgnoreNull($key, $value)) {
						foreach($value as $featureName => $featureValue) {
							$item->addFeature($featureName, $featureValue);
						}
					} else {
						$item->setFeaturesChanged();
					}
					break;
				case 'features_default':
					throw new InvalidParameterException('Cannot set default features explicitly, they are read-only');
				case 'content':
					throw new InvalidParameterException('No nested Items allowed in "update", use "parent" to move items');
					break;
				default:
					throw new InvalidParameterException('Unknown field ' . $key);
					break;
			}
		}
		return $item;
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