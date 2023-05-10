<?php

namespace WEEEOpen\Tarallo;

trait ItemTraitDonations
{
	protected $donations = [];

	/**
	 * Add a donation to item
	 *
	 * @param Integer $id
	 *
	 * @return $this aka the parent item to the one you just added
	 */
	public function addDonation($id, $donation)
	{
		$this->donations[$id] = $donation;

		return $this;
	}

	/**
	 * Return if item is part of any donation
	 *
	 * @return bool
	 */
	public function hasDonations(): bool
	{
		return !empty($this->donations);
	}

	/**
	 * Get donations of item
	 *
	 * @return Array
	 */
	public function getDonations(): array
	{
		return $this->donations;
	}

	/**
	 * Get a specific donation of item
	 *
	 * @param Integer $id
	 *
	 * @return Array
	 */
	public function getDonation($id): array
	{
		if ($this->donations ?? null !== null) {
			throw new \InvalidArgumentException("Cannot retrieve donation {$id} from {$this}: not here");
		} else {
			return $this->donations[$id];
		}
	}

	/**
	 * Remove all donations of item
	 */
	public function clearDonations(): array
	{
		$this->donations = [];
	}

	/**
	 * Remove specific donation of item
	 *
	 * @param Integer $id
	 */
	public function removeDonation($id): array
	{
		if ($donations[$id] ?? null !== null) {
			throw new \InvalidArgumentException("Cannot remove donation {$id} from {$this}: not here");
		} else {
			unset($this->donations[$id]);
		}
	}
}
