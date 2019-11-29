<?php

namespace WEEEOpen\Tarallo;

/**
 * Regular items
 *
 * @package WEEEOpen\Tarallo
 */
class Item
	implements \JsonSerializable,
	ItemWithCode,
	ItemWithFeatures,
	ItemWithLocation {
	use ItemTraitOptionalCode;
	use ItemTraitContent;
	use ItemTraitLocation;
	use ItemTraitFeatures;
	use ItemTraitProduct;

	protected $token = null;
	protected $deletedAt = null;
	protected $lostAt = null;
	protected $separate = false;


	public function getToken(): ?string {
		return $this->token;
	}

	public function setToken($token) {
		$this->token = $token;
		return $this;
	}

	public function setSeparate() {
		$this->separate = true;
		foreach($this->contents as $item) {
			$item->setSeparate();
		}
	}

	public function jsonSerialize() {
		$array = [];
		$array['code'] = $this->getCode();
		if($this->separate) {
			// TODO: add item_features + product_features to $array
		} else {
			// TODO: add features (both product and item) to $array
			// TODO: item overrides product if there is a conflict
			if(!empty($this->features)) {
				foreach($this->features as $feature) {
					/** @var Feature $feature */
					$name = $feature->name;
					$value = $feature->value;
					$array['features'][$name] = $value;
				}
			}
		}
		if(!empty($this->contents)) {
			$array['contents'] = $this->contents;
		}
		if(!empty($this->location)) {
			$array['location'] = $this->getPath();
		}
		if($this->deletedAt !== null) {
			$array['deleted_at'] = $this->deletedAt;
		}
		if($this->lostAt !== null) {
			$array['lost_at'] = $this->deletedAt;
		}

		return $array;
	}

	public function __toString() {
		$type = $this->getFeature('type');
		if($type === null) {
			return $this->getCode();
		} else {
			return $this->getCode() . " ($type)";
		}
	}

	// TODO: consider the builder pattern as an alternative
	public function setDeletedAt(\DateTime $when) {
		$this->deletedAt = $when;
	}

	public function setLostAt(\DateTime $when) {
		$this->lostAt = $when;
	}

	public function getDeletedAt(): ?\DateTime {
		return $this->deletedAt;
	}

	public function getLostAt(): ?\DateTime {
		return $this->lostAt;
	}
}
