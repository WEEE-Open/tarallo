<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var \WEEEOpen\Tarallo\Server\Item|null $base */
/** @var bool $recursion|null */
/** @var bool $innerrecursion|null */
/** @var string[] $featuresEmpty|null */

$base = $base ?? null;
$recursion = $recursion ?? false; // Placed inside another item (new or existing)
$innerrecursion = $innerrecursion ?? false; // Placed inside another NEW item
$featuresEmpty = $featuresEmpty ?? [];

$this->layout('main', ['title' => 'New item', 'itembuttons' => true]);
$this->insert('newItem', [
    'recursion' => $recursion,
    'innerrecursion' => $innerrecursion,
    'featuresEmpty' => $featuresEmpty,
    'base' => $base,
]);
