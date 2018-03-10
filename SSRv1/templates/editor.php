<template id="feature-edit-template-fractional-not-allowed">
	<section class="error message">Value must represent an integer number of base units<button>OK</button></section>
</template>
<template id="feature-edit-template-invalid-prefix">
	<section class="error message">Value outside range of known SI prefixes<button>OK</button></section>
</template>
<template id="feature-edit-template-empty-input">
	<section class="error message">Empty field not allowed<button>OK</button></section>
</template>
<template id="feature-edit-template-negative-input">
	<section class="error message">Negative values not allowed<button>OK</button></section>
</template>
<template id="feature-edit-template-string-start-nan">
	<section class="error message">Value must begin with a positive number<button>OK</button></section>
</template>
<template id="feature-edit-template-string-parse-nan">
	<section class="error message">Value must contain a number<button>OK</button></section>
</template>
<template id="feature-edit-template-generic-error">
	<section class="error message">Error<button>OK</button></section>
</template>
<template id="new-item-template">
	<?php $this->insert('newItem', ['recursion' => true, 'innerrecursion' => true]) ?>
</template>
<script src="/features.js"></script>
<script src="/editor.js"></script>
