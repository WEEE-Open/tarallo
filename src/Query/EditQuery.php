<?php

namespace WEEEOpen\Tarallo\Query;


use PHPUnit\DbUnit\Operation\Exception;
use WEEEOpen\Tarallo\Database\Database;
use WEEEOpen\Tarallo\InvalidParameterException;
use WEEEOpen\Tarallo\Item;
use WEEEOpen\Tarallo\ItemDefault;
use WEEEOpen\Tarallo\ItemIncomplete;
use WEEEOpen\Tarallo\ItemUpdate;
use WEEEOpen\Tarallo\User;

class EditQuery extends PostJSONQuery implements \JsonSerializable {
	private $newItems = [];
	private $newItemsNoParent = [];
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
        	switch($op) {
		        case 'create':
			        if(!is_array($itemsArray)) {
				        throw new InvalidParameterException('"create" parameters should be objects or null, ' . gettype($op) . ' given');
			        }
		        	foreach($itemsArray as $itemCode => $itemPieces) {
		        		$pair = $this->buildItem($itemCode, $itemPieces);
		        		// null is cast to empty string: whatever,
		        		$this->newItems[$pair[0]][] = $pair[1]; // parent => item
			        }
		        	break;
		        case 'update':
			        if(!is_array($itemsArray)) {
				        throw new InvalidParameterException('"update" parameters should be objects or null, ' . gettype($op) . ' given');
			        }
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

    public function run($user, Database $db) {
        if(!($user instanceof User)) {
	        throw new InvalidParameterException('Not logged in');
        }
        $db->modificationDAO()->modifcationBegin($user);
        try {
	        $db->itemDAO()->addItems($this->newItemsNoParent, null);
        	foreach($this->newItems as $parent => $items) {
		        $db->itemDAO()->addItems($items, new ItemIncomplete($parent));
	        }

	        // TODO: update items

	        foreach($this->deleteItems as $item) {
		        $db->treeDAO()->removeFromTree($item);
	        }
        } catch(Exception $e) {
        	$db->modificationDAO()->modificationRollback();
        	throw $e;
        }
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
        if(!empty($this->newItemsNoParent)) {
        	foreach($this->newItemsNoParent as $item) {
		        $result['create'][ $item->getCode() ] = $item->jsonSerialize();
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
							if($parent !== null) {
								try {
									$parent = ItemIncomplete::sanitizeCode($parent);
								} catch(InvalidParameterException $e) {
									throw new InvalidParameterException('Invalid "parent": ' . $e->getMessage());
								}
							}
						} else {
							throw new InvalidParameterException('Inner items cannot set their parent item explicitly (parent = ' . $parent . ')');
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