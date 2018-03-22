<?php
/** @var string $title */
/** @var string $lang */
/** @var boolean $itembuttons */
/** @var \WEEEOpen\Tarallo\Server\User $user */
$itembuttons = $itembuttons ?? false;
?>
<!doctype html>
<html lang="<?= /* $lang */ 'en-US' ?>">
<head>
	<meta charset="utf-8">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<title><?=$this->e($title)?> - T.A.R.A.L.L.O.</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="/main.css">
</head>
<body>

<header id="title">
	<h1>T.A.R.A.L.L.O.</h1>
	<p>Trabiccolo Amministrazione Rottami e Assistenza, Legalmente-noto-come L'inventario Opportuno</p>
</header>
<div id="view">
	<?php if(isset($user) || $this->section('aftermenu')): ?>
	<nav id="top">
		<?php if(isset($user)):
			echo $this->fetch('menu');
		endif ?>
		<?= $this->section('aftermenu', '') ?>
	</nav>
	<?php endif ?>
	<section id="content">
		<?=$this->section('content')?>
	</section>
</div>
<?php if($itembuttons): ?>
	<script src="/itembuttons.js"></script>
<?php endif ?>
</body>
</html>
