<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var int $searchId */
/** @var int $page */
/** @var \WEEEOpen\Tarallo\Server\Item[]|null $results */
$this->layout('main', ['title' => 'Search', 'user' => $user, 'itembuttons' => true]);
?>

<nav>SEARCH BUTTONS GO HERE</nav>

<?php
if($searchId) {
	if(empty($results)) {
		echo 'Nothing found :(';
	} else {
		foreach($results as $item) {
			$this->include('item', ['item' => $item]);
		}
	}
}
?>
