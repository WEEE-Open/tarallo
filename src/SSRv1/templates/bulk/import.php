<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var array|null $imports */
$imports = $imports ?? null;
$this->layout('main', ['title' => 'Import from Peracotta', 'user' => $user, 'currentPage' => 'bulk import']);
?>
<div class="row">
	<div class="col">
		<table class="table table-borderless stats">
			<caption>Bulk imports from PERACOTTA</caption>
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
				<?php $id=0; foreach($imports as $import): ?>
					<tr>
						<td class="align-middle"><?php echo $import['BulkIdentifier']?></td>
						<td class="align-middle"><?php echo $import['Time']?></td>
						<td class="align-middle"><?php echo $import['User']?></td>
						<td class="align-middle"><?php echo $import['Type']?></td>
						<!-- Actions Btns -->
						<td>
							<form class="text-center" method="post">
								<button class="btn btn-primary" type="button" data-toggle="collapse" data-target=<?php echo '"#json'.$id.'"'; ?> aria-expanded="false" aria-controls=<?php echo '"json'.$id.'"'; ?>>
									JSON
								</button>
								<button class="btn btn-success" type="submit"
										name="import" value=<?php echo '"'.$import["Identifier"].'"' ?>>
									Import
								</button>
								<button class="btn btn-danger" type="submit"
										name="delete" value=<?php echo '"'.$import['Identifier'].'"' ?>>
									Delete
								</button>
							</form>
						</td>
					</tr>
				<!-- Hidden JSON -->
					<tr>
						<td colspan="5">
							<div class="collapse" id=<?php echo '"json'.$id.'"'; ?>>
								<div>
									<pre class="prettyprint"><?php echo \WEEEOpen\Tarallo\Database\BulkDAO::prettyPrint($import['JSON']);?></pre>
								</div>
							</div>
						</td>
					</tr>
				<?php $id++; endforeach ?>
			</tbody>
		</table>
	</div>
</div>
