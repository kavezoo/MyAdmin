<?php
/**
 * Language view — main fields (no related tabs).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Language $language
 */
$this->Html->css(['pages/index'], ['block' => true]);
?>
<div class="row">
	<div class="col-12 col-xxl-10 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-language"></i> <?= __('Language details') ?></h3>
					<code><?= h((string)$language->code) ?></code>
					— <?= h((string)$language->name) ?>
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
					<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h((string)$language->id) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Code') ?></dt><dd><code><?= h((string)$language->code) ?></code></dd></div>
					<div class="record-view-row"><dt><?= __('Name') ?></dt><dd><?= h((string)$language->name) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Endonym') ?></dt><dd><?= h((string)$language->endonim_name) ?></dd></div>
					<div class="record-view-row">
						<dt><?= __('Visible') ?></dt>
						<dd>
							<?= !empty($language->visible)
								? '<i class="fa fa-check text-success"></i> ' . h(__('Yes'))
								: '<i class="fa fa-times text-danger"></i> ' . h(__('No')) ?>
						</dd>
					</div>
					<div class="record-view-row"><dt><?= __('Position') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($language->pos, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Created') ?></dt><dd><?= $language->created ? h(\App\Utility\LocaleDateParser::format($language->created, 'datetime_short')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Modified') ?></dt><dd><?= $language->modified ? h(\App\Utility\LocaleDateParser::format($language->modified, 'datetime_short')) : '—' ?></dd></div>
				</dl>
			</div>
			<div class="card-footer">
				<div class="record-view-footer-actions">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . __('Edit'),
						['action' => 'edit', $language->id],
						['escape' => false, 'class' => 'btn btn-primary']
					) ?>
				</div>
			</div>
		</div>
	</div>
</div>
