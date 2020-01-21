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
	ItemWithProduct,
	ItemWithContent,
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
			/** @var Item $item */
			$item->setSeparate();
		}
	}

	public function jsonSerialize() {
		$array = [];
		$array['code'] = $this->getCode();

		$array['features'] = [];

		if($this->separate) {
			// Add item features
			if(!empty($this->features)){
				foreach($this->features as $features) {
					$array['features'][$features->name] = $features->value;
				}
			}

			// Then add a product
			if(!empty($this->product)) {
				$array['product'] = $this->product;
			}
		} else {
			// Add product features first
			if(!empty($this->product)) {
				foreach($this->product->getFeatures() as $features) {
					$array['features'][$features->name] = $features->value;
				}
			}

			// Then item features, so they can override others
			if(!empty($this->features)){
				foreach($this->features as $features) {
					$array['features'][$features->name] = $features->value;
				}
			}
		}
		if(!empty($this->contents)) {
			$array['contents'] = $this->contents;
		}
		if(!empty($this->location)) {
			$array['location'] = $this->getPath();
		}
		if($this->deletedAt instanceof \DateTime) {
			$array['deleted_at'] = $this->deletedAt->format(DATE_ISO8601);
		}
		if($this->lostAt instanceof \DateTime) {
			$array['lost_at'] = $this->lostAt->format(DATE_ISO8601);
		}

		return $array;
	}

	public function __toString() {
		$type = $this->getFeatureValue('type');
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
