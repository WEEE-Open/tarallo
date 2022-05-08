<?php

/** @var string $currentPage */
?>
<nav class="nav nav-pills submenu mb-sm-2 mb-lg-3">
	<a class="nav-link <?= $currentPage === '' ? 'active' : '' ?>" href="/options">General</a>
	<a class="nav-link <?= $currentPage === 'normalization' ? 'active' : '' ?>" href="/options/normalization">Normalization</a>
	<a class="nav-link <?= $currentPage === 'stats' ? 'active' : '' ?>" href="/options/stats">Global</a>
</nav>
