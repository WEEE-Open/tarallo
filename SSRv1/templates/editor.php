<template id="feature-edit-template-fractional-not-allowed">
	<div class="error description">Value must represent an integer number of base units</div>
</template>
<template id="feature-edit-template-invalid-prefix">
	<div class="error description">Value outside range of known SI prefixes</div>
</template>
<template id="feature-edit-template-empty-input">
	<div class="error description">Empty field not allowed</div>
</template>
<template id="feature-edit-template-negative-input">
	<div class="error description">Negative values not allowed</div>
</template>
<template id="feature-edit-template-string-start-nan">
	<div class="error description">Value must begin with a positive number</div>
</template>
<template id="feature-edit-template-string-parse-nan">
	<div class="error description">Value must contain a number</div>
</template>
<template id="feature-edit-template-generic-error">
	<div class="error description">Error</div>
</template>
<template id="new-item-template">
	<?php $this->insert('newItem', ['recursion' => true, 'innerrecursion' => true]) ?>
</template>
<template id="features-select-template">
	<?php $this->insert('featuresList'); ?>
</template>
<!--suppress JSUnusedLocalSymbols -->
<script src="/editor.js"></script>
