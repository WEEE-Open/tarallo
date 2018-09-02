<?php
/** @var string $currentPage */
?>
<nav class="menu submenu">
	<ul>
		<li><a href="/stats"<?= $currentPage === '' ? ' class="selected"' : '' ?>>Stats</a></li
		><li><a href="/stats/attention"<?= $currentPage === 'attention' ? ' class="selected"' : '' ?>>Fix these</a></li
		><li><a href="/stats/cases"<?= $currentPage === 'cases' ? ' class="selected"' : '' ?>>Cases</a></li
		><li><a href="/stats/rams"<?= $currentPage === 'rams' ? ' class="selected"' : '' ?>>RAMs</a></li
		><li><a href="/stats/cpus"<?= $currentPage === 'cpus' ? ' class="selected"' : '' ?>>CPUs</a></li>
	</ul>
</nav>
