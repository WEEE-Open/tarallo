<?php
/** @var string $currentPage */
?>
<nav class="nav nav-pills submenu">
	<a class="nav-link <?= $currentPage === '' ? 'active' : '' ?>" href="/stats">General</a>
	<a class="nav-link <?= $currentPage === 'attention' ? 'active' : '' ?>" href="/stats/attention">Fix these</a>
	<a class="nav-link <?= $currentPage === 'todo' ? 'active' : '' ?>" href="/stats/todo">TODO</a>
	<a class="nav-link <?= $currentPage === 'cases' ? 'active' : '' ?>" href="/stats/cases">Cases</a>
	<a class="nav-link <?= $currentPage === 'rams' ? 'active' : '' ?>" href="/stats/rams">RAMs</a>
	<a class="nav-link <?= $currentPage === 'cpus' ? 'active' : '' ?>" href="/stats/cpus">CPUs</a>
	<a class="nav-link disabled" href="/stats/hdds" tabindex="-1" aria-disabled="true">HDDs</a>
	<a class="nav-link disabled" href="/stats/users" tabindex="-1" aria-disabled="true">Users</a>
	<a class="nav-link <?= $currentPage === 'products' ? 'active' : '' ?>" href="/stats/product">Products</a>
</nav>
