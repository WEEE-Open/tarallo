<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */

$recursion = $recursion ?? false;
$floating = $floating ?? false;
// to display new inner items, set their $recursion to true
?>

<article class="item <?= $recursion ? '' : 'head' ?> new">
	<header>
		<h2><label>Code: <input id="newcode" placeholder="Automatically generated"></label></h2>
	</header>

	<?php if(!$recursion && $floating): ?>
		<div class="breadsetter"><label>Location: <input id="newparent"></label></div>
	<?php endif ?>

	<nav class="itembuttons">
		<?php if(!$recursion): ?>
			<button class="save">💾&nbsp;Save</button><button class="cancel">🔙&nbsp;Cancel</button>
		<?php endif ?>
	</nav>


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
