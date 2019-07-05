<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var int|null $searchId */
/** @var int|null $page Current page (only if searchId is not null) */
/** @var int|null $pages Total number of pages (only if searchId is not null) */
/** @var int|null $total Total number of results (only if searchId is not null) */
/** @var int|null $resultsPerPage Pretty self-explanatory (only if searchId is not null) */
/** @var \WEEEOpen\Tarallo\Server\Item[]|null $results Items (only if searchId is not null) */
$this->layout('main', ['title' => 'Search', 'user' => $user, 'itembuttons' => true]);
?>

<nav id="searcharea">
	<nav id="searchbuttons">
		<p>Filter by
			<button data-for="search-control-code">Code</button
			><button data-for="search-control-location">Location</button
			><button data-for="search-control-features">Features</button
			><button data-for="search-control-ancestor">Ancestor</button
			><button data-for="search-control-sort">Sort</button>
		</p>
		<!--Use an URL parameter: <button>Depth</button>-->
	</nav>
	<!--<label><input type="checkbox" class="usecompactview" checked="checked">Usa ComputerView (se possibile)</label>-->
	<div id="searchcontrols">
		<div id="search-control-code" class="hidden">
			<label class="searchby" for="search-control-code-input">Code:</label>
			<div class="filter"><input id="search-control-code-input">&nbsp;(Supports % and _)</div>
		</div>
		<div id="search-control-location" class="hidden">
			<label class="searchby" for="search-control-location-input">Location:</label>
			<div class="filter"><input id="search-control-location-input"></div>
		</div>
		<div id="search-control-features" class="hidden">
			<label class="searchby">Features:</label>
			<div class="filter"><div class="own features"><ul></ul></div><div class="selector"><label>Feature:<select class="allfeatures"></select></label><button>Add</button></div></div>
		</div>
		<div id="search-control-ancestor" class="hidden">
			<label class="searchby">Ancestor:</label>
			<div class="filter"><div class="own features"><ul></ul></div><div class="selector"><label>Feature:<select class="allfeatures"></select></label><button>Add</button></div></div>
		</div>
		<div id="search-control-sort" class="hidden">
			<label class="searchby" for="search-control-sort-input">Order by:</label>
			<div class="filter"><select id="search-control-sort-input" class="allfeatures">
			</select><label><select id="search-control-sort-direction-input">
				<option value="+">Ascending (alphabetical)</option>
				<option value="-">Descending</option>
			</select></label></div>
		</div>
	</div>
	<button id="searchbutton" <?= $searchId === null ?: 'data-search-id="'.$searchId.'"' ?>><?=$searchId === null ? 'Search' : 'Refine'?></button>
</nav>
<?php if(!empty($results)): ?>
<div id="stats"><?= $total ?> results, showing <?= $resultsPerPage ?> (page <?= $page ?> of <?= $pages ?>).</div>
<?php endif ?>
<div class="results">

	<?php
	if($searchId):
		if(empty($results)): ?>
			<p>Nothing found :(</p>
		<?php else:
			$this->insert('pagination', ['page' => $page, 'pages' => $pages, 'searchId' => $searchId]);
			foreach($results as $item) {
				/* the editor is activated by item or newItem (called by item) */
				$parameters = ['item' => $item, 'recursion' => false];
				if(isset($add)) {
					$parameters['add'] = $add;
				} else if(isset($edit)) {
					$parameters['edit'] = $edit;
				}
				$this->insert('item', $parameters);
			}
			$this->insert('pagination', ['page' => $page, 'pages' => $pages, 'searchId' => $searchId]);
		endif;
	endif;
	?>

</div>
<script src="/static/search.js"></script>
