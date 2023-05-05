<?php

namespace WEEEOpen\Tarallo;

interface ItemWithDonations
{
	public function getDonations(): array;
	public function hasDonations(): bool;
	public function getDonation($id): array;
	public function addDonation($id, $donation);
	public function removeDonation($id);
	public function clearDonations();
}
