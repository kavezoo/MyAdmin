<?php
/**
 * Language tabs for email template text fields (one DB row per language_id + slug).
 *
 * @var \App\View\AppView $this
 * @var list<array{language_id: int, locale: string, code: string, label: string}> $emailTemplateLanguageTabs
 * @var array<int, array{id?: int|null, name?: string, subject?: string, body_html?: string, body_text?: string}> $emailTemplateTranslations
 * @var int $emailTemplateActiveLanguageId
 */
$emailTemplateLanguageTabs = $emailTemplateLanguageTabs ?? [];
$emailTemplateTranslations = $emailTemplateTranslations ?? [];
$emailTemplateActiveLanguageId = (int)($emailTemplateActiveLanguageId ?? 0);

if ($emailTemplateLanguageTabs === []) {
	return;
}

$fields = [
	['name' => 'subject', 'label' => __('Subject:'), 'type' => 'text'],
	['name' => 'body_html', 'label' => __('HTML body:'), 'type' => 'textarea', 'rows' => 12, 'class' => 'font-monospace'],
	['name' => 'body_text', 'label' => __('Text body:'), 'type' => 'textarea', 'rows' => 8, 'class' => 'font-monospace'],
];
?>
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
					<?php foreach ($fields as $fi => $field): ?>
						<?php
						$fieldName = $field['name'];
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
						if ($field['type'] === 'textarea') {
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
<?php
$this->Html->scriptBlock(
	<<<'JS'
(function () {
	function focusLanguageName(btn) {
		if (!btn) {
			return;
		}
		var nameId = btn.getAttribute('data-name-target');
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
?>
