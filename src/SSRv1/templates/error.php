<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var string $statusCode int */
/** @var string $reason optional */
/** @var string $reasonNoEscape optional and not escaped */
$reasonPhrase = $this->fetch('reasonPhrase', ['statusCode' => $statusCode]);
$this->layout('main', ['title' => $statusCode . ' ' . $reasonPhrase, 'container' => true]); ?>

<section id="content">
	<p class="httperror">
        <strong class="status"><?= $statusCode ?></strong><span class="status">
            <?= $reasonPhrase ?></span>
    </p>
	<?php if(isset($reason)): ?>
		<p><?= $this->e($reason) ?></p>
	<?php elseif(isset($reasonNoEscape)): ?>
        <p><?= $reasonNoEscape ?></p>
	<?php endif; ?>
</section>
