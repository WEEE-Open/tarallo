<template id="feature-edit-template-fractional-not-allowed">
	<div class="error description">Value must represent an integer number of base units</div>
</template>
<template id="feature-edit-template-invalid-prefix">
	<div class="error description">Value outside range of known SI prefixes</div>
</template>
<template id="feature-edit-template-empty-input">
	<div class="error description">Value cannot be empty</div>
</template>
<template id="feature-edit-template-negative-input">
	<div class="error description">Negative values not allowed</div>
</template>
<template id="feature-edit-template-string-start-nan">
	<div class="error description">Value must begin with a positive number</div>
</template>
<template id="feature-edit-template-string-parse-nan">
	<div class="error description">Value must be a number</div>
</template>
<template id="feature-edit-template-meaningless-zero">
	<div class="error description">Zero has no meaning here, remove this feature if there's none of that</div>
</template>
<template id="feature-edit-template-generic-error">
	<div class="error description">Error</div>
</template>
<template id="feature-edit-template-linked-error">
	<div class="error description">Wrong value for a feature <a href="#first-error">here</a></div>
</template>
<template id="new-item-template">
	<?php
	$empty = new \WEEEOpen\Tarallo\ItemIncomplete(null);
	$empty->addFeature(new \WEEEOpen\Tarallo\BaseFeature('type'));
	$empty->addFeature(new \WEEEOpen\Tarallo\BaseFeature('working'));
	$this->insert('newItem', ['recursion' => true, 'innerrecursion' => true, 'base' => $empty])
	?>
</template>
<template id="features-select-template">
	<?php $this->insert('featuresList'); ?>
</template>
<!--suppress JSUnusedLocalSymbols -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js" integrity="sha512-AA1Bzp5Q0K1KanKKmvN/4d3IRKVlv9PYgwFPvm32nPO6QS8yH1HO7LbgB1pgiOxPtfeg5zEn2ba64MUcqJx6CA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="/static/editor.js"></script>
