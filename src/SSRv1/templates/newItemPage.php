<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\Item|null $base */
/** @var int $importedFrom|null */
/** @var bool $recursion|null */
/** @var bool $innerrecursion|null */

$base = $base ?? null;
$importedFrom = $importedFrom ?? null;
$recursion = $recursion ?? false; // Placed inside another item (new or existing)
$innerrecursion = $innerrecursion ?? false; // Placed inside another NEW item

$this->layout('main', ['title' => 'New item', 'currentPage' => 'item new']);
$this->insert('newItem', [
    'recursion' => $recursion,
    'innerrecursion' => $innerrecursion,
    'base' => $base,
	'importedFrom' => $importedFrom,
]);
