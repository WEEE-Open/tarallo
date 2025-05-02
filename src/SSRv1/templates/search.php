<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var int|null $depth */
/** @var string $noDepthUrl */
/** @var int|null $searchId */
/** @var \WEEEOpen\Tarallo\Search|null $search */
/** @var int|null $page Current page (only if searchId is not null) */
/** @var int|null $pages Total number of pages (only if searchId is not null) */
/** @var int|null $total Total number of results (only if searchId is not null) */
/** @var int|null $resultsPerPage Pretty self-explanatory (only if searchId is not null) */
/** @var \WEEEOpen\Tarallo\Item[]|null $results Items (only if searchId is not null) */
$this->layout('main', ['title' => 'Search', 'user' => $user, 'currentPage' => 'item search', 'tooltips' => true]);
// $placeholder = ['R%', 'C_', 'ROS%', '%Box%', 'Box__'];
// placeholder="?= $placeholder[array_rand($placeholder)] ?"
?>

<?php if (!$searchId) : ?>
	<p class="alert alert-info" id="search-tip" role="alert"><i class="fa fa-lightbulb mr-2"></i>Do you know the code already? Type it in the box in the upper right corner, instead!</p>
<?php endif; ?>
<p class="alert alert-danger d-none" id="search-error" role="alert"></p>

<?php if ($searchId && isset($depth)) {
	$this->insert('depthWarning', ['depth' => $depth, 'viewAllUrl' => $noDepthUrl]);
} ?>

<template id="search-template-code">
	<div class="row d-flex flex-row mr-0 mb-1 searchrow search-code" id="search-row-container-new">
		<label class="col-sm-2 col-form-label" for="search-row-new">Code is <span data-tippy-content="Wildcards: % for any number of characters, _ for a single character">like</span></label>
		<div class="flex-fill">
			<input type="text" class="form-control comparisonvalue basicAutoComplete" id="search-row-new" required autocomplete="off" data-url="/v2/autosuggest/code">
		</div>
		<button class="btn btn-danger ml-2 delete" aria-roledescription="delete" tabindex="-1"><i class="fa fa-trash" role="img" aria-label="Delete"></i></button>
	</div>
</template>

<template id="search-template-location">
	<div class="row d-flex flex-row mr-0 mb-1 searchrow search-location" id="search-row-container-new">
		<label class="col-sm-2 col-form-label" for="search-row-new">Location is one of</label>
		<div class="flex-fill">
			<input type="text" class="form-control comparisonvalue" id="search-row-new" required autocomplete="off" data-url="/v2/autosuggest/location">
		</div>
		<button class="btn btn-danger ml-2 delete" aria-roledescription="delete" tabindex="-1"><i class="fa fa-trash" role="img" aria-label="Delete"></i></button>
	</div>
</template>

<template id="search-template-features">
	<div class="row d-flex flex-row mr-0 mb-1 searchrow search-features" id="search-row-container-new">
		<label class="col-sm-2 col-form-label" for="search-row-new">Has</label>
		<div class="flex-fill">
			<select class="form-control allfeatures" id="search-row-new" required>
			</select>
		</div>
		<div>
			<select class="form-control comparison" aria-label="Compare like" required>
			</select>
		</div>
		<div class="flex-fill featurevalues comparisonvalue">
			<select class="form-control" aria-label="Value" required>
			</select>
		</div>
		<button class="btn btn-danger ml-2 delete" aria-roledescription="delete" tabindex="-1"><i class="fa fa-trash" role="img" aria-label="Delete"></i></button>
	</div>
</template>

<template id="search-template-ancestor">
	<div class="row d-flex flex-row mr-0 mb-1 searchrow search-ancestor" id="search-row-container-new">
		<label class="col-sm-2 col-form-label" for="search-row-new">Container has</label>
		<div class="flex-fill">
			<select class="form-control allfeatures" id="search-row-new" required>
			</select>
		</div>
		<div>
			<select class="form-control comparison" aria-label="Compare like" required>
			</select>
		</div>
		<div class="flex-fill featurevalues comparisonvalue">
			<select class="form-control" aria-label="Value" required>
			</select>
		</div>
		<button class="btn btn-danger ml-2 delete" aria-roledescription="delete" tabindex="-1"><i class="fa fa-trash" role="img" aria-label="Delete"></i></button>
	</div>
