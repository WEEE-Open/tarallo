<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var \WEEEOpen\Tarallo\Server\Item|null $copy */
/** @var bool $recursion|null */
/** @var bool $innerrecursion|null */
/** @var string[] $featuresEmpty|null */

$copy = $copy ?? null;
$recursion = $recursion ?? false; // Placed inside another item (new or existing)
$innerrecursion = $innerrecursion ?? false; // Placed inside another NEW item
$featuresEmpty = $featuresEmpty ?? [];

$this->layout('main', ['title' => 'New item', 'itembuttons' => true]);
$this->insert('newItem', [
    'item' => $copy,
    'recursion' => $recursion,
    'innerrecursion' => $innerrecursion,
    'featuresEmpty' => $featuresEmpty,
    'copy' => $copy,
]);
