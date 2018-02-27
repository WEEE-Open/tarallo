<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var string $title */
/** @var string $self */
if(isset($user)) {
	$this->layout('internalPage', ['title' => '404 Not Found', 'user' => $user]);
} else {
	$this->layout('externalPage', ['title' => '404 Not Found']);
}
?>

<section id="content">
	<p class="httperror"><strong>404</strong> Not Found</p>
</section>
