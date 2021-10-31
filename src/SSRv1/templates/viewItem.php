<?php

/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\Item $item */
/** @var string $url */
/** @var int|null $depth */
/** @var string|null $add */
/** @var string|null $edit */
$this->layout(
	'main',
	[
		'title' => $item->getCode(),
		'user' => $user,
		'moveDefaultFrom' => $item->getCode(),
		'tooltips' => isset($edit) || isset($add),
	]
);
if (isset($depth)) {
	$this->insert('depthWarning', ['depth' => $depth, 'viewAllUrl' => $url]);
}
$this->insert(
	'item',
	[
		'item' => $item,
		'add' => $add,
		'edit' => $edit,
		'recursion' => false,
	]
);
