<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */

$recursion = $recursion ?? false; // Placed inside another item (new or existing)
$innerrecursion = $innerrecursion ?? false; // Placed inside another NEW item

if(!$innerrecursion && !$recursion) {
	$this->layout('main', ['title' => 'New item', 'user' => $user, 'itembuttons' => true]);
}

// to display new inner items, set their $recursion and $innerrecursion to true
?>

<article class="item <?= $innerrecursion ? '' : 'head' ?> new">
	<header>
		<h2><label>Code: <input id="newcode" placeholder="Automatically generated"></label></h2>
	</header>

	<nav class="itembuttons">
		<?php if(!$innerrecursion && !$recursion): ?>
			<button class="save">💾&nbsp;Save</button><button class="cancel">🔙&nbsp;Cancel</button>
		<?php endif ?>
	</nav>


	<?php if(!$innerrecursion && !$recursion): ?>
		<div class="setlocation"><label>Location: <input id="newparent"></label></div>
	<?php endif ?>

	<section class="own features editing">
		<?php
		$this->insert('featuresEdit', ['features' => []]);
		?>
	</section>

	<section class="add">
		<label>Feature:
			<select>
			<?php $this->insert('allFeatures') ?>
			</select></label>
		<button>Add</button>
	</section>

	<script src="/features.js"></script>
	<script src="/editor.js"></script>

	<section class="subitems">

	</section>
</article>
