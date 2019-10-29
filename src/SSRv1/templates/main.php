<?php
/** @var string $title */
/** @var string $lang */
/** @var boolean $itembuttons */
/** @var string $moveDefaultFrom */
/** @var \WEEEOpen\Tarallo\User $user */
/** @var string $currentPage|null */
$currentPage = $currentPage ?? null;
$itembuttons = $itembuttons ?? false;
$moveDefaultFrom = $moveDefaultFrom ?? null;
?>
<!doctype html>
<html lang="<?= /* $lang */ 'en-US' ?>">
<head>
	<meta charset="utf-8">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<title><?=$this->e($title)?> - T.A.R.A.L.L.O.</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/png" href="/static/favicon.png"/>
	<link rel="stylesheet" href="/static/main.css">
	<?php if(defined('TARALLO_DEVELOPMENT_ENVIRONMENT') && TARALLO_DEVELOPMENT_ENVIRONMENT): ?>
    <style>
        a, a:visited, a:active, a:hover, a:visited:hover {
            color: darkorange;
        }

        header#title, table > thead, .tablewrapper table .total.last, .item h2, #stats {
            background-color: darkorange;
        }
    </style>
    <?php endif; ?>
</head>
<body>

<header id="title">
	<h1>T.A.R.A.L.L.O.</h1>
    <?php if(defined('TARALLO_DEVELOPMENT_ENVIRONMENT') && TARALLO_DEVELOPMENT_ENVIRONMENT): ?>
    <p>Development version, this is running locally on your machine</p>
    <?php else: ?>
	<p>Trabiccolo Amministrazione Rottami e Assistenza, Legalmente-noto-come L'inventario Opportuno</p>
    <?php endif ?>
</header>
<div id="view">
	<?php if(isset($user) || $this->section('aftermenu')): ?>
	<nav id="top">
		<?php if(isset($user)):
			echo $this->fetch('menu', ['moveDefaultFrom' => $moveDefaultFrom, 'currentPage' => $currentPage]);
		endif ?>
		<?= $this->section('aftermenu', '') ?>
	</nav>
	<?php endif ?>
	<section id="content">
		<?=$this->section('content')?>
	</section>
</div>
<?php if($itembuttons): ?>
	<script src="/static/itembuttons.js"></script>
<?php endif ?>
</body>
</html>
