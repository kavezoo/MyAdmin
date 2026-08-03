<?php
/**
 * Setup view
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Setup $setup
 * @var array<string, string> $setupTypeOptions
 * @var bool $canDelete
 */
use App\Utility\SetupValue;

$this->Html->css(['pages/index'], ['block' => true]);

$type = (string)$setup->type;
$typeLabel = ($setupTypeOptions[$type] ?? $type);
$valueDisplay = SetupValue::formatForDisplay($type, $setup->value);
$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.');
$canDelete = $canDelete ?? true;
?>
<div class="row mt-3">
	<div class="col-12 col-xxl-10 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-cogs"></i> <?= h($setup->name) ?></h3>
					<code><?= h($setup->slug) ?></code>
				</div>
				<div class="float-right">
					<a href="<?= $this->Url->build($indexListUrl ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary"><i class="fa fa-times"></i></a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<dl class="record-view-dl mb-0">
					<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h($setup->id) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Name') ?></dt><dd><?= h($setup->name) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Slug') ?></dt><dd><code><?= h($setup->slug) ?></code></dd></div>
					<div class="record-view-row"><dt><?= __('Type') ?></dt><dd><?= h($typeLabel) ?></dd></div>
					<div class="record-view-row">
						<dt><?= __('Value') ?></dt>
						<dd>
							<?php if ($type === SetupValue::TYPE_BOOLEAN): ?>
								<?= ((string)$setup->value === '1')
									? '<i class="fa fa-check text-success"></i> ' . h(__('Yes'))
									: '<i class="fa fa-times text-danger"></i> ' . h(__('No')) ?>
							<?php elseif (in_array($type, [SetupValue::TYPE_JSON, SetupValue::TYPE_ARRAY], true)): ?>
								<pre class="mb-0 small bg-light border rounded p-2"><?= h(SetupValue::formatForForm($type, $setup->value)) ?></pre>
							<?php else: ?>
								<?= h($valueDisplay) ?>
							<?php endif; ?>
						</dd>
					</div>
					<?php if ($setup->description): ?>
						<div class="record-view-row"><dt><?= __('Description') ?></dt><dd><?= nl2br(h($setup->description)) ?></dd></div>
					<?php endif; ?>
					<div class="record-view-row"><dt><?= __('Position') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($setup->pos, decimals: 0)) ?></dd></div>
					<div class="record-view-row">
						<dt><?= __('Visible') ?></dt>
						<dd><?= $setup->visible
							? '<i class="fa fa-check text-success"></i> ' . h(__('Yes'))
							: '<i class="fa fa-times text-danger"></i> ' . h(__('No')) ?></dd>
					</div>
					<div class="record-view-row"><dt><?= __('Created') ?></dt><dd><?= $setup->created ? h(\App\Utility\LocaleDateParser::format($setup->created, 'datetime_short')) : '' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Modified') ?></dt><dd><?= $setup->modified ? h(\App\Utility\LocaleDateParser::format($setup->modified, 'datetime_short')) : '' ?></dd></div>
				</dl>

				<div class="record-view-footer-actions mt-4">
					<a href="<?= $this->Url->build(['action' => 'edit', $setup->id]) ?>" class="btn btn-primary"><i class="fa fa-pencil"></i> <?= __('Edit') ?></a>
					<?php if ($canDelete): ?>
						<a role="button" href="#" class="btn btn-danger ms-2" id="btn-delete" data-delete-form="#delete-form-current" data-bs-toggle="tooltip" data-bs-html="true" title="<?= h($tooltipDelete) ?>">
							<i class="fa fa-trash"></i> <?= __('Delete') ?>
						</a>
						<?= $this->Form->create(null, [
							'url' => ['action' => 'delete', $setup->id],
							'id' => 'delete-form-current',
							'class' => 'd-none',
						]) ?>
						<?= $this->Form->end() ?>
					<?php endif; ?>
					<a href="<?= $this->Url->build($indexListUrl ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary ms-2"><?= __('Back to list') ?></a>
				</div>
			</div>
		</div>
	</div>
</div>
