<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var \Psr\Http\Message\RequestInterface $request */
/** @var \Psr\Http\Message\ResponseInterface $response */
/** @var string $reason optional */
$this->layout('main', ['title' => $response->getStatusCode() . ' ' . $response->getReasonPhrase()]); ?>

<section id="content">
	<p class="httperror"><strong><?=$response->getStatusCode()?></strong> <?=$response->getReasonPhrase()?></p>
	<?php if(isset($reason)): ?>
		<p><?= $this->e($reason) ?></p>
	<?php endif; ?>
	<p></p>
</section>
