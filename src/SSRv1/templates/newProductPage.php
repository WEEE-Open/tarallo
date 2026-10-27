<?php

/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\ProductIncomplete|\WEEEOpen\Tarallo\Product|null $base */
$base = $base ?? null;
$baseItem = $baseItem ?? null;
$bulkMatches = $bulkMatches ?? null;
/** @var int $importedFrom|null */
$importedFrom = $importedFrom ?? null;

$this->layout('main', ['title' => 'New product', 'currentPage' => 'product new', 'tooltips' => true]);
$this->insert('newProduct', [
	'base' => $base,
	'baseItem' => $baseItem,
	'importedFrom' => $importedFrom,
	'bulkMatches' => $bulkMatches,
]);
