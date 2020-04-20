<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\ProductIncomplete|\WEEEOpen\Tarallo\Product|null $base */

$base = $base ?? null;
/** @var int $importedFrom|null */

$this->layout('main', ['title' => 'New product', 'currentPage' => 'product new']);
$this->insert('newProduct', [
    'base' => $base,
	'importedFrom' => $importedFrom,
]);
