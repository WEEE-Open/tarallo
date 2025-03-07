<?php

/** @var \WEEEOpen\Tarallo\Product $product */
/** @var string $class */
// The site expects urlencode, not rawurlencode
$escaped = rawurlencode(strtolower($product->getFullName()));
$firstLetter = $escaped[0];
$href = "https://www.manualslib.com/$firstLetter/$escaped.html"
?>
<a class="<?= $class ?>" href="<?= $href ?>" target="_blank" role="button" rel="noreferrer"><span class="fa fa-book"></span>&nbsp;Manuals</a>
