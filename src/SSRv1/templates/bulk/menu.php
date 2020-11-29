<?php
/** @var string $currentPage */
?>
<nav class="nav nav-pills submenu">
	<a class="nav-link <?= $currentPage === 'import' ? 'active' : '' ?>" href="/bulk/import">Import</a>
	<a class="nav-link <?= $currentPage === 'add' ? 'active' : '' ?>" href="/bulk/add">Add</a>
	<a class="nav-link <?= $currentPage === 'move' ? 'active' : '' ?>" href="/bulk/move">Move</a>
</nav>
