<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var string $title */
/** @var string $self */
$this->layout('main', ['title' => 'Authentication needed']) ?>

<section id="content">
	<p class="httperror"><strong>403</strong> <a href="/login">Please authenticate</a></p>
</section>
