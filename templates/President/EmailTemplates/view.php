<?php
/**
 * Email template view (President).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\EmailTemplate $emailTemplate
 * @var string $languageLabel
 * @var string $slugLabel
 */
$this->Html->css(['pages/index'], ['block' => true]);
$languageLabel = (string)($languageLabel ?? '');
$slugLabel = (string)($slugLabel ?? (string)$emailTemplate->slug);
?>
<div class="row">
	<div class="col-12 col-xxl-10 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-envelope"></i> <?= __('Email template details') ?></h3>
					<?= h((string)$emailTemplate->name) ?>
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
					<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h((string)$emailTemplate->id) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Language') ?></dt><dd><?= h($languageLabel) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Template') ?></dt><dd><?= h($slugLabel) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Name') ?></dt><dd><?= h((string)$emailTemplate->name) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Subject') ?></dt><dd><?= h((string)$emailTemplate->subject) ?></dd></div>
					<div class="record-view-row">
						<dt><?= __('HTML body') ?></dt>
						<dd><pre class="mb-0 small font-monospace text-wrap"><?= h((string)$emailTemplate->body_html) ?></pre></dd>
					</div>
					<div class="record-view-row">
						<dt><?= __('Text body') ?></dt>
						<dd><pre class="mb-0 small font-monospace text-wrap"><?= h((string)$emailTemplate->body_text) ?></pre></dd>
					</div>
					<div class="record-view-row">
						<dt><?= __('Enabled') ?></dt>
						<dd>
							<?= !empty($emailTemplate->enabled)
								? '<i class="fa fa-check text-success"></i> ' . h(__('Yes'))
								: '<i class="fa fa-times text-danger"></i> ' . h(__('No')) ?>
						</dd>
					</div>
					<div class="record-view-row">
						<dt><?= __('Visible') ?></dt>
						<dd>
							<?= !empty($emailTemplate->visible)
								? '<i class="fa fa-check text-success"></i> ' . h(__('Yes'))
								: '<i class="fa fa-times text-danger"></i> ' . h(__('No')) ?>
						</dd>
					</div>
					<div class="record-view-row"><dt><?= __('Position') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($emailTemplate->pos, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Created') ?></dt><dd><?= $emailTemplate->created ? h(\App\Utility\LocaleDateParser::format($emailTemplate->created, 'datetime_short')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Modified') ?></dt><dd><?= $emailTemplate->modified ? h(\App\Utility\LocaleDateParser::format($emailTemplate->modified, 'datetime_short')) : '—' ?></dd></div>
				</dl>
			</div>
			<div class="card-footer">
				<div class="record-view-footer-actions">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . __('Edit'),
						['action' => 'edit', $emailTemplate->id],
						['escape' => false, 'class' => 'btn btn-primary']
					) ?>
				</div>
			</div>
		</div>
	</div>
</div>
