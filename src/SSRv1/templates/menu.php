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
					<a class="dropdown-item <?= $hereClass('item search', $currentPage) ?>" href="/search/advanced">Search<?= $hereSr('item search', $currentPage) ?></a>

				</div>
			</li>
			<li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle <?= $hereClass('product', $currentPageShort) ?>" href="#" id="productsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					Products<?= $hereSr('product', $currentPageShort) ?>
				</a>
				<div class="dropdown-menu" aria-labelledby="productsDropdown">
					<a class="dropdown-item <?= $hereClass('product new', $currentPage) ?>" href="/new/product">Create<?= $hereSr('product new', $currentPage) ?></a>
					<a class="dropdown-item <?= $hereClass('product list', $currentPage) ?>" href="/products">List<?= $hereSr('product list', $currentPage) ?></a>
				</div>
			</li>
			<li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle <?= $hereClass('info', $currentPageShort) ?>" href="#" id="infoDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					Info<?= $hereSr('info', $currentPageShort) ?>
				</a>
				<div class="dropdown-menu" aria-labelledby="infoDropdown">
					<a class="dropdown-item <?= $hereClass('info locations', $currentPage) ?>" href="/info/locations">Locations<?= $hereSr('info locations', $currentPage) ?></a>
					<a class="dropdown-item <?= $hereClass('info todo', $currentPage) ?>" href="/info/todo">TODO<?= $hereSr('info todo', $currentPage) ?></a>
				</div>
			</li>
			<li class="nav-item">
				<a class="nav-link" data-toggle="collapse" href="#quickmove" role="button" aria-expanded="false" aria-controls="quickmove" id="quickmovebutton">Move</a>
			</li>
			<li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle <?= $hereClass('bulk', $currentPageShort) ?>" href="#" id="bulkDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					Bulk Actions<?= $hereSr('bulk', $currentPageShort) ?>
				</a>
				<div class="dropdown-menu" aria-labelledby="bulkDropdown">
					<a class="dropdown-item <?= $hereClass('bulk import', $currentPage) ?>" href="/bulk/import">Import<?= $hereSr('bulk import', $currentPage) ?></a>
					<a class="dropdown-item <?= $hereClass('bulk add', $currentPage) ?>" href="/bulk/add">Bulk add<?= $hereSr('item bulk add', $currentPage) ?></a>
					<a class="dropdown-item <?= $hereClass('bulk move', $currentPage) ?>" href="/bulk/move">Bulk move<?= $hereSr('item bulk move', $currentPage) ?></a>
				</div>
			</li>
			<li class="nav-item">
				<a class="nav-link <?= $hereClass('stats', $currentPage) ?>" href="/stats">Stats<?= $hereSr('stats', $currentPage) ?></a>
			</li>
			<li class="nav-item">
				<a class="nav-link <?= $hereClass('options', $currentPage) ?>" href="/options">Options<?= $hereSr('options', $currentPage) ?></a>
			</li>
		</ul>

		<form class="form-inline my-2 my-md-0" action="/search" method="post">
			<input required class="form-control mx-0 mr-sm-2" type="search" aria-label="Search" name="search">
			<button class="btn btn-outline-primary my-2 my-sm-0 mr-sm-2" type="submit">Search</button>
			<a class="btn btn-secondary my-2 my-sm-0" href="/search/advanced">Advanced</a>
		</form>
	</div>
</nav>

<nav id="quickmove" class="navbar navbar-dark bg-dark collapse">
	<form class="nav-item form-inline">
		<label for="quickmovecode" class="navbar-text mr-sm-1 col-form-label">Move item:</label>
		<input required class="form-control basicAutoComplete mr-sm-1" name="code" id="quickmovecode" type="text" autocomplete="off" data-url="/v2/autosuggest/code"<?= $moveDefaultFrom === null ? '' : ' value="' . $this->e($moveDefaultFrom) . '"' ?>>
		<button class="btn btn-outline-secondary mr-1 my-2 my-sm-0 swap" title="Swap" tabindex="-1">⇄</button>
		<label for="quickmovelocation" class="navbar-text col-form-label mr-sm-1">into:</label>
		<input required class="form-control basicAutoComplete mr-sm-1 to" id="quickmovelocation" type="text" autocomplete="off" data-url="/v2/autosuggest/code">
		<button type="submit" class="btn btn-outline-secondary mt-2 mt-sm-0 do">Move</button>
	</form>
	<div class="nav-item alert alert-success col-12 my-2 d-none" role="alert" style="display: flex; justify-content: space-between;">Ok</div>
	<div class="nav-item alert alert-danger col-12 my-2 d-none" role="alert">Error</div>
	<div class="nav-item alert alert-warning col-12 my-2 d-none" role="alert">Fail</div>
</nav>
<script src="https://cdn.jsdelivr.net/gh/xcash/bootstrap-autocomplete@v2.3.7/dist/latest/bootstrap-autocomplete.min.js"></script>
<script>$('.basicAutoComplete').autoComplete({minLength:3,resolverSettings:{requestThrottling:300}});</script>
