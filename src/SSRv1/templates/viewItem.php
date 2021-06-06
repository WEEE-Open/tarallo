<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\Item $item */
/** @var int|null $depth */
/** @var string|null $add */
/** @var string|null $edit */
$this->layout(
	'main',
	[
		'title' => $item->getCode(),
		'user' => $user,
		'moveDefaultFrom' => $item->getCode(),
	]
);
if(isset($depth)) {
	$this->insert('depthWarning', ['depth' => $depth]);
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
