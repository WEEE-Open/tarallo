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
	 * @param string $brand
	 * @param string $model
	 * @param string $variant
	 */
	public function __construct(string $brand, string $model, string $variant = self::DEFAULT_VARIANT) {
		$this->brand = $brand;
		$this->model = $model;
		$this->variant = $variant;
	}

	/**
	 * @return string
	 */
	public function getBrand(): string {
		return $this->brand;
	}

	/**
	 * @return string
	 */
	public function getModel(): string {
		return $this->model;
	}

	/**
	 * @return string|null
	 */
	public function getVariant(): ?string {
		return $this->variant;
	}
}
