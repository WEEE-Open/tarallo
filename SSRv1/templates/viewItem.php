<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var \WEEEOpen\Tarallo\Server\Item $item */
/** @var string|null $add */
/** @var string|null $edit */
/** @var bool|null $recursion */
$this->layout('main', ['title' => $this->e($item->getCode()), 'user' => $user, 'itembuttons' => true]);
$this->insert('item', ['user' => $user, 'item' => $item, 'add' => $add, 'edit' => $edit, 'recursion' => false]);
