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
		<div id="search-control-code" class="disabled">
			<label class="searchby" for="search-control-code-input">Code:</label>
			<div class="filter"><input id="search-control-code-input">&nbsp;(Supports % and _)</div>
		</div>
		<div id="search-control-location" class="disabled">
			<label class="searchby" for="search-control-location-input">Location:</label>
			<div class="filter"><input id="search-control-location-input">&nbsp;(Supports % and _)</div>
		</div>
		<div id="search-control-features" class="disabled">
			<label class="searchby">Features:</label>
			<div class="filter"><div class="own features"><ul></ul></div><div class="selector"><label>Feature:<select class="allfeatures"></select></label><button>Add</button></div></div>
		</div>
		<div id="search-control-ancestor" class="disabled">
			<label class="searchby">Ancestor:</label>
			<div class="filter"><div class="own features"><ul></ul></div><div class="selector"><label>Feature:<select class="allfeatures"></select></label><button>Add</button></div></div>
		</div>
		<div id="search-control-sort" class="disabled">
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
			foreach($results as $item) {
				$this->insert('item', ['item' => $item, 'recursion' => false, 'allowIncludes' => false]);
			} ?>
			<div class="pagination"><?php if($page === 1): ?><!--
					--><a class="disabled">← Previous</a><!--
				<?php else: ?>
					--><a href="<?php printf('/search/%d/page/%d', $searchId, $page - 1) ?>">← Previous</a><!--
			<?php endif; for($i = 1; $i <= $pages; $i++): ?><!--
				<?php if($i === $page): ?>
					--><a class="disabled"><?= $i ?></a><!--
				<?php else: ?>
					--><a href="<?php printf('/search/%d/page/%d', $searchId, $i) ?>"><?= $i ?></a><!--
				<?php endif ?>
			<?php endfor; if($page === $pages): ?>
					--><a class="disabled">Next →</a><!--
				<?php else: ?>
					--><a href="<?php printf('/search/%d/page/%d', $searchId, $page + 1) ?>">Next →</a><!--
			<?php endif ?>--></div>
		<?php
		endif;
	endif;
	?>

</div>

<?php $this->insert('editor'); ?>
<script src="/search.js"></script>
