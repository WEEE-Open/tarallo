<?php
/** @var \WEEEOpen\Tarallo\Feature[] $features */
?>

<?php
if (count($features) > 0) :
	$ultras = $this->getUltraFeatures($features);
	foreach ($ultras as $ultra) {
		// Names of all the ultraFeatures to insert
		$ultraNames[$ultra->name] = true;
	}
	$groups = $this->getGroupedFeatures($ultras);

	foreach ($groups as $groupTitle => $group) : ?>
	<section>
		<h5><?=$groupTitle?></h5>
		<ul>
			<?php foreach ($group as $ultra) : /** @var $ultra \WEEEOpen\Tarallo\SSRv1\UltraFeature */
				$help = $this->printExplanation($ultra);
				if ($help !== '') {
					$help = $this->e($help);
					$help = "<i class=\"fa fa-question-circle ml-1\" data-tippy-content=\"$help\"></i>";
				}

				?><li class="feature-edit-<?= $ultra->name ?> feature-edit pr-4">
					<div class="name"><label for="feature-el-<?= $ultra->name ?>"><?=$ultra->pname?><?=$help?></label></div>
					<?php switch ($ultra->type) :
						case WEEEOpen\Tarallo\BaseFeature::ENUM:
							?>
						<select class="value" autocomplete="off" data-internal-name="<?= $ultra->name ?>" data-internal-type="e" data-initial-value="<?= $this->e($ultra->value, 'asTextContent')?>" id="feature-el-<?= $ultra->name ?>">
													<?php if ($ultra->value == null) :
														?><option value="" disabled selected></option><?php
													endif; ?>
							<?php foreach ($this->getOptions($ultra->name) as $optionValue => $optionName) : ?>
							<option value="<?= $optionValue ?>" <?= $optionValue === $ultra->value ? 'selected' : '' ?>><?=$this->e($optionName)?></option>
							<?php endforeach ?>
						</select>
							<?php
							break; default:
						case WEEEOpen\Tarallo\BaseFeature::STRING:
							?>
						<input class="value" type="text" data-internal-type="s" data-internal-name="<?= $ultra->name ?>" value="<?= $this->e($ultra->value) ?>" data-initial-value="<?= $ultra->value ?>" id="feature-el-<?= $ultra->name ?>"></input>
							<?php
									   break; case WEEEOpen\Tarallo\BaseFeature::INTEGER:
								?>
						<input class="value" type="number" data-internal-type="i" step="1" data-internal-name="<?= $ultra->name ?>" value="<?= $ultra->value ?>" data-initial-value="<?= $ultra->value ?>" id="feature-el-<?= $ultra->name ?>"></input>
								<?php
												  break; case WEEEOpen\Tarallo\BaseFeature::DOUBLE:
											?>
						<input class="value" type="number" data-internal-type="d" step="0.01" data-internal-name="<?= $ultra->name ?>" value="<?= $ultra->value ?>" data-initial-value="<?= $ultra->value ?>" id="feature-el-<?= $ultra->name ?>"></input>
					<?php endswitch; ?>
					<div class="controls"><button data-name="<?= $ultra->name ?>" class="btn btn-danger ml-2 delete" aria-roledescription="delete" tabindex="-1"><i class="fa fa-trash" role="img" aria-label="Delete"></i></button></div>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
		<?php
	endforeach;
endif;
?>
<section class="newfeatures pr-4">
	<h5>New features</h5>
	<ul></ul>
</section>
