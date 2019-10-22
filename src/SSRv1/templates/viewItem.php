<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\Item $item */
/** @var string|null $add */
/** @var string|null $edit */
$this->layout(
	'main',
	[
		'title' => $item->getCode(),
		'user' => $user,
		'moveDefaultFrom' => $item->getCode(),
		'itembuttons' => true
	]
);
$this->insert(
	'item',
	[
		'item' => $item,
		'add' => $add,
		'edit' => $edit,
		'recursion' => false,
	]
);
