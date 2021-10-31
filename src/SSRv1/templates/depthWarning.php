<?php

/** @var int $depth */
/** @var string $viewAllUrl */
?>

<p class="alert alert-warning" role="alert">
	Showing items only up to <?= (int) $depth ?> deep. <a href="<?= $this->e($viewAllUrl) ?>">View all</a>.
</p>
