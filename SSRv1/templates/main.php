<?php
/** @var string $title */
?>
<!doctype html>
<html lang="<?= $this->language ?>">
<head>
	<meta charset="utf-8">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<title><?=$this->e($title)?> - T.A.R.A.L.L.O.</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="/main.css">
	<!--<link rel="stylesheet" href="https://unpkg.com/purecss@1.0.0/build/pure-min.css"
			integrity="sha384-nn4HPE8lTHyVtfCBi5yW9d20FjT8BJwUXyWZT9InLYax14RDjBj46LmSztkmNP9w" crossorigin="anonymous">-->
</head>
<body>

<header id="title">
	<h1>T.A.R.A.L.L.O.</h1>
	<p>Trabiccolo Amministrazione Rottami e Assistenza, Legalmente-noto-come L'inventario Opportuno</p>
</header>
<section id="view">
	<?=$this->section('content')?>
</section>
</body>
</html>
