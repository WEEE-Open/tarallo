<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var string $title */
$this->layout('main', ['title' => $title]) ?>

<?php if(isset($user)): ?>
<nav id="top">
	<nav>
		<span class="message">Logged in as <?= $user->getUsername() ?></span><button class="logout">Logout</button>
	</nav>
	<nav id="main">
		<ul>
			<li><a href="/">Home</a></li
			><li><a href="/add" class="pure-menu-link">Add new</a></li
			><li><a class="pure-menu-link">View</a></li
			><li><a class="pure-menu-link">Move</a></li
			><li><a class="pure-menu-link" href="/search">Search</a></li>
		</ul>
	</nav>
	<nav class="quickviewitem" style="display: none;">
		<label>Code: <input type="text"></label>
		<button>View item</button>
	</nav>
	<nav class="quickmoveitem" style="display:none;">
		<label>Sposta oggetto: <input class="from" type="text"></label>
		<label>dentro a: <input class="to" type="text"></label>
		<button>Sposta</button>
	</nav>
</nav>
<?php endif ?>

<section id="content">
	<?=$this->section('content')?>
</section>
