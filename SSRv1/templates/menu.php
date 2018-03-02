<?php
/** @var string $title */
/** @var string $lang */
/** @var \WEEEOpen\Tarallo\Server\User $user */
?>
<nav>
	<span class="message">Logged in as <?= $user->getUsername() ?></span><button class="logout">Logout</button>
</nav>
<nav id="main">
	<ul>
		<li><a href="/">Home</a></li
		><li><a href="/add">Add new</a></li
		><li><a class="quick" data-toggle="view">View</a></li
		><li><a class="quick" data-toggle="move">Move</a></li
		><li><a href="/search">Search</a></li>
	</ul>
</nav>
<nav class="quick view">
	<label>Code: <input type="text"></label>
	<button>View item</button>
</nav>
<nav class="quick move">
	<label>Move item: <input class="from" type="text"></label>
	<label>into: <input class="to" type="text"></label>
	<button>Move</button>
</nav>
<script src="/main.js"></script>
