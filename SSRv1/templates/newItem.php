<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */

$recursion = $recursion ?? false; // Placed inside another item (new or existing)
$innerrecursion = $innerrecursion ?? false; // Placed inside another NEW item

if(!$innerrecursion && !$recursion) {
	$this->layout('main', ['title' => 'New item', 'user' => $user, 'itembuttons' => true]);
}

// to display new inner items, set their $recursion and $innerrecursion to true
?>

<article class="item new editing <?=$recursion ? '' : 'root'?> <?=$innerrecursion ? '' : 'head'?>">
	<header>
		<h2><label>Code: <input class="newcode" placeholder="Automatically generated"></label></h2>
	</header>

	<nav class="itembuttons">
		<?php if(!$innerrecursion): ?><button class="save">💾&nbsp;Save</button><button class="cancel">🔙&nbsp;Cancel</button><?php else: ?><button class="removenew">❌&nbsp;Delete</button><?php endif ?><button class="addnew">🆕&nbsp;More</button>
	</nav>


	<?php if(!$innerrecursion && !$recursion): ?>
		<div class="setlocation"><label>Location: <input id="newparent"></label></div>
	<?php endif ?>

	<section class="own features editing">
		<?php
		$this->insert('featuresEdit', ['features' => []]);
		?>
	</section>

	<section class="addfeatures">
		<label>Feature:
			<select>
				<?php $this->insert('allFeatures') ?>
			</select></label>
		<button>Add</button>
	</section>

	<section class="subitems">

	</section>
</article>
<?php if(!$recursion) {
	$this->insert('editor');
} ?>
