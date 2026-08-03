<?php
/**
 * Country view — read-only (i18n names are for display locale only, not listed here).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Country $country
 */
$this->Html->css(['pages/index'], ['block' => true]);
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-eye"></i> <?= __('Country details') ?></h3>
					<?= __('View the selected record (read-only).') ?>
				</div>
				<div class="float-right">
					<a role="button" href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<dl class="record-view-fields mb-0">
					<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h($country->id) ?></dd></div>
					<div class="record-view-row"><dt><?= __('ISO') ?></dt><dd><code><?= h($country->iso2) ?></code></dd></div>
					<div class="record-view-row"><dt><?= __('Name') ?></dt><dd><?= h($country->name) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Primary locale') ?></dt><dd><code><?= h($country->locale) ?></code></dd></div>
					<div class="record-view-row"><dt><?= __('Continent') ?></dt><dd><?= h($country->continent->name ?? '') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Visible') ?></dt><dd><?= $country->visible ? __('Yes') : __('No') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Position') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($country->pos, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Users') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::formatCount($country->user_count, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Created') ?></dt><dd><?= $country->created ? h(\App\Utility\LocaleDateParser::format($country->created, 'datetime_short')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Modified') ?></dt><dd><?= $country->modified ? h(\App\Utility\LocaleDateParser::format($country->modified, 'datetime_short')) : '—' ?></dd></div>
				</dl>

				<div class="record-view-footer-actions mt-4">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . __('Edit'),
						['action' => 'edit', $country->id],
						['escape' => false, 'class' => 'btn btn-primary', 'role' => 'button']
					) ?>
				</div>
			</div>
		</div>
	</div>
</div>
