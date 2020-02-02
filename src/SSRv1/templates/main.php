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
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" type="image/png" href="/static/favicon.png"/>
<!--	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4/dist/css/bootstrap.min.css">-->
<!--	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4/css/font-awesome.min.css">-->
	<link rel="stylesheet" href="/static/bootstrap.custom.min.css">
	<link rel="stylesheet" href="/static/main.css">
	<?php if(defined('TARALLO_DEVELOPMENT_ENVIRONMENT') && TARALLO_DEVELOPMENT_ENVIRONMENT): ?>
	<style>
        /*a, a:visited, a:active, a:hover, a:visited:hover {*/
        /*    color: darkorange;*/
        /*}*/

        /*header#title, table > thead, .tablewrapper table .total.last, .item h2, #stats {*/
        /*    background-color: darkorange;*/
        /*}*/
    </style>
	<?php endif; ?>
	<title><?=$this->e($title)?> - T.A.R.A.L.L.O.</title>
</head>
<body>

<nav class="navbar navbar-dark bg-dark">
	<a href="/" class="navbar-brand" title="Trabiccolo Amministrazione Rottami e Assistenza, Legalmente-noto-come L'inventario Opportuno">T.A.R.A.L.L.O.</a>

	<?php if(defined('TARALLO_DEVELOPMENT_ENVIRONMENT') && TARALLO_DEVELOPMENT_ENVIRONMENT): ?>
		<small class="navbar-text text-muted">Development version, this is running locally on your machine</small>
	<?php endif ?>
	<?php if($user !== null): ?>
	<div class="ml-auto">
		<span class="mr-2 navbar-text">Logged in as <?= $this->e($user->cn) ?> (<?= $this->e($user->uid) ?>)</span><a class="btn btn-outline-secondary btn-sm" href="/logout">Logout</a>
	</div>
	<?php endif ?>
</nav>

<?php if(isset($user)) {
	echo $this->fetch('menu', ['moveDefaultFrom' => $moveDefaultFrom, 'currentPage' => $currentPage]);
} ?>

<section class="container">
	<?=$this->section('content')?>
</section>
<?php if($itembuttons): ?>
	<script src="/static/itembuttons.js"></script>
<?php endif ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap.native@2/dist/bootstrap-native-v4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2/dist/umd/popper.min.js"></script>
</body>
</html>
