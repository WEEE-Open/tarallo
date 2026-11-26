<?php
/** @var array[] $activeDonations */
/** @var string|null $divclass */
$divclass = $divclass ?? '';
?>
<?php if (!empty($activeDonations)) : ?>
<div class="row mb-3">
	<div class="stats list col-12 py-3 border border-danger rounded bg-white"<?= $divclass ?>>
		<div class="d-flex justify-content-between align-items-center mb-2">
			<h4 class="mb-0"><span class="fa fa-gift mr-2 text-danger"></span>Active donations</h4>
			<a href="/donation" class="small">View all donations</a>
		</div>
		<?php foreach ($activeDonations as $donation) : ?>
			<div class="border-top pt-2 mt-2">
				<div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
					<div>
						<a href="/donation/<?= (int) $donation['id'] ?>" class="font-weight-bold h5 mb-0">
							<?= $this->e($donation['name']) ?>
						</a>
						<?php if (!empty($donation['location'])) : ?>
							<span class="badge badge-secondary ml-2"><?= $this->e($donation['location']) ?></span>
						<?php endif ?>
						<?php if (!empty($donation['date'])) : ?>
							<small class="text-muted ml-2"><?= $this->e($donation['date']) ?></small>
						<?php endif ?>
					</div>
					<small class="text-muted">
						<?= (int) $donation['totalItems'] ?> items &middot; <?= (int) $donation['progress'] ?>% complete
					</small>
				</div>
				<?php if (!empty($donation['items'])) : ?>
					<div class="row">
						<?php foreach ($donation['items'] as $item) : ?>
							<div class="col-6 col-sm-4 col-md-2 mb-2">
								<div class="card h-100 shadow-sm">
									<div class="card-body p-2 d-flex flex-column justify-content-between">
										<a href="/item/<?= $this->e(rawurlencode($item['code'])) ?>" class="code font-weight-bold text-truncate">
											<?= $this->e($item['code']) ?>
										</a>
										<div class="mt-2">
											<div class="progress mb-1" style="height: 6px;">
												<div class="progress-bar <?= $item['progress'] === 100 ? 'bg-success' : 'bg-info' ?>"
													role="progressbar"
													style="width: <?= (int) $item['progress'] ?>%;"
													aria-valuenow="<?= (int) $item['progress'] ?>"
													aria-valuemin="0"
													aria-valuemax="100"></div>
											</div>
											<small class="text-muted d-block text-truncate" title="<?= $this->e($item['nextTask'] ?? 'All tasks done') ?>">
												<?php if ($item['nextTask'] !== null) : ?>
													Next: <?= $this->e($item['nextTask']) ?>
												<?php else : ?>
													<span class="text-success">Done</span>
												<?php endif ?>
											</small>
										</div>
									</div>
								</div>
							</div>
						<?php endforeach ?>
						<?php if (!empty($donation['hasMore'])) : ?>
							<div class="col-6 col-sm-4 col-md-2 mb-2">
								<a href="/donation/<?= (int) $donation['id'] ?>" class="card h-100 shadow-sm text-decoration-none text-dark">
									<div class="card-body p-2 d-flex flex-column align-items-center justify-content-center">
										<span class="h4 mb-0">&hellip;</span>
										<small class="text-muted">+<?= (int) ($donation['totalItems'] - count($donation['items'])) ?> more</small>
									</div>
								</a>
							</div>
						<?php endif ?>
					</div>
				<?php else : ?>
					<small class="text-muted"><i>No items in this donation yet.</i></small>
				<?php endif ?>
			</div>
		<?php endforeach ?>
	</div>
</div>
<?php endif ?>
