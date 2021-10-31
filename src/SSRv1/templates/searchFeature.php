<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\Feature $feature */
/** @var \WEEEOpen\Tarallo\ItemCode[] $items */
/** @var int $limit */

/** @var \WEEEOpen\Tarallo\SSRv1\UltraFeature $ultra */
$ultra = $this->getUltraFeatures([$feature])[0];
$this->layout('main', ['title' => 'Search items having ' . $this->e($ultra->pname) . ' = ' . $this->e($ultra->pvalue), 'user' => $user, 'currentPage' => 'search feature', 'container' => true]);
?>
<h5>Items with "<?= $this->e($ultra->pname) ?>" equal to <?= $this->e($ultra->pvalue) ?> <small>(<?= count($items) ?>, max <?= (int) $limit ?>)</small></h5>
<?php if (count($items) > 0) : ?>
	<ul class="list-unstyled underlinelinks">
	<?php foreach ($items as $item) :
		$c = $this->e($item->getCode()); ?>
		<li><a href="/item/<?= $c ?>"><?= $c ?></li>
	<?php endforeach ?>
	</ul>
<?php endif ?>
