<?php

/** @var string $currentPage */
/** @var \WEEEOpen\Tarallo\User $user */

use WEEEOpen\Tarallo\User;

?>
<nav class="nav nav-pills submenu mb-sm-2 mb-lg-3">
	<a class="nav-link <?= $currentPage === 'normalization' ? 'active' : '' ?>" href="/options/normalization">Normalization</a>
	<?php if ($user->getlevel() === User::AUTH_LEVEL_ADMIN) : ?>
	<a class="nav-link <?= $currentPage === 'stats' ? 'active' : '' ?>" href="/options/stats">Global</a>
	<a class="nav-link <?= $currentPage === 'token' ? 'active' : '' ?>" href="/options/token">Tokens</a>
	<?php endif;?>
</nav>
