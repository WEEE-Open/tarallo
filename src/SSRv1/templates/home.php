<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var string[][] $todos */
/** @var array[] $missingSmartOrSurfaceScan */
/** @var array[] $toTest */
$this->layout('main', ['title' => 'Home', 'user' => $user]);
//date_default_timezone_set('Europe/Rome');
?>

<h2>Useful key combinations</h2>
<p>In editor mode, select a feature and...</p>
<ul class="list-unstyled">
	<li><kbd>Ctrl</kbd> + <kbd>Alt</kbd> + <kbd>Z</kbd> delete feature</li>
	<li><kbd>Ctrl</kbd> + <kbd>Alt</kbd> + <kbd>U</kbd> CONVERT TO UPPERCASE</li>
	<li><kbd>Ctrl</kbd> + <kbd>Alt</kbd> + <kbd>L</kbd> convert to lowercase</li>
	<li><kbd>Ctrl</kbd> + <kbd>Alt</kbd> + <kbd>Y</kbd> Convert To Title Case</li>
</ul>
<?php $this->insert('info::todo', ['todos' => $todos, 'missingSmartOrSurfaceScan' => $missingSmartOrSurfaceScan, 'toTest' => $toTest, 'included' => true]) ?>
