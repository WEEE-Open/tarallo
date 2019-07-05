<?php
/** @var string $currentPage */
?>
<nav class="menu submenu">
	<ul>
		<li><a href="/bulk/move"<?= $currentPage === 'move' ? ' class="selected"' : '' ?>>Move</a></li
		><li><a href="/bulk/add"<?= $currentPage === 'add' ? ' class="selected"' : '' ?>>Add</a></li
	</ul>
</nav>
