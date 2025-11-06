<?php

/** @var string $commit|null */

$this->layout('main', ['title' => 'Credits', 'currentPage' => 'info credits', 'tooltips' => true]);

?>

<div class="row">
	<h1>T.A.R.A.L.L.O</h1>
	<h3 class="col-12">Tuttofare Assistente il Riuso di Aggeggi Logori e Localmente Opprimenti</h2>
	<h4 class="col-12">(aka L'inventario Opportuno)</h4>
	<div class="col-12"><b>Current commit:</b> <?=$commit ?? "<i>unknown</i>"?></div>
	<div class="col-12"><b>Current PHP version:</b> <?=phpversion() ?? "<i>unknown</i>"?></div>
	<div class="col-12"><b>Github page:</b> <a href="https://github.com/WEEE-Open/tarallo">https://github.com/WEEE-Open/tarallo</a></div>
	<div class="col-12">
		<img src="/static/logo.png" alt="WEEEOPEN logo" height="300" width="300">
		<img src="/static/tarallo.png" alt="Divino Grante Tarallo Volante" title="Il Grande Tarallo Volante ti guiderà alla Luce" height="300" width="300">
	</div>
</div>