<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var \WEEEOpen\Tarallo\Donations[] $donations */
$this->layout('main', ['title' => 'Donations', 'user' => $user]);
?>

<!doctype html>
<html lang="<?= /* $lang */ 'en-US' ?>">
<head>
	<meta charset="utf-8">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="shortcut icon" type="image/png" href="/static/favicon.png"/>
	<link rel="stylesheet" href="/static/bootstrap.custom.min.css">
	<link rel="stylesheet" href="https://unpkg.com/bootstrap-table@1.18.3/dist/bootstrap-table.min.css">
	<script src="https://cdn.jsdelivr.net/npm/jquery@3.4.1/dist/jquery.min.js"></script>
	<script src="https://unpkg.com/@popperjs/core@2"></script>
	<script src="https://unpkg.com/tippy.js@6"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@4/dist/js/bootstrap.min.js"></script>
	<script src="https://cdn.jsdelivr.net/gh/google/code-prettify@master/loader/run_prettify.js"></script>
	<script src="https://unpkg.com/bootstrap-table@1.18.3/dist/bootstrap-table.min.js"></script>
</head>
<body>
    <div class="container">
		<div class="row">
        	<h2 class="col-8">Donations</h2>
				<input class="col btn btn-secondary" type="button" value="New">
		</div>
		<br>
		<table class="table table-striped table-borderless">
			<thead class="thead-dark">
				<tr>
					<th class="col-4">Donation Recipient</th>
					<th class="col">Donation Date</th>
					<th class="col">Completed</th>
					<th class="col"></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($donations as $donation) : ?>
					<tr> 
						<td class="col-4"><a href="/donation/<?=$donation["Donation"]?>"><?=ucfirst($donation["DonationName"])?></a></td>
						<td class="col"><?=$donation["Date"]?></td>
						<td class="col text-center">
							<?php 
								if($donation["IsCompleted"])
									echo '<i class="fas fa-check"></i>';
								else
									echo '<i class="fas fa-times"></i>';
							?>
						</td>
						<td class="col">
							<button class="btn btn-danger ml-2 delete" data-name="brand" tabindex="-1">
								<i class="fa fa-trash" role="img" aria-label="Delete"></i>
							</button>
						</td>
				</tr>
				<?php endforeach ?>
			</tbody>
    </div>
</body>