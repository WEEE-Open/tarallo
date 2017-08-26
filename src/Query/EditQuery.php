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
			        $i = 1;
		        	foreach($itemsArray as $itemPieces) {
		        		try {
		        			if($op === 'create') {
						        $pair = $this->buildItem($itemPieces);
					        } else {
						        $this->updateItems[$itemCode] = $this->buildItemUpdate($itemPieces);
					        }
				        } catch(\Exception $e) {
		        			throw new InvalidParameterException('Error reading item ' . $i . ' for "' . $op . '": ' . $e->getMessage());
				        }
		        		// null is cast to empty string: whatever,
		        		$this->newItems[$pair[0]][] = $pair[1]; // parent => item
				        $i++
			        }
		        	break;
		        case 'update':
			        if(!is_array($itemsArray)) {
				        throw new InvalidParameterException('"update" parameters should be objects or null, ' . gettype($op) . ' given');
			        }
			        foreach($itemsArray as $itemPieces) {
				        try {
					        $itemCode = self::codePeek( $itemPieces );
				        } catch(\InvalidArgumentException $e) {

				        }
			        	if(is_array($itemPieces)) {

				        } else {
					        throw new InvalidParameterException($itemCode . ' should be null or object/array, ' . gettype($itemPieces) . ' given');
				        }
			        }
		        	break;
		        case 'delete':
			        if(!is_array($itemsArray)) {
				        throw new InvalidParameterException('"delete" parameters should be objects or null, ' . gettype($op) . ' given');
			        }
			        foreach($itemsArray as $itemCode) {
				        $this->deleteItems[] = $itemCode;
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

	/**
	 * Find unique code in new/edited items and return it.
	 *
	 * @param array $array - array of random stuff belonging to an item (content, location, is_default, and so on)
	 * @return string
	 */
    private static function codePeek($array) {
    	if(isset($array['code'])) {
    		if(is_string($array['code']) || is_integer($array['code'])) {
    			return $array['code'];
		    } else {
			    throw new \InvalidArgumentException('Expected string or int, got ' . gettype($array['code']));
		    }
	    } else {
    		throw new \InvalidArgumentException('Missing "code" parameter');
	    }
    }

    public function run($user, Database $db) {
        if(!($user instanceof User)) {
	        throw new InvalidParameterException('Not logged in');
        }
        $db->modificationDAO()->modifcationBegin($user, $this->notes);
        try {
	        $db->itemDAO()->addItems($this->newItemsNoParent, null);
        	foreach($this->newItems as $parent => $items) {
		        $db->itemDAO()->addItems($items, new ItemIncomplete($parent));
	        }

	        if($this->updateItems !== null) {
		        $db->itemDAO()->updateItems($this->updateItems);
	        }

	        foreach($this->deleteItems as $item) {
		        $db->treeDAO()->removeFromTree($item);
	        }
        } catch(Exception $e) {
        	$db->modificationDAO()->modificationRollback();
        	throw $e;
        }

        // TODO: return newly inserted items?
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
			        $result['create'][] = $serialItemzed;
		        }
	        }
        }
        if(!empty($this->newItemsNoParent)) {
        	foreach($this->newItemsNoParent as $item) {
		        $result['create'][] = $item->jsonSerialize();
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

	private function buildItem($itemPieces, $outer = true) {
    	$parent = null;
		$itemCode = self::codePeek($itemPieces);
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
		if($this->isArrayIgnoreNull(null, $itemPieces)) {
			foreach($itemPieces as $key => $value) {
				switch($key) {
					case 'is_default':
					case 'default':
						continue;
						break; // purely for decoration
					case 'code':
						if($item->getCode() === (string) $value) {
							continue;
						} else {
							throw new \LogicException('Code "' . $item->getCode() . '"" changed to "' . $value . '"');
						}
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
							foreach($value as $featureValue) {
								// yay recursion!
								$pair = $this->buildItem($featureValue, false);
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

	/**
	 * Is it an array? True
	 * Is it null? False
	 * Is it anything else? Throw an exception
	 *
	 * @param $key mixed|null some identifier, used only for the exception
	 * @param $value mixed value to be checked
	 *
	 * @return bool true if array, false if null
	 * @throws InvalidParameterException
	 */
	private static function isArrayIgnoreNull($key, $value) {
		if($value === null) {
			return false;
		}
		if(is_array($value)) {
			return true;
		}
		if($key === null) {
			throw new InvalidParameterException('Should be an object or null, ' . gettype( $value ) . ' given');
		} else {
			throw new InvalidParameterException($key . ' should be an object or null, ' . gettype( $value ) . ' given');
		}
	}
}