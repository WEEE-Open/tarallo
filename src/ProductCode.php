<?php


namespace WEEEOpen\Tarallo;


class ProductCode {
	protected $brand;
	protected $model;
	protected $variant;
	const DEFAULT_VARIANT = null;

	/**
	 * Product constructor.
	 *
	 * @param string $brand
	 * @param string $model
	 * @param string $variant
	 */
	public function __construct(string $brand, string $model, ?string $variant = self::DEFAULT_VARIANT) {
		$this->brand = $brand;
		$this->model = $model;
		$this->variant = $variant;
	}
}
