<?php
/**
 * Email templates add/edit (Admin).
 *
 * Cake convention: all form markup lives here (templates/President/EmailTemplates/form.php),
 * not in custom elements.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\EmailTemplate $emailTemplate
 * @var array<string, string> $slugOptions
 * @var array<int, string> $countryOptions
 * @var bool $canChangeCountry
 * @var list<array{language_id: int, locale: string, code: string, label: string}> $emailTemplateLanguageTabs
 * @var array<int, array{id?: int|null, name?: string, subject?: string, body_html?: string, body_text?: string}> $emailTemplateTranslations
 * @var int $emailTemplateActiveLanguageId
 */
$this->Html->css([
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
	'pages/form',
], ['block' => true]);
$this->element('admin/form_summernote_assets');

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
$countryOptions = $countryOptions ?? [];
$canChangeCountry = !empty($canChangeCountry);
$countryId = (int)($emailTemplate->country_id ?? 0);
$countryLabel = $countryOptions[$countryId] ?? ($countryId > 0 ? \App\Utility\AdminCountry::label($countryId) : '');
$emailTemplateLanguageTabs = $emailTemplateLanguageTabs ?? [];
$emailTemplateTranslations = $emailTemplateTranslations ?? [];
$emailTemplateActiveLanguageId = (int)($emailTemplateActiveLanguageId ?? 0);

