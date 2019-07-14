<?php
/** @var \WEEEOpen\Tarallo\Server\Item|null $base */
/** @var bool $recursion */
/** @var bool $innerrecursion */
/** @var string[] $subitems */
/** @var string[] $featuresEmpty */

$base = $base ?? null;
if($base === null) {
	$subitems = [];
	$features = [];
} else {
	$subitems = $base->getContent();
	$features = $base->getFeatures();
}

// to display new inner items, set their $recursion and $innerrecursion to true
// .head is the beginning of the edit/new subtree, .root is the root of the entire tree
?>

<article class="item new editing <?=$recursion ? '' : 'root'?> <?=$innerrecursion ? '' : 'head'?>">
	<header>
		<h2><label>Code: <input class="newcode" placeholder="Automatically generated"></label></h2>
		<?php if(isset($base) && $base->hasCode()): ?>
			<div class="info message">ℹ️&nbsp;This is a copy of <span class="code"><?= $base->getCode() ?></span>, remember to change serial numbers, notes, working status, etc...</div>
		<?php unset($noticeFeature); endif; ?>
	</header>

	<nav class="itembuttons">
		<?php if(!$innerrecursion): ?><button class="save">💾&nbsp;Save</button><button class="cancel">🔙&nbsp;Cancel</button><?php else: ?><button class="removenew">❌&nbsp;Delete</button><?php endif ?><button class="addnew">🆕&nbsp;More</button>
	</nav>

	<?php if(!$innerrecursion && !$recursion): ?>
		<section class="setlocation"><label>Location: <input id="newparent"></label></section>
	<?php endif ?>

	<section class="own features editing">
		<?php
		$this->insert('featuresEdit', ['features' => $features, 'featuresEmpty' => $featuresEmpty]);
		?>
	</section>

	<section class="addfeatures">
		<label>Feature:
			<select class="allfeatures">
			</select></label>
		<button>Add</button>
	</section>

    <nav class="itembuttons secondary">
        <button class="removeemptyfeatures" title="Remove empty features">💥&nbsp;Clean</button>
        <button class="addnew">🆕&nbsp;More</button>
    </nav>

	<section class="subitems">
		<?php
			// Used when cloning, empty otherwise
			foreach($subitems as $subitem) {
				$this->insert('newItem', ['recursion' => true, 'innerrecursion' => true, 'base' => $subitem, 'featuresEmpty' => $featuresEmpty]);
			}
		?>
	</section>
</article>
<?php if(!$innerrecursion) {
	?><script>const activate = true;</script><?php
	$this->insert('editor');
} ?>
