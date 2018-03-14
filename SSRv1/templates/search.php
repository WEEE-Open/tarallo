<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var int|null $searchId */
/** @var int $page */
/** @var \WEEEOpen\Tarallo\Server\Item[]|null $results */
$this->layout('main', ['title' => 'Search', 'user' => $user, 'itembuttons' => true]);
?>

<nav id="searcharea">
	<nav id="searchbuttons">
		<label>Filter by<button>Code</button><button>Features</button><button>Location</button><button>Ancestor</button><button>Sort</button></label>
		<!--Use an URL parameter: <button>Depth</button>-->
	</nav>
	<!--<label><input type="checkbox" class="usecompactview" checked="checked">Usa ComputerView (se possibile)</label>-->
	<div id="searchcontrols"></div>
	<button id="searchbutton"><?=$searchId === null ? 'Search' : 'Refine'?></button>
</nav>
<div class="results contents"></div>

<?php
if($searchId):
	?>

	<?php if(empty($results)): ?>
	<p>Nothing found :(</p>
<?php else:
	foreach($results as $item) {
		$this->insert('item', ['item' => $item]);
	} ?>
	<div class="pagination">TODO</div>
<?php
endif;
	?>

<?php
endif;
?>
