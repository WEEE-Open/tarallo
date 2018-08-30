<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var string $title */
/** @var string $self */
$this->layout('main', ['title' => 'Authentication needed']) ?>

<section id="content">
	<p class="httperror"><strong>403</strong> Authentication needed</p>
	<p><a href="/login">Please authenticate</a></p></p>
</section>
