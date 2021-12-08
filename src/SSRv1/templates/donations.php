<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var array $donations */
$this->layout('main', ['title' => 'Donations', 'user' => $user]);
?>
    <div class="container">
		<div class="row d-flex m-0 justify-content-between">
        	<h2 class="col-8 p-0">Donations</h2>
            <input class="col-2 btn btn-outline-primary" type="button" value="New">
		</div>
		<br>
		<table class="table table-striped table-borderless">
			<thead class="thead-dark">
				<tr class="text-center">
					<th>Donation Name</th>
					<th>Donation Date</th>
					<th>Completed</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($donations as $donation) : ?>
					<tr> 
						<td><a href="/donation/<?=$donation["Donation"]?>"><?=ucfirst($donation["DonationName"])?></a></td>
						<td><?=$donation["Date"]?></td>
						<td class=" text-center">
							<?php 
								if($donation["IsCompleted"])
									echo '<i class="fas fa-check"></i>';
								else
									echo '<i class="fas fa-times"></i>';
							?>
						</td>
						<td>
							<button class="btn btn-danger ml-2 delete" data-name="brand" tabindex="-1">
								<i class="fa fa-trash" role="img" aria-label="Delete"></i>
							</button>
						</td>
				</tr>
				<?php endforeach ?>
			</tbody>
    </div>
