<?php
/**
 * Language tabs for translatable text fields (visible countries by pos, EN first).
 *
 * Tab label = short language code (EN, HU, DE, …).
 * Default locale fields map to the entity root; other locales to `_translations.{locale}.*`.
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $entity
 * @var list<array{locale: string, code: string, iso2: string, country_id: int}> $formLanguageTabs
 * @var list<array{name: string, label: string, type?: string, rows?: int, class?: string}> $i18nFields
 * @var string $defaultLocale
 */
$formLanguageTabs = $formLanguageTabs ?? [];
$i18nFields = $i18nFields ?? [];
$defaultLocale = $defaultLocale ?? 'en_GB';
$entity = $entity ?? null;

if ($formLanguageTabs === [] || $i18nFields === [] || $entity === null) {
	return;
}

$slug = static function (string $locale): string {
	return preg_replace('/[^a-z0-9]+/i', '-', strtolower($locale)) ?: 'locale';
};
?>
<div class="form-group row mb-3">
	<label class="col-sm-3 col-md-2 col-form-label pt-2"><?= __('Translations:') ?></label>
	<div class="col-12 col-md-10 col-xxl-9">
		<ul class="nav nav-tabs form-language-tabs" id="formLanguageTabs" role="tablist">
			<?php foreach ($formLanguageTabs as $i => $tab): ?>
				<?php
				$localeSlug = $slug($tab['locale']);
				$tabId = 'form-lang-' . $localeSlug . '-tab';
				$paneId = 'form-lang-' . $localeSlug;
				$isFirst = $i === 0;
				?>
				<li class="nav-item" role="presentation">
					<button
						class="nav-link<?= $isFirst ? ' active' : '' ?>"
						id="<?= h($tabId) ?>"
						data-bs-toggle="tab"
						data-bs-target="#<?= h($paneId) ?>"
						type="button"
						role="tab"
						aria-controls="<?= h($paneId) ?>"
						aria-selected="<?= $isFirst ? 'true' : 'false' ?>"
					><?= h($tab['code']) ?></button>
				</li>
			<?php endforeach; ?>
		</ul>
		<div class="tab-content form-language-tab-content" id="formLanguageTabContent">
			<?php foreach ($formLanguageTabs as $i => $tab): ?>
				<?php
				$locale = $tab['locale'];
				$localeSlug = $slug($locale);
				$tabId = 'form-lang-' . $localeSlug . '-tab';
				$paneId = 'form-lang-' . $localeSlug;
				$isFirst = $i === 0;
				$isDefault = $locale === $defaultLocale
					|| (
						\App\Utility\FormLanguages::isEnglish($locale)
						&& \App\Utility\FormLanguages::isEnglish($defaultLocale)
					);
				?>
				<div
					class="tab-pane fade<?= $isFirst ? ' show active' : '' ?>"
					id="<?= h($paneId) ?>"
					role="tabpanel"
					aria-labelledby="<?= h($tabId) ?>"
					tabindex="0"
				>
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
							'error' => $isDefault,
						];
						if ($fieldType === 'editor') {
							$controlOptions['type'] = 'textarea';
							$controlOptions['rows'] = $rows;
							$controlOptions['class'] = trim('form-control editor ' . ($field['class'] ?? ''));
						} else {
							$controlOptions['type'] = 'text';
							$controlOptions['class'] = trim('form-control mb-3 ' . ($field['class'] ?? ''));
							$controlOptions['autocomplete'] = 'off';
							if ($isFirst && $fieldName === 'name') {
								$controlOptions['autofocus'] = true;
							}
						}
						?>
						<label for="<?= h($inputId) ?>" class="form-label"><?= h($fieldLabel) ?></label>
						<?= $this->Form->control($formName, $controlOptions) ?>
						<?php if ($isDefault): ?>
							<?= $this->element('admin/field_error', ['field' => $fieldName]) ?>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
