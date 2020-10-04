<?php
/** @var \WEEEOpen\Tarallo\User $user */

use WEEEOpen\Tarallo\SSRv1\TemplateUtilities;

/** @var array|null $imports */
$imports = $imports ?? null;
$this->layout('main', ['title' => 'Bulk imports', 'user' => $user, 'currentPage' => 'bulk import']);
?>
<div class="row">
	<div class="col">
		<table class="table table-borderless stats">
			<caption>Bulk imports</caption>
			<thead class="thead-dark">
			<tr>
				<th scope="col">BulkIdentifier</th>
				<th scope="col">Time</th>
				<th scope="col">User</th>
				<th scope="col">Type</th>
				<th style="text-align:center" scope="col">Actions</th>
			</tr>
			</thead>
			<tbody>
				<?php $jsonid=0; foreach($imports as $import): ?>
					<tr>
						<td class="align-middle"><?= $this->e($import['BulkIdentifier']) ?></td>
						<td class="align-middle"><?= $this->e($import['Time']) ?></td>
						<td class="align-middle"><?= $this->e($import['User']) ?></td>
						<td class="align-middle"><?= $this->e($import['Type']) ?></td>
						<!-- Actions Btns -->
						<td>
							<form class="text-center" method="post">
							<button class="btn btn-primary" type="button" data-toggle="collapse" data-target="<?= '#json'.$jsonid; ?>" aria-expanded="false" aria-controls="<?= '#json'.$jsonid; ?>">
									JSON
								</button>
								<button class="btn btn-success" type="submit"
										name="import" value="<?= (int) $import["Identifier"]?>">
									Import
								</button>
								<button class="btn btn-danger" type="submit"
										name="delete" value="<?= (int) $import["Identifier"]?>">
									Delete
								</button>
							</form>
						</td>
					</tr>
				<!-- Hidden JSON -->
					<tr>
						<td colspan="5">
							<div class="collapse" id="<?= 'json'.$jsonid; ?>">
								<div>
									<pre class="prettyprint"><?= TemplateUtilities::prettyPrint($import['JSON']); ?></pre>
								</div>
							</div>
						</td>
					</tr>
				<?php $jsonid++; endforeach ?>
			</tbody>
		</table>
	</div>
</div>
