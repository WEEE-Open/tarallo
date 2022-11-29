<?php
/** @var int $searchId */
/** @var int $page Current page */
/** @var int $pages Total number of pages */
?>
<div class="pagination">
	<?php if ($page === 1) :
		?><a class="disabled">← Previous</a><?php
	else :
		?><a href="<?php printf('/search/advanced/%d/page/%d', $searchId, $page - 1) ?>">← Previous</a><?php
	endif;
	for ($i = max(1, $page-max(5, 10-$pages+$page)); $i <= min($pages, $page+max(5, 11-$page)); $i++) :
		if ($i === $page) :
			?><a class="disabled current"><?=$i?></a><?php
		else :
			?><a href="<?php printf('/search/advanced/%d/page/%d', $searchId, $i) ?>"><?=$i?></a><?php
		endif;
	endfor;
	if ($page === $pages) :
		?><a class="disabled">Next →</a><?php
	else :
		?><a href="<?php printf('/search/advanced/%d/page/%d', $searchId, $page + 1) ?>">Next →</a><?php
	endif ?></div>
