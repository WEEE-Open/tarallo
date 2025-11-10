<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var array|null $imports */
$imports = $imports ?? null;
$this->insert('bulk::menu', ['currentPage' => 'import']);
$this->layout('main', ['title' => 'Bulk imports', 'user' => $user, 'currentPage' => 'bulk import', 'container' => true]);

$typize = function ($type) {
	if ($type === 'I') {
		return '<abbr title="Item">I</abbr>';
	} elseif ($type === 'P') {
		return '<abbr title="Product">P</abbr>';
	} else {
		return $this->e($type);
	}
};

$jsonid = 0;
?>
<!--<h2>Bulk imports</h2>-->
<?php foreach ($imports as $bulkIdentifier => $import) : ?>
<div class="row">
	<div class="col">
		<table class="table table-borderless stats">
			<caption><?= $this->e($bulkIdentifier) ?></caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">Type</th>
				<th scope="col">Item</th>
				<th scope="col">Time and User</th>
				<th style="text-align:center" scope="col">Actions</th>
			</tr>
			</thead>
			<tbody>
				<?php foreach ($import as $line) : ?>
					<tr>
						<td class="align-middle"><?= $typize($line['Type']) ?></td>
						<td class="align-middle"><?= $line["Error"] ? '<i class="fa fa-exclamation-triangle text-danger"></i>&nbsp;' : '' ?><?= $this->e($line['SuperSummary'][0]) ?><?= $line['SuperSummary'][0] !== '' && $line['SuperSummary'][1] !== '' ? ' ' : '' ?><small class="text-muted"><?php if ($line['Exists']) {
							echo "<a class=\"text-muted\" href=\"{$line['EncodedUrl']}\">";
												 } ?><?= $this->e($line['SuperSummary'][1]) ?><?php if ($line['Exists']) {
												 echo '</a>';
												 } ?></small><?php if ($line['Exists']) {
												 echo '&nbsp;<span title="Duplicate"><i class="fa fa-ban"></i></span>';
												 } ?></td>
						<td class="align-middle">
							<div><?= $this->e($line['Time']) ?></div>
							<div><small class="text-muted">by <?= $this->e($line['User']) ?></small></div>
						</td>
						<!-- Actions Btns -->
						<td>
							<form class="text-center" method="post">
							<button class="btn btn-primary" type="button" data-toggle="collapse" data-target="<?= '#bulkjson' . $jsonid; ?>" aria-expanded="false" aria-controls="<?= '#json' . $jsonid; ?>">
									JSON
								</button>
								<button class="btn <?= $line["Exists"] ? 'btn-outline-success' : 'btn-success' ?>" type="submit"
										name="import" value="<?= (int) $line["Identifier"]?>" <?= $line["Error"] ? 'disabled' : '' ?>>
									Import
								</button>
								<button class="btn <?= $line["Exists"] ? 'btn-danger' : 'btn-outline-danger' ?>" type="submit"
										name="delete" value="<?= (int) $line["Identifier"]?>">
									Delete
								</button>
							</form>
						</td>
					</tr>
				<!-- Hidden JSON -->
					<tr>
						<td colspan="5">
							<div class="collapse" id="<?= 'bulkjson' . $jsonid; ?>">
								<div>
									<pre class="prettyprint"><?= $this->prettyPrintJson($line['JSON']); ?></pre>
								</div>
							</div>
						</td>
					</tr>
					<?php $jsonid++;
				endforeach ?>
			</tbody>
		</table>
	</div>
</div>
<?php endforeach; ?>