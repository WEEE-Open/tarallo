<?php
/** @var string $title */
$this->layout('main', ['title' => $title]) ?>

<section id="content">
	<?=$this->section('content')?>
</section>