</template>

<template id="search-template-sort">
	<div class="row d-flex flex-row mr-0 mb-1 searchrow search-sort" id="search-row-container-new">
		<label class="col-sm-2 col-form-label" for="search-row-1">Sort by</label>
		<div class="flex-fill">
			<select class="form-control allfeatures" id="search-row-new" required>
			</select>
		</div>
		<div>
			<select class="form-control sorting" id="search-row-new" required>
				<option value="+">Ascending (alphabetical)</option>
				<option value="-">Descending</option>
			</select>
		</div>
		<button class="btn btn-danger ml-2 delete" aria-roledescription="delete" tabindex="-1"><i class="fa fa-trash" role="img" aria-label="Delete"></i></button>
	</div>
</template>

<?php if ($searchId === null || !empty($results)) : ?>
<form id="searchform" class="search-refine-collapse <?= $searchId === null ? '' : 'collapse' ?>" <?= $searchId === null ?: 'data-search-id="' . (int)$searchId . '"' ?> >
	<p>Select one or more filters. Only items that match <em>all</em> the filters are returned.</p>
	<div class="mb-2">
		Add...
		<div id="searchbuttons" class="btn-group" role="group">
			<button class="btn btn-secondary" type="button" id="search-control-code" data-template="search-template-code">Code</button>
			<button class="btn btn-secondary" type="button" id="search-control-location" data-template="search-template-location">Location</button>
			<button class="btn btn-secondary" type="button" id="search-control-features" data-template="search-template-features">Features</button>
			<button class="btn btn-secondary" type="button" id="search-control-ancestor" data-template="search-template-ancestor">Container</button>
			<button class="btn btn-secondary" type="button" id="search-control-sort" data-template="search-template-sort">Sort</button>
		</div>
	</div>
	<div id="searchrows"></div>
	<script id="search-data" type="application/json"><?php if (isset($search)) echo json_encode($search); ?></script>
	<div>
		<input type="submit" disabled class="btn btn-primary" id="searchbutton" value="<?=$searchId === null ? 'Search' : 'Refine'?>">
	</div>
</form>

	<?php if ($searchId !== null) : ?>
	<button class="btn btn-primary search-refine-collapse collapse show" id="refinecollapsebutton" data-toggle="collapse" data-target=".search-refine-collapse" role="button" aria-expanded="false" aria-controls="searchform refinecollapsebutton">
		Refine search
	</button>
	<?php endif; ?>

<?php endif; ?>

<?php if (!empty($results)) : ?>
	<div id="stats"><?= $total ?> results, showing <?= $resultsPerPage ?> (page <?= $page ?> of <?= $pages ?>).</div>
<?php endif ?>
<div class="results">
	<?php
	if ($searchId) :
		if (empty($results)) : ?>
			<p>Nothing found :(</p>
			<p><!--suppress HtmlUnknownTarget --><a href="/search/advanced" class="btn btn-primary">New search</a></p>
		<?php else :
			$this->insert('pagination', ['page' => $page, 'pages' => $pages, 'searchId' => $searchId]);
			foreach ($results as $item) {
				/* the editor is activated by item or newItem (called by item) */
				$parameters = ['item' => $item, 'recursion' => false];
				if (isset($add)) {
					$parameters['add'] = $add;
				} elseif (isset($edit)) {
					$parameters['edit'] = $edit;
				}
				$this->insert('item', $parameters);
			}
			$this->insert('pagination', ['page' => $page, 'pages' => $pages, 'searchId' => $searchId]);
		endif;
	endif;
	?>
</div>

<?php if (!isset($edit)) :
	$this->insert('editor');
endif; ?>
<script src="/static/search.js"></script>
