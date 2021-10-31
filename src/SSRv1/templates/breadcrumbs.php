<?php
/** @var \WEEEOpen\Tarallo\Item $item */
?>
<nav class="breadbox">
	<ul class="breadcrumbs">
		<?php foreach ($item->getPath() as $piece) :
			?><li><a href="/item/<?=$this->u($piece)?>"><?=$this->e($piece)?></a></li><?php
		endforeach; ?>
	</ul>
</nav>
