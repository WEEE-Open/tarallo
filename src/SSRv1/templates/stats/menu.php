<?php

/** @var string $currentPage */
?>
<nav class="nav nav-pills submenu">
	<a class="nav-link <?= $currentPage === '' ? 'active' : '' ?>" href="/info/stats">General</a>
	<a class="nav-link <?= $currentPage === 'attention' ? 'active' : '' ?>" href="/info/stats/attention">Fix these</a>
	<a class="nav-link <?= $currentPage === 'cases' ? 'active' : '' ?>" href="/info/stats/cases">Cases</a>
	<a class="nav-link <?= $currentPage === 'rams' ? 'active' : '' ?>" href="/info/stats/rams">RAMs</a>
	<a class="nav-link <?= $currentPage === 'cpus' ? 'active' : '' ?>" href="/info/stats/cpus">CPUs</a>
	<a class="nav-link <?= $currentPage === 'hdds' ? 'active' : '' ?>" href="/info/stats/hdds">HDDs</a>
	<a class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>" href="/info/stats/users">Users</a>
	<a class="nav-link <?= $currentPage === 'products' ? 'active' : '' ?>" href="/info/stats/products">Products</a>
	<a class="nav-link <?= $currentPage === 'history' ? 'active' : '' ?>" href="/info/stats/history">History</a>
	<a class="nav-link <?= $currentPage === 'cool' ? 'active' : '' ?>" href="/info/stats/cool">Cool</a>

</nav>
