<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \Psr\Http\Message\RequestInterface $request */
/** @var \Psr\Http\Message\ResponseInterface $response */
/** @var string $reason optional */
/** @var string $reasonNoEscape optional and not escaped */
$this->layout('main', ['title' => $response->getStatusCode() . ' ' . $response->getReasonPhrase()]); ?>

<section id="content">
	<p class="httperror">
        <strong class="status"><?=$response->getStatusCode()?></strong><span class="status">
            <?=$response->getReasonPhrase()?></span>
    </p>
	<?php if(isset($reason)): ?>
		<p><?= $this->e($reason) ?></p>
	<?php elseif(isset($reasonNoEscape)): ?>
        <p><?= $reasonNoEscape ?></p>
	<?php endif; ?>
</section>
