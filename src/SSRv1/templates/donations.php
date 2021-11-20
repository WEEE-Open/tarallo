<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var array $donations */
$this->layout('main', ['title' => 'Donations', 'user' => $user]);
?>
    <div class="container">
		<div class="row">
        	<h2 class="col-8">Donations</h2>
				<input class="col btn btn-outline-primary" type="button" value="New">
		</div>
		<br>
		<table class="table table-striped table-borderless">
			<thead class="thead-dark">
				<tr>
					<th class="col-4">Donation Name</th>
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
