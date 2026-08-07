<?php
/**
 * Email templates add/edit (President) — multi-language tabs for text fields.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\EmailTemplate $emailTemplate
 * @var array<string, string> $slugOptions
 * @var list<array{language_id: int, locale: string, code: string, label: string}> $emailTemplateLanguageTabs
 * @var array<int, array{id?: int|null, name?: string, subject?: string, body_html?: string, body_text?: string}> $emailTemplateTranslations
 * @var int $emailTemplateActiveLanguageId
 */
$this->Html->css([
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
	'pages/form',
], ['block' => true]);

$config = [
	'indexUrl' => $this->Url->build(['action' => 'index']),
	'numberFormat' => \App\Utility\LocaleNumberParser::jsConfig(),
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);
$this->Html->script([
	'/plugins/select2-4.1.0/js/select2.full.min',
	'/plugins/inputmask/jquery.inputmask.min',
	'pages/form',
], ['block' => 'scriptBottom']);

$isEdit = !$emailTemplate->isNew();
$slugOptions = $slugOptions ?? [];
$emailTemplateLanguageTabs = $emailTemplateLanguageTabs ?? [];
$emailTemplateTranslations = $emailTemplateTranslations ?? [];
$emailTemplateActiveLanguageId = (int)($emailTemplateActiveLanguageId ?? 0);
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-check-square-o"></i> <?= $isEdit ? __('Edit email template') : __('New email template') ?></h3>
					<?= $isEdit ? __('Edit the selected record.') : __('Create a new record.') ?>
				</div>
				<div class="float-right d-flex align-items-center gap-3">
					<?php if ($isEdit): ?>
						<div class="text-end text-muted small lh-sm">
							<div><?= __('Created:') ?> <b><?= $emailTemplate->created ? h(\App\Utility\LocaleDateParser::format($emailTemplate->created, 'date')) : '—' ?></b></div>
							<div><?= __('Modified:') ?> <b><?= $emailTemplate->modified ? h(\App\Utility\LocaleDateParser::format($emailTemplate->modified, 'date')) : '—' ?></b></div>
						</div>
					<?php endif; ?>
					<a role="button" href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary" id="btn-close-form" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h('<b>' . __('Close window') . '</b>') ?>">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?= $this->Form->create($emailTemplate, [
					'id' => 'form-horizontal',
					'autocomplete' => 'off',
				]) ?>
					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('slug', __('Template:'), ['for' => 'slug']) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('slug', [
								'label' => false,
								'type' => 'select',
								'options' => $slugOptions,
								'empty' => __('Select template...'),
								'class' => 'js-example-basic-single form-select',
								'id' => 'slug',
								'data-placeholder' => __('Select template...'),
								'disabled' => $isEdit,
							]) ?>
							<?php if ($isEdit): ?>
								<?= $this->Form->hidden('slug', ['value' => (string)$emailTemplate->slug]) ?>
							<?php endif; ?>
						</div>
					</div>

					<?= $this->element('admin/email_template_language_fields', [
						'emailTemplateLanguageTabs' => $emailTemplateLanguageTabs,
						'emailTemplateTranslations' => $emailTemplateTranslations,
						'emailTemplateActiveLanguageId' => $emailTemplateActiveLanguageId,
					]) ?>

					<div class="form-group row mb-3">
						<div class="d-none d-md-block col-md-2"></div>
						<div class="col-12 col-md-10">
							<div class="form-check form-switch">
								<?= $this->Form->checkbox('enabled', ['class' => 'form-check-input', 'id' => 'enabled']) ?>
								<?= $this->Form->adminLabel('enabled', __('Enabled'), [
									'for' => 'enabled',
									'class' => 'form-check-label',
								]) ?>
							</div>
							<?= $this->element('admin/field_error', ['field' => 'enabled']) ?>
						</div>
					</div>

					<div class="row">
						<div class="col-12 col-xxl-11">
							<hr class="my-4">
						</div>
					</div>
					<div class="form-group row mb-3">
						<div class="d-none d-md-block col-md-2"></div>
						<div class="col-12 col-md-10">
							<div class="form-check form-switch">
								<?= $this->Form->checkbox('visible', ['class' => 'form-check-input', 'id' => 'visible']) ?>
								<?= $this->Form->adminLabel('visible', __('Visible'), [
									'for' => 'visible',
									'class' => 'form-check-label',
								]) ?>
							</div>
							<?= $this->element('admin/field_error', ['field' => 'visible']) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('pos', __('Position:'), ['for' => 'pos']) ?>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('pos', \App\Utility\LocaleNumberParser::formIntegerOptions(
								$emailTemplate->pos,
								['id' => 'pos']
							)) ?>
						</div>
					</div>
				<?= $this->Form->end() ?>
			</div>
			<div class="card-footer">
				<div class="row">
					<div class="col-12 col-md-10 col-xxl-9 offset-md-2">
						<button type="submit" form="form-horizontal" class="btn btn-success">
							<span class="btn-label"><i class="fa fa-save"></i></span><?= __('Save') ?>
						</button>
						<a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary ms-3" id="btn-cancel">
							<span class="btn-label"><i class="fa fa-times"></i></span><?= __('Cancel') ?>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php if (!$isEdit): ?>
<?php
$addUrl = $this->Url->build(['action' => 'add']);
$this->Html->scriptBlock(
	'window.EmailTemplateForm = ' . json_encode(['addUrl' => $addUrl], JSON_UNESCAPED_SLASHES) . ';'
	. <<<'JS'
(function ($) {
	$(function () {
		var $slug = $('#slug');
		if (!$slug.length || !window.EmailTemplateForm) {
			return;
		}
		$slug.on('change', function () {
			var slug = String($slug.val() || '');
			if (!slug) {
				return;
			}
			if (typeof MyAdmin !== 'undefined' && typeof MyAdmin.confirmLeave === 'function') {
				MyAdmin.confirmLeave({
					onConfirm: function () {
						if (typeof MyAdmin.allowFormLeave === 'function') {
							MyAdmin.allowFormLeave();
						}
						window.location.href = window.EmailTemplateForm.addUrl + '?slug=' + encodeURIComponent(slug);
					}
				});
				return;
			}
			window.location.href = window.EmailTemplateForm.addUrl + '?slug=' + encodeURIComponent(slug);
		});
	});
})(jQuery);
JS
	,
	['block' => 'scriptBottom']
);
?>
<?php endif; ?>