$langFields = [
	['name' => 'subject', 'label' => __('Subject:'), 'type' => 'text'],
	['name' => 'body_html', 'label' => __('HTML body:'), 'type' => 'editor', 'rows' => 12, 'editorHeight' => 400],
	['name' => 'body_text', 'label' => __('Text body:'), 'type' => 'textarea', 'rows' => 8, 'class' => 'font-monospace'],
];
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
						<?= $this->Form->adminLabel('slug', __('Template key:'), ['for' => 'slug']) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('slug', [
								'label' => false,
								'type' => 'text',
								'class' => 'form-control font-monospace',
								'id' => 'slug',
								'readonly' => $isEdit,
								'autocomplete' => 'off',
							]) ?>
							<div class="form-text"><?= __('System key (e.g. membership_application). Set with the developer — not a user-facing list.') ?></div>
						</div>
					</div>

					<?php if ($canChangeCountry): ?>
					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('country_id', __('Country:'), ['for' => 'country-id']) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('country_id', [
								'label' => false,
								'options' => $countryOptions,
								'empty' => __('Select country...'),
								'class' => 'js-example-basic-single form-select',
								'id' => 'country-id',
								'data-placeholder' => __('Select country...'),
							]) ?>
						</div>
					</div>
					<?php else: ?>
					<div class="form-group row mb-3">
						<label class="col-sm-3 col-md-2 col-form-label"><?= __('Country:') ?></label>
						<div class="col-12 col-md-10 col-xl-5">
							<p class="form-control-plaintext mb-0"><?= h($countryLabel) ?></p>
							<?= $this->Form->hidden('country_id', ['id' => 'country-id']) ?>
						</div>
					</div>
					<?php endif; ?>

					<?php if ($emailTemplateLanguageTabs !== []): ?>
					<div class="form-group row mb-3">
						<label class="col-sm-3 col-md-2 col-form-label pt-2"><?= __('Translations:') ?></label>
						<div class="col-12 col-md-10 col-xxl-9">
							<ul class="nav nav-tabs form-language-tabs" id="formLanguageTabs" role="tablist">
								<?php foreach ($emailTemplateLanguageTabs as $i => $tab): ?>
									<?php
									$langId = (int)$tab['language_id'];
									$localeSlug = preg_replace('/[^a-z0-9]+/i', '-', strtolower((string)$tab['locale'])) ?: 'locale';
									$tabId = 'email-lang-' . $localeSlug . '-tab';
									$paneId = 'email-lang-' . $localeSlug;
									$isActive = $emailTemplateActiveLanguageId > 0
										? $langId === $emailTemplateActiveLanguageId
										: $i === 0;
									$focusInputId = 'subject-' . $localeSlug;
									?>
									<li class="nav-item" role="presentation">
										<button
											class="nav-link<?= $isActive ? ' active' : '' ?>"
											id="<?= h($tabId) ?>"
											data-bs-toggle="tab"
											data-bs-target="#<?= h($paneId) ?>"
											data-name-target="<?= h($focusInputId) ?>"
											type="button"
											role="tab"
											aria-controls="<?= h($paneId) ?>"
											aria-selected="<?= $isActive ? 'true' : 'false' ?>"
										><span
											class="js-hover-only-tooltip"
											data-bs-placement="top"
											data-bs-html="true"
											title="<?= h((string)$tab['label']) ?>"
										><?= h((string)$tab['code']) ?></span></button>
									</li>
								<?php endforeach; ?>
							</ul>
							<div class="tab-content form-language-tab-content" id="formLanguageTabContent">
								<?php foreach ($emailTemplateLanguageTabs as $i => $tab): ?>
									<?php
									$langId = (int)$tab['language_id'];
									$localeSlug = preg_replace('/[^a-z0-9]+/i', '-', strtolower((string)$tab['locale'])) ?: 'locale';
									$tabId = 'email-lang-' . $localeSlug . '-tab';
									$paneId = 'email-lang-' . $localeSlug;
									$isActive = $emailTemplateActiveLanguageId > 0
										? $langId === $emailTemplateActiveLanguageId
										: $i === 0;
									$values = $emailTemplateTranslations[$langId] ?? [];
									?>
									<div
										class="tab-pane<?= $isActive ? ' show active' : '' ?>"
										id="<?= h($paneId) ?>"
										role="tabpanel"
										aria-labelledby="<?= h($tabId) ?>"
										tabindex="-1"
									>
										<?= $this->Form->hidden('translations.' . $langId . '.language_id', [
											'value' => $langId,
										]) ?>
										<?php if (!empty($values['id'])): ?>
											<?= $this->Form->hidden('translations.' . $langId . '.id', [
												'value' => (int)$values['id'],
											]) ?>
										<?php endif; ?>
										<?php foreach ($langFields as $fi => $field): ?>
											<?php
											$fieldName = $field['name'];
											$fieldType = $field['type'];
											$inputId = $fieldName . '-' . $localeSlug;
											$formName = 'translations.' . $langId . '.' . $fieldName;
											$value = $values[$fieldName] ?? '';
											$controlOptions = [
												'label' => false,
												'id' => $inputId,
												'value' => $value,
												'required' => false,
												'error' => false,
											];
							if ($fieldType === 'editor') {
								$controlOptions['type'] = 'textarea';
								$controlOptions['rows'] = (int)($field['rows'] ?? 12);
								$controlOptions['class'] = 'form-control editor';
								$editorHeight = (int)($field['editorHeight'] ?? 0);
								if ($editorHeight > 0) {
									$controlOptions['data-editor-height'] = $editorHeight;
								}
											} elseif ($fieldType === 'textarea') {
												$controlOptions['type'] = 'textarea';
												$controlOptions['rows'] = (int)($field['rows'] ?? 8);
												$controlOptions['class'] = trim('form-control mb-3 ' . ($field['class'] ?? ''));
											} else {
												$controlOptions['type'] = 'text';
												$controlOptions['class'] = 'form-control mb-3' . ($fieldName === 'subject' ? ' js-i18n-name' : '');
												$controlOptions['autocomplete'] = 'off';
												if ($fieldName === 'subject') {
													$controlOptions['data-i18n-name'] = '1';
												}
												if ($isActive && $fi === 0) {
													$controlOptions['autofocus'] = true;
												}
											}
											?>
											<label for="<?= h($inputId) ?>" class="form-label"><?= h($field['label']) ?></label>
											<?= $this->Form->control($formName, $controlOptions) ?>
										<?php endforeach; ?>
									</div>
								<?php endforeach; ?>
							</div>
							<div class="form-text"><?= __('One template per language. Placeholders: {applicantName}, {clubName}, {listUrl}, …') ?></div>
						</div>
					</div>
					<?php endif; ?>

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
<?php
$this->Html->scriptBlock(
	<<<'JS'
(function () {
	function focusLanguageName(btn) {
		if (!btn) {
			return;
		}
		var nameId = btn.getAttribute('data-name-target');
		var paneSel = btn.getAttribute('data-bs-target');
		var pane = paneSel ? document.querySelector(paneSel) : null;

		if (pane && window.jQuery) {
			window.jQuery(pane).find('.editor').each(function () {
				var $editor = window.jQuery(this);
				if ($editor.next('.note-editor').length && typeof $editor.summernote === 'function') {
					$editor.summernote('code', $editor.summernote('code'));
				}
			});
		}

		var input = nameId ? document.getElementById(nameId) : null;
		if (!input || input.disabled) {
			return;
		}
		input.focus();
		if (typeof input.select === 'function') {
			try { input.select(); } catch (err) { /* ignore */ }
		}
	}

	function bindLanguageTabFocus() {
		var root = document.getElementById('formLanguageTabs');
		if (!root || root.getAttribute('data-name-focus-bound') === '1') {
			return;
		}
		root.setAttribute('data-name-focus-bound', '1');
		root.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (btn) {
			btn.addEventListener('shown.bs.tab', function () {
				focusLanguageName(btn);
			});
		});
		root.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-bs-toggle="tab"]');
			if (!btn || !root.contains(btn)) {
				return;
			}
			window.requestAnimationFrame(function () {
				focusLanguageName(btn);
			});
		}, true);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bindLanguageTabFocus);
	} else {
		bindLanguageTabFocus();
	}
})();
JS,
	['block' => 'scriptBottom']
);

if (!$isEdit):
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
		function buildAddUrl(slug) {
			var url = window.EmailTemplateForm.addUrl + '?slug=' + encodeURIComponent(slug);
			var countryId = String($('#country-id').val() || '');
			if (countryId) {
				url += '&country_id=' + encodeURIComponent(countryId);
			}
			return url;
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
						window.location.href = buildAddUrl(slug);
					}
				});
				return;
			}
			window.location.href = buildAddUrl(slug);
		});
	});
})(jQuery);
JS
		,
		['block' => 'scriptBottom']
	);
endif;
?>
