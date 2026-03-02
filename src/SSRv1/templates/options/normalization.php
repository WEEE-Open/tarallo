<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var string|null $error */
/** @var array|null $old_data */
/** @var string[] $normalizationValues */
/** @var array $normalizationCategories */
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

<?php $old_data = $old_data ?? []; ?>


<?php $categoryLabels = [];
foreach ($normalizationCategories as $category) {
	$categoryLabels[$category['name']] = $category['printableName'];
}
?>

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
			<th>Regex Matching</th>
			<th>Output</th>
			<th>Field</th>
			<th>Comment</th>
			<th>Actions</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($normalizationValues as $row) : ?>
		<tr>
			<td class="minimized"><?= $this->e($row[0]) ?></td>
			<td><?= $this->e($row[1]) ?></td>
			<td><?= $this->e($categoryLabels[$row[2]] ?? $row[2]) ?></td>
			<td></td>
			<td>
				<form method="post">
					<input type="hidden" name="regex" value="<?= $this->e($row[0]) ?>">
					<button type="submit" name="delete" value="true" class="btn btn-danger btn-sm">Delete</button>
				</form>
			</td>
		<?php endforeach; ?>
		</tr>
		</tbody>
	</table>
	<p><small>There are <?= count($normalizationValues); ?> rows.</small></p>
	<script>
        const search = document.getElementById("search");
        const normalizationtable = document.getElementById("normalizationtable");
        const rows = normalizationtable.querySelectorAll("tbody tr");
        let debounceTimer;

        // Parse a PHP-style regex like "/foo(bar)/i" or "#foo#i" into JS RegExp
        function parsePhpRegex(patternText) {
            patternText = patternText.trim();
            if (patternText.length < 3) {
                return null;
            }

            const delimiter = patternText[0];
            // Delimiter cannot be alphanumeric, whitespace or backslash
            if (/[\w\s\\]/.test(delimiter)) {
                return null;
            }

            let lastDelimiter = -1;
            for (let i = patternText.length - 1; i > 0; i--) {
                if (patternText[i] !== delimiter) continue;
                // Ignore escaped delimiters
                let backslashes = 0;
                for (let j = i - 1; j >= 0 && patternText[j] === '\\'; j--) {
                    backslashes++;
                }
                if (backslashes % 2 === 0) {
                    lastDelimiter = i;
                    break;
                }
            }
            if (lastDelimiter <= 0) {
                return null;
            }

            const body = patternText.slice(1, lastDelimiter);
            const phpFlags = patternText.slice(lastDelimiter + 1);

            let jsFlags = "";
            if (phpFlags.includes("i")) jsFlags += "i";
            if (phpFlags.includes("m")) jsFlags += "m";
            if (phpFlags.includes("s")) jsFlags += "s";
            if (phpFlags.includes("u")) jsFlags += "u";

            try {
                return new RegExp(body, jsFlags);
            } catch (e) {
                console.warn("Invalid regex in table:", patternText, e);
                return null;
            }
        }

        function applyTest() {
            const value = search.value;

            // If empty: show all rows and restore output
            if (!value) {
                rows.forEach(row => {
                    row.classList.remove("d-none");
                    const outputCell = row.children[1];
                    if (outputCell.dataset.originalOutput !== undefined) {
                        outputCell.textContent = outputCell.dataset.originalOutput;
                    }
                });
                return;
            }

            rows.forEach(row => {
                const regexCell = row.querySelector("td.minimized");
                const outputCell = row.children[1];

                if (!regexCell || !outputCell) {
                    return;
                }

                // Store original output once
                if (outputCell.dataset.originalOutput === undefined) {
                    outputCell.dataset.originalOutput = outputCell.textContent.trim();
                }

                const originalOutput = outputCell.dataset.originalOutput;
                const patternText = regexCell.textContent;
                const regex = parsePhpRegex(patternText);
                if (!regex) {
                    row.classList.remove("d-none");
                    outputCell.textContent = originalOutput;
                    return;
                }

                const match = value.match(regex);

                if (!match) {
                    row.classList.add("d-none");
                    outputCell.textContent = originalOutput;
                } else {
                    row.classList.remove("d-none");

                    // Replace ONLY $1, $2, $3 ...
                    let evaluated = originalOutput.replace(/\$([0-9]+)/g, (_, n) => {
                        const index = Number(n);
                        return match[index] !== undefined ? match[index] : "";
                    });

                    // Show "$1 → Core i3-2125"
                    outputCell.textContent = originalOutput + " \u2192 " + evaluated;
                }
            });
        }

        function debouncedApplyTest() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(applyTest, 300);
        }

        search.addEventListener("keyup", debouncedApplyTest);
        applyTest();
	</script>


</div>

<div class="col-12">
	<h3>Normalize a new value</h3>
	<form method="post">
		<div class="form-group row">
			<label class="col col-form-label" for="regex">Regex Matching</label>
			<div class="col">
				<input type="text" class="form-control" id="regex" name="regex" required value="<?= $this->e($old_data['regex'] ?? '') ?>">
			</div>
		</div>
		<div class="form-group row">
			<label class="col col-form-label" for="output">Output pattern</label>
			<div class="col">
				<input type="text" class="form-control" id="output" name="output" value="<?= $this->e($old_data['output'] ?? '') ?>">
			</div>
		</div>
		<script>
			const regexInput = document.getElementById('regex');
			const outputInput = document.getElementById('output');
			outputInput.addEventListener('input', () => {
				regexInput.placeholder = outputInput.value;
			});
			outputInput.dispatchEvent(new Event('input'));
		</script>
		<div class="form-group row">
			<label class="col col-form-label" for="field">Fields</label>
			<div class="col">
				<select class="form-control" id="field" name="field" required>
					<?php foreach ($normalizationCategories as $category) : ?>
					<?php $selected = ($old_data['field'] ?? '') === $category['name']; ?>
					<option value="<?= $this->e($category['name']) ?>" <?= $selected ? 'selected' : '' ?>><?= $this->e($category['printableName']) ?></option>
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
