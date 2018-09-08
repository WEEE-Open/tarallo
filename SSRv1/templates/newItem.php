<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var \WEEEOpen\Tarallo\Server\Item $copy */

$recursion = $recursion ?? false; // Placed inside another item (new or existing)
$innerrecursion = $innerrecursion ?? false; // Placed inside another NEW item

if(!$innerrecursion && !$recursion) {
	$this->layout('main', ['title' => 'New item', 'user' => $user, 'itembuttons' => true]);
}

if(isset($copy)) {
	$features = $copy->getFeatures();
	$subitems = $copy->getContents();
} else {
	$subitems = [];
	$features = [];
}

// to display new inner items, set their $recursion and $innerrecursion to true
?>

<article class="item new editing <?=$recursion ? '' : 'root'?> <?=$innerrecursion ? '' : 'head'?>">
	<header>
		<h2><label>Code: <input class="newcode" placeholder="Automatically generated"></label></h2>
		<?php if(isset($copy)): ?>
			<div class="info message">ℹ️&nbsp;This is a copy of <span class="code"><?= $copy->getCode() ?></span>, remember to change serial numbers, notes, working status, etc...</div>
		<?php unset($noticeFeature); endif; ?>
	</header>

	<nav class="itembuttons">
		<?php if(!$innerrecursion): ?><button class="save">💾&nbsp;Save</button><button class="cancel">🔙&nbsp;Cancel</button><?php else: ?><button class="removenew">❌&nbsp;Delete</button><?php endif ?><button class="addnew">🆕&nbsp;More</button>
	</nav>


	<?php if(!$innerrecursion && !$recursion): ?>
		<div class="setlocation"><label>Location: <input id="newparent"></label></div>
	<?php endif ?>

	<section class="own features editing">
		<?php
		$this->insert('featuresEdit', ['features' => $features]);
		?>
	</section>

	<section class="addfeatures">
		<label>Feature:
			<select class="allfeatures">
			</select></label>
		<button>Add</button>
	</section>

	<section class="subitems">
		<?php
			// Empty if not cloning
			foreach($subitems as $subitem) {
				$this->insert('newItem', ['recursion' => true, 'innerrecursion' => true, 'copy' => $subitem]);
			}
		?>
	</section>
</article>
<?php if(!$recursion) {
	?><script>const activate = true;</script><?php
	$this->insert('editor');
} ?>
