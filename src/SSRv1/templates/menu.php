<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var string $moveDefaultFrom */
/** @var string $currentPage */
$moveDefaultFrom = $moveDefaultFrom ?? null;
$currentPageShort = explode(' ', $currentPage)[0];
$hereClass = function($page, $current){
	return $page === $current ? 'active' : '';
};
$hereSr = function($page, $current) {
	return $page === $current ? ' <span class="sr-only">(current)</span>' : '';
};
?>
<nav class="navbar navbar-expand-md navbar-light bg-secondary" id="main">
	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#menu" aria-controls="main" aria-expanded="false" aria-label="Toggle navigation">
		<span class="navbar-toggler-icon"></span>
	</button>

	<div class="collapse navbar-collapse" id="menu">
		<ul class="navbar-nav mr-auto">
			<li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle <?= $hereClass('item', $currentPageShort) ?>" href="#" id="itemsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					Items<?= $hereSr('item', $currentPageShort) ?>
				</a>
				<div class="dropdown-menu" aria-labelledby="itemsDropdown">
					<a class="dropdown-item <?= $hereClass('item new', $currentPage) ?>" href="/new/item">Create<?= $hereSr('item new', $currentPage) ?></a>
					<a class="dropdown-item <?= $hereClass('item bulk add', $currentPage) ?>" href="/bulk/add">Bulk add<?= $hereSr('item bulk add', $currentPage) ?></a>
					<a class="dropdown-item <?= $hereClass('item bulk move', $currentPage) ?>" href="/bulk/move">Bulk move<?= $hereSr('item bulk move', $currentPage) ?></a>
				</div>
			</li>
			<li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle <?= $hereClass('product', $currentPageShort) ?>" href="#" id="productsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					Products<?= $hereSr('product', $currentPageShort) ?>
				</a>
				<div class="dropdown-menu" aria-labelledby="productsDropdown">
					<a class="dropdown-item <?= $hereClass('product new', $currentPage) ?>" href="/new/product">Create<?= $hereSr('product new', $currentPage) ?></a>
					<a class="dropdown-item <?= $hereClass('product list', $currentPage) ?>" href="/product">List<?= $hereSr('product list', $currentPage) ?></a>
				</div>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="#" data-toggle="move" id="quickmovebutton">Move<span class="d-none"> (open)</span></a>
			</li>
			<li class="nav-item">
				<a class="nav-link <?= $hereClass('stats', $currentPage) ?>" href="/stats">Stats<?= $hereSr('stats', $currentPage) ?></a>
			</li>
		</ul>

		<form class="form-inline my-2 my-lg-0" action="/search" method="post">
			<input required class="form-control mr-sm-2" type="search" aria-label="Search" name="search">
			<button class="btn btn-outline-primary my-2 my-sm-0" type="submit">Search</button>
		</form>
	</div>
</nav>

<nav id="quickmove" class="navbar navbar-dark bg-dark d-none">
	<form class="nav-item form-inline">
		<label for="quickmovecode" class="navbar-text col-form-label">Move item:</label>
		<input required class="form-control" name="code" id="quickmovecode" type="text"<?= $moveDefaultFrom === null ? '' : ' value="' . $this->e($moveDefaultFrom) . '"' ?>>
		<button class="btn btn-outline-secondary swap" title="Swap" tabindex="-1">⇄</button>
		<label for="quickmovelocation" class="navbar-text col-form-label">into:</label>
		<input required class="form-control to" id="quickmovelocation" type="text">
		<button type="submit" class="btn btn-outline-secondary do">Move</button>
	</form>
	<div class="nav-item alert alert-success ml-0 mr-auto my-auto d-none" role="alert"><a href="#">Ok</a></div>
	<div class="nav-item alert alert-danger col-12 my-2 d-none" role="alert">Error</div>
	<div class="nav-item alert alert-warning col-12 my-2 d-none" role="alert">Fail</div>
</nav>
<script src="/static/menu.js"></script>
