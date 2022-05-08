<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var string|null $error */
/** @var array $normalizationValues */
/** @var string[] $normalizationCategories */
/** @var bool $apcuEnabled */
$this->layout('main', ['title' => 'Options', 'user' => $user, 'currentPage' => 'options', 'container' => true]);
$this->insert('options::menu', ['currentPage' => 'normalization']);
?>
<?php $locationNames = [
	'DefaultRamLocation' => 'Default location for RAM stats',
	'DefaultCpuLocation' => 'Default location for CPU stats',
	'DefaultLabLocation' => 'Lab name, for stats',
];
?>

<?php if ($error !== null) : ?>
<div class="col-12">
	<p class="alert alert-danger" role="alert"><?= $this->e($error) ?></p>
</div>
<?php endif; ?>

<div class="col-12">
	<h2>Normalization values</h2>
	<div class="form-group row">
		<label class="col col-form-label text-right" for="search">Test normalization</label>
		<div class="col">
			<input type="text" class="form-control" id="search">
		</div>
	</div>
	<table class="table table-borderless table-responsive-lg" id="normalizationtable">
		<caption class="sr-only">List of normalization values</caption>
		<thead class="thead-dark">
		<tr>
			<th>Minimized</th>
			<th>Value</th>
			<th>Category</th>
			<th>Comment</th>
			<th>Actions</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($normalizationValues as $row) : ?>
		<tr>
			<td class="minimized"><?= $this->e($row[0]) ?></td>
			<td><?= $this->e($row[1]) ?></td>
			<td><?= $this->e($row[2]) ?></td>
			<td></td>
			<td>
				<form method="post">
					<input type="hidden" name="minimized" value="<?= $this->e($row[0]) ?>">
					<button type="submit" name="delete" value="true" class="btn btn-danger btn-sm">Delete</button>
				</form>
			</td>
		<?php endforeach; ?>
		</tr>
		</tbody>
	</table>
	<p><small>There are <?= count($normalizationValues); ?> rows.</small></p>
	<script>
		let search = document.getElementById("search");
		let normalizationtable = document.getElementById("normalizationtable");
		let debounceTimer;

		search.addEventListener('keyup', () => {
			clearTimeout(debounceTimer);

			debounceTimer = setTimeout(() => {
				let minimized = search.value.toLowerCase().replace(/[^a-z0-9&]/, '');
				for(let td of normalizationtable.querySelectorAll('td.minimized')) {
					if(minimized === '' || td.textContent === minimized) {
						td.parentElement.classList.remove("d-none");
					} else {
						td.parentElement.classList.add("d-none");
					}
				}
			}, 300);
		});
	</script>
</div>

<div class="col-12">
	<h3>Normalize a new value</h3>
	<form method="post">
		<div class="form-group row">
			<label class="col col-form-label" for="value">Normalized value</label>
			<div class="col">
				<input type="text" class="form-control" id="value" name="value" required>
			</div>
		</div>
		<div class="form-group row">
			<label class="col col-form-label" for="wrong">String to replace</label>
			<div class="col">
				<input type="text" class="form-control" id="wrong" name="wrong">
			</div>
		</div>
		<script>
			let wrong = document.getElementById('wrong');
			let value = document.getElementById('value');
			value.addEventListener('change', () => {
				wrong.placeholder = value.value;
			});
		</script>
		<div class="form-group row">
			<label class="col col-form-label" for="category">Category</label>
			<div class="col">
				<select class="form-control" id="category" name="category" required>
					<?php foreach ($normalizationCategories as $category) : ?>
					<option value="<?= $this->e($category) ?>"><?= $this->e($category) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<div class="form-group row">
			<div class="col">
				<button class="btn btn-primary" type="submit" name="new" value="true">Create</button>
			</div>
		</div>
	</form>
</div>

<p class="col-12">APCu status: <?= $apcuEnabled ? 'enabled' : '<strong>disabled!</strong>' ?></p>
