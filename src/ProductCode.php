<?php


namespace WEEEOpen\Tarallo;


class ProductCode {
	protected $brand;
	protected $model;
	protected $variant;
	const DEFAULT_VARIANT = 'default';

	/**
	 * Product constructor.
	 *
	 * @param string $brand Brand
	 * @param string $model Model
	 * @param string|null $variant Variant, if not set the default will be used
	 */
	public function __construct(string $brand, string $model, ?string $variant = null) {
		$this->brand = $brand;
		$this->model = $model;
		$this->variant = $variant ?? self::DEFAULT_VARIANT;
	}

	public function getBrand(): string {
		return $this->brand;
	}

	public function getModel(): string {
		return $this->model;
	}

	public function getVariant(): string {
		return $this->variant;
	}

	public function getVariantOrEmpty(): string {
		return self::variantOrEmpty($this->variant);
	}

	public static function variantOrEmpty(string $variant): string {
		if($variant === self::DEFAULT_VARIANT) {
			return '';
		} else {
			return $variant;
		}
	}

	public function __toString() {
		return "$this->brand $this->model $this->variant";
	}
}
