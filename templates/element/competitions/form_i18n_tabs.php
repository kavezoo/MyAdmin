<?php
/**
 * Competition language tabs (Admin + President form.php).
 * Shared across prefixes — same Translate fields; not a generic admin/*_language_fields element.
 *
 * JeffAdmin5: text/Description card-header tabs use full-width editor (`col-sm-12`).
 * Pass `fullWidth => true` on Description panes so the editor is not trapped in col-md-10/xxl-9.
 *
 * @see doc/snippets/form_language_fields.php.example
 * @see doc/form-i18n-tabs.md
 * @see vendor/zsfoto/jeffadmin5/templates/bake/element/form.twig (text tabPanel)
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $entity
 * @var list<array{locale: string, code: string, iso2: string, country_id: int, country_name: string, countries?: list<array{iso2: string, name: string, country_id: int}>}> $formLanguageTabs
 * @var list<array{name: string, label: string, type?: string, rows?: int, class?: string, editorHeight?: int}> $i18nFields
 * @var string $defaultLocale
 * @var string|null $tabsId Unique id when multiple language tab groups on one form
 * @var bool $fullWidth JeffAdmin5 text-tab layout (Description): full card width
 */
$formLanguageTabs = $formLanguageTabs ?? [];
$i18nFields = $i18nFields ?? [];
$defaultLocale = $defaultLocale ?? \App\Utility\FormLanguages::defaultLocaleForForm();
$entity = $entity ?? null;
$tabsId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($tabsId ?? 'formLanguageTabs')) ?: 'formLanguageTabs';
$fullWidth = !empty($fullWidth);
$contentId = $tabsId . '-content';

if ($formLanguageTabs === [] || $i18nFields === [] || $entity === null) {
	return;
}

$slug = static function (string $locale): string {
	return preg_replace('/[^a-z0-9]+/i', '-', strtolower($locale)) ?: 'locale';
};
?>
<?php if ($fullWidth): ?>
<div class="form-i18n-tabs form-i18n-tabs--full mb-3">
	<div class="form-i18n-tabs__toolbar d-flex flex-wrap align-items-center gap-2 mb-0">
		<span class="col-form-label py-0"><?= __('Translations:') ?></span>
		<ul class="nav nav-tabs form-language-tabs mb-0" id="<?= h($tabsId) ?>" role="tablist">
<?php else: ?>
<div class="form-group row mb-3">
	<label class="col-sm-3 col-md-2 col-form-label pt-2"><?= __('Translations:') ?></label>
	<div class="col-12 col-md-10 col-xxl-9">
		<ul class="nav nav-tabs form-language-tabs" id="<?= h($tabsId) ?>" role="tablist">
<?php endif; ?>
			<?php foreach ($formLanguageTabs as $i => $tab): ?>
				<?php
				$locale = $tab['locale'];
				$localeSlug = $slug($locale);
				$tabId = $tabsId . '-' . $localeSlug . '-tab';
				$paneId = $tabsId . '-' . $localeSlug;
				$isFirst = $i === 0;
				$isDefault = $locale === $defaultLocale
					|| (
						\App\Utility\FormLanguages::isEnglish($locale)
						&& \App\Utility\FormLanguages::isEnglish($defaultLocale)
					);
				$nameInputId = ($isDefault ? 'name' : 'name-' . $localeSlug);
				$countryLines = [];
				$countriesList = $tab['countries'] ?? null;
				if (is_array($countriesList) && $countriesList !== []) {
					foreach ($countriesList as $c) {
						$cName = trim((string)($c['name'] ?? ''));
						$cIso = trim((string)($c['iso2'] ?? ''));
						if ($cName === '') {
							$cName = $cIso !== '' ? $cIso : (string)($tab['code'] ?? '');
						}
						$countryLines[] = $cIso !== '' && !str_contains($cName, '(' . $cIso . ')')
							? $cName . ' (' . $cIso . ')'
							: $cName;
					}
				} else {
					$countryName = trim((string)($tab['country_name'] ?? ''));
					$iso2 = trim((string)($tab['iso2'] ?? ''));
					if ($countryName === '') {
						$countryName = $iso2 !== '' ? $iso2 : (string)($tab['code'] ?? '');
					}
					$countryLines[] = $iso2 !== '' && !str_contains($countryName, '(' . $iso2 . ')')
						? $countryName . ' (' . $iso2 . ')'
						: $countryName;
				}
				$tabTooltipHtml = implode('<br>', array_map('h', $countryLines));
				?>
				<li class="nav-item" role="presentation">
					<button
						class="nav-link<?= $isFirst ? ' active' : '' ?>"
						id="<?= h($tabId) ?>"
						data-bs-toggle="tab"
						data-bs-target="#<?= h($paneId) ?>"
						data-name-target="<?= h($nameInputId) ?>"
						type="button"
						role="tab"
						aria-controls="<?= h($paneId) ?>"
						aria-selected="<?= $isFirst ? 'true' : 'false' ?>"
					><span
						class="js-hover-only-tooltip"
						data-bs-placement="top"
						data-bs-html="true"
						title="<?= $tabTooltipHtml ?>"
					><?= h($tab['code']) ?></span></button>
				</li>
			<?php endforeach; ?>
		</ul>
<?php if ($fullWidth): ?>
	</div>
	<div class="tab-content form-language-tab-content form-language-tab-content--full" id="<?= h($contentId) ?>">
<?php else: ?>
		<div class="tab-content form-language-tab-content" id="<?= h($contentId) ?>">
