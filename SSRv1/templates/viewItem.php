<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var \WEEEOpen\Tarallo\Server\Item $item */
/** @var string|null $add */
/** @var string|null $edit */
/** @var bool $deleted */
$this->layout('main', ['title' => $item->getCode(), 'user' => $user, 'itembuttons' => true]);
$this->insert('item', ['item' => $item, 'add' => $add, 'edit' => $edit, 'deleted' => $deleted, 'recursion' => false]);
