<?php
/**
 * County view.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\County $county
 * @var string $countryLabel
 * @var bool $canDelete
 */
$this->Html->css(['pages/index'], ['block' => true]);
$countryLabel = (string)($countryLabel ?? \App\Utility\AdminCountry::label((int)$county->country_id));
$canDelete = (bool)($canDelete ?? false);
?>
<div class="row">
	<div class="col-12 col-xxl-10 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-map"></i> <?= __('County details') ?></h3>
					<?= h((string)$county->name) ?>
				</div>
				<div class="float-right">
					<a role="button" href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<dl class="row record-view-fields mb-0">
					<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h((string)$county->id) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Country') ?></dt><dd><?= h($countryLabel) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Name') ?></dt><dd><?= h((string)$county->name) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Short name') ?></dt><dd><?= h((string)$county->shortname) ?: '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Capital city') ?></dt><dd><?= h((string)$county->capitalcity) ?: '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Region') ?></dt><dd><?= h((string)$county->region) ?: '—' ?></dd></div>
					<div class="record-view-row">
						<dt><?= __('Visible') ?></dt>
						<dd>
							<?= !empty($county->visible)
								? '<i class="fa fa-check text-success"></i> ' . h(__('Yes'))
								: '<i class="fa fa-times text-danger"></i> ' . h(__('No')) ?>
						</dd>
					</div>
					<div class="record-view-row"><dt><?= __('Position') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($county->pos, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Created') ?></dt><dd><?= $county->created ? h(\App\Utility\LocaleDateParser::format($county->created, 'datetime_short')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Modified') ?></dt><dd><?= $county->modified ? h(\App\Utility\LocaleDateParser::format($county->modified, 'datetime_short')) : '—' ?></dd></div>
				</dl>
			</div>
			<div class="card-footer">
				<div class="record-view-footer-actions">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . __('Edit'),
						['action' => 'edit', $county->id],
						['escape' => false, 'class' => 'btn btn-primary']
					) ?>
				</div>
			</div>
		</div>
	</div>
</div>
