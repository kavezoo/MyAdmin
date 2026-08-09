<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CompetitionTextTemplate $competitionTextTemplate
 * @var string $countryLabel
 */
$this->Html->css(['pages/index'], ['block' => true]);
$yes = '<i class="fa fa-check text-success"></i> ' . h(__('Yes'));
$no = '<i class="fa fa-times text-danger"></i> ' . h(__('No'));
?>
<div class="row">
	<div class="col-12 col-xxl-10 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-file-text-o"></i> <?= __('Competition text template details') ?></h3>
					<?= h((string)$competitionTextTemplate->label) ?>
				</div>
				<div class="float-right">
					<a role="button" href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary"><i class="fa fa-times"></i></a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<div class="alert alert-info py-2"><?= __('Placeholders such as {{name}} and {{competition_datetime}} are resolved when the competition is displayed.') ?></div>
				<dl class="row record-view-fields mb-0">
					<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h((string)$competitionTextTemplate->id) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Country') ?></dt><dd><?= h($countryLabel ?? \App\Utility\AdminCountry::label((int)$competitionTextTemplate->country_id)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Label') ?></dt><dd><?= h((string)$competitionTextTemplate->label) ?></dd></div>
					<div class="record-view-row">
						<dt><?= __('Description') ?></dt>
						<dd><?= $this->element('admin/html_content', ['html' => (string)($competitionTextTemplate->description ?? '')]) ?></dd>
					</div>
					<div class="record-view-row"><dt><?= __('Enabled') ?></dt><dd><?= !empty($competitionTextTemplate->enabled) ? $yes : $no ?></dd></div>
					<div class="record-view-row"><dt><?= __('Visible') ?></dt><dd><?= !empty($competitionTextTemplate->visible) ? $yes : $no ?></dd></div>
					<div class="record-view-row"><dt><?= __('Position') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($competitionTextTemplate->pos, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Created') ?></dt><dd><?= $competitionTextTemplate->created ? h(\App\Utility\LocaleDateParser::format($competitionTextTemplate->created, 'datetime_short')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Modified') ?></dt><dd><?= $competitionTextTemplate->modified ? h(\App\Utility\LocaleDateParser::format($competitionTextTemplate->modified, 'datetime_short')) : '—' ?></dd></div>
				</dl>
			</div>
			<div class="card-footer">
				<div class="record-view-footer-actions">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . __('Edit'),
						['action' => 'edit', $competitionTextTemplate->id],
						['escape' => false, 'class' => 'btn btn-primary']
					) ?>
				</div>
			</div>
		</div>
	</div>
</div>