<?php endif; ?>
			<?php foreach ($formLanguageTabs as $i => $tab): ?>
				<?php
				$locale = $tab['locale'];
				$localeSlug = $slug($locale);
				$tabId = $tabsId . '-' . $localeSlug . '-tab';
				$paneId = $tabsId . '-' . $localeSlug;
				$isFirst = $i === 0;
				$isDefault = $locale === $defaultLocale
					|| (
						\App\Utility\FormLanguages::isEnglish($locale)
						&& \App\Utility\FormLanguages::isEnglish($defaultLocale)
					);
				?>
				<div
					class="tab-pane<?= $isFirst ? ' show active' : '' ?>"
					id="<?= h($paneId) ?>"
					role="tabpanel"
					aria-labelledby="<?= h($tabId) ?>"
					tabindex="-1"
				>
					<?php if ($fullWidth): ?>
					<div class="row mb-0">
						<div class="col-12">
					<?php endif; ?>
					<?php foreach ($i18nFields as $field): ?>
						<?php
						$fieldName = $field['name'];
						$fieldLabel = $field['label'];
						$fieldType = $field['type'] ?? 'text';
						$rows = (int)($field['rows'] ?? 8);
						$inputId = $fieldName . '-' . $localeSlug;
						if ($isDefault && $fieldName === 'name') {
							$inputId = 'name';
						}
						$formName = $isDefault
							? $fieldName
							: '_translations.' . $locale . '.' . $fieldName;
						$value = null;
						if ($isDefault) {
							$value = $entity->get($fieldName);
						} else {
							$translations = $entity->get('_translations');
							if (is_array($translations) && isset($translations[$locale])) {
								$tr = $translations[$locale];
								$value = is_object($tr) && method_exists($tr, 'get')
									? $tr->get($fieldName)
									: (is_array($tr) ? ($tr[$fieldName] ?? null) : null);
							} elseif (method_exists($entity, 'translation')) {
								$tr = $entity->translation($locale);
								if ($tr !== null && method_exists($tr, 'get')) {
									$value = $tr->get($fieldName);
								}
							}
						}
						$controlOptions = [
							'label' => false,
							'id' => $inputId,
							'value' => $value,
						];
						if ($fieldName === 'name') {
							$controlOptions['data-i18n-name'] = '1';
							$controlOptions['data-i18n-locale'] = $locale;
						}
						// HTML5 required only on default locale — hidden tab required fields steal focus
						if (!$isDefault) {
							$controlOptions['error'] = false;
							$controlOptions['required'] = false;
						}
						if ($fieldType === 'editor') {
							$controlOptions['type'] = 'textarea';
							$controlOptions['rows'] = $rows;
							$controlOptions['class'] = trim('form-control editor ' . ($field['class'] ?? ''));
							$editorHeight = (int)($field['editorHeight'] ?? 0);
							if ($editorHeight > 0) {
								$controlOptions['data-editor-height'] = $editorHeight;
							}
						} else {
							$controlOptions['type'] = 'text';
							$class = 'form-control mb-3';
							if ($fieldName === 'name') {
								$class .= ' js-i18n-name';
							}
							$controlOptions['class'] = trim($class . ' ' . ($field['class'] ?? ''));
							$controlOptions['autocomplete'] = 'off';
							if ($isFirst && $fieldName === 'name') {
								$controlOptions['autofocus'] = true;
							}
						}
						?>
						<?php if (!$fullWidth || $fieldType !== 'editor'): ?>
							<label for="<?= h($inputId) ?>" class="form-label"><?= $this->Form->requiredMark($fieldName) ?><?= h($fieldLabel) ?></label>
						<?php endif; ?>
						<?= $this->Form->control($formName, $controlOptions) ?>
						<?php if ($isDefault): ?>
							<?= $this->element('admin/field_error', ['field' => $fieldName]) ?>
						<?php endif; ?>
					<?php endforeach; ?>
					<?php if ($fullWidth): ?>
						</div>
					</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
<?php if ($fullWidth): ?>
</div>
<?php else: ?>
	</div>
</div>
<?php endif; ?>
<?php
// Inline: TAB → focus that locale's name; refresh Summernote in pane
$tabsIdJs = json_encode($tabsId, JSON_UNESCAPED_UNICODE);
$this->Html->scriptBlock(
	<<<JS
(function () {
	var tabsId = {$tabsIdJs};
	function focusLanguageName(btn) {
		if (!btn) {
			return;
		}
		var nameId = btn.getAttribute('data-name-target');
		var paneSel = btn.getAttribute('data-bs-target');
		var pane = paneSel ? document.querySelector(paneSel) : null;

		if (pane && window.jQuery) {
			window.jQuery(pane).find('.editor').each(function () {
				var \$editor = window.jQuery(this);
				if (\$editor.next('.note-editor').length && typeof \$editor.summernote === 'function') {
					\$editor.summernote('code', \$editor.summernote('code'));
				}
			});
		}

		var input = null;
		if (nameId) {
			input = document.getElementById(nameId);
		}
		if (!input && pane) {
			input = pane.querySelector('input.js-i18n-name')
				|| pane.querySelector('input[data-i18n-name="1"]')
				|| pane.querySelector('input.form-control');
		}
		if (!input || input.disabled) {
			return;
		}
		input.focus();
		if (typeof input.select === 'function') {
			try { input.select(); } catch (err) { /* ignore */ }
		}
	}

	function bindLanguageTabFocus() {
		var root = document.getElementById(tabsId);
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
				window.setTimeout(function () { focusLanguageName(btn); }, 0);
				window.setTimeout(function () { focusLanguageName(btn); }, 50);
			});
		}, true);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bindLanguageTabFocus);
	} else {
		bindLanguageTabFocus();
	}
})();
JS
	,
	['block' => 'scriptBottom']
);
?>
