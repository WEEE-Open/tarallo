<?php
/** @var string $title */
/** @var string $lang */
/** @var \WEEEOpen\Tarallo\Server\User $user */
?>
<nav>
	<span class="message">Logged in as <?= $user->getUsername() ?></span><button id="logout">Logout</button>
</nav>
<nav id="main" class="menu">
	<ul>
		<li><a href="/">Home</a></li
		><li><a href="/add">New</a></li
		><li><a role="button" tabindex="0" class="quick" data-toggle="view">View</a></li
		><li><a role="button" tabindex="0" class="quick" data-toggle="move">Move</a></li
		><li><a href="/search">Search</a></li
		><li><a href="/stats">Stats</a></li
        ><li><a href="/move2">Move.2</a></li
		><li><a href="/options">Options</a></li>
	</ul>
</nav>
<nav class="quick view">
	<label>Code:<input type="text"></label
	><button>View item</button>
</nav>
<nav class="quick move">
	<label>Move item:<input class="from" type="text"></label
	><label>into:<input class="to" type="text"></label
	><button>Move</button
	><span class="error message">Error</span
	><span class="success message"><a href="#">Ok</a></span
	><div class="warning message long">Fail</div>
</nav>
<script src="/menu.js"></script>
