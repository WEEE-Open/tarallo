<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var string $title */
/** @var string $self */
$this->layout('main', ['title' => 'Authentication needed']) ?>

<section id="content">
	<p class="httperror"><strong>401</strong> Unauthorized, authentication needed</p>
	<p><a href="/login">Please authenticate</a></p></p>
</section>
