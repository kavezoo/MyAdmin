<?php
/**
 * Countries add/edit — superuser: full fields; admin: visible + pos only.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Country $country
 * @var array<int, string> $continents
 * @var bool $canEditFully
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

$isEdit = !$country->isNew();
$canEditFully = (bool)$this->get('canEditFully', false);
$continents = $continents ?? [];
$visibleCountryOptions = $visibleCountryOptions ?? [];
$selfCountryId = (int)($selfCountryId ?? ($isEdit ? (int)$country->id : 0));
$countryExamples = $registeredCountryExamples ?? \App\Utility\AdminCountry::registeredCountryExamples();

// Options: every other country (own language is always stored, never listed).
$visibleCountrySelectOptions = [];
foreach ($visibleCountryOptions as $optId => $optLabel) {
	$optId = (int)$optId;
	if ($selfCountryId > 0 && $optId === $selfCountryId) {
		continue;
	}
	$visibleCountrySelectOptions[$optId] = $optLabel;
}

// Selected extras only (from junction — not BelongsToMany contain).
$selectedVisibleIds = array_values(array_unique(array_map(
	'intval',
	(array)($selectedVisibleIds ?? [])
)));
$selectedVisibleIds = array_values(array_filter(
	$selectedVisibleIds,
	static fn(int $id): bool => $id > 0 && ($selfCountryId < 1 || $id !== $selfCountryId)
));
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-check-square-o"></i> <?= $isEdit ? __('Edit country') : __('New country') ?></h3>
					<?= $canEditFully
						? ($isEdit ? __('Edit the selected record.') : __('Create a new record.'))
						: __('Only visibility and position can be changed.') ?>
				</div>
				<div class="float-right d-flex align-items-center gap-3">
					<?php if ($isEdit): ?>
						<div class="text-end text-muted small lh-sm">
							<div><?= __('Created:') ?> <b><?= $country->created ? h(\App\Utility\LocaleDateParser::format($country->created, 'date')) : '—' ?></b></div>
							<div><?= __('Modified:') ?> <b><?= $country->modified ? h(\App\Utility\LocaleDateParser::format($country->modified, 'date')) : '—' ?></b></div>
						</div>
					<?php endif; ?>
					<a role="button" href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary" id="btn-close-form" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h('<b>' . __('Close window') . '</b>') ?>">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?= $this->Form->create($country, [
					'id' => 'form-horizontal',
					'autocomplete' => 'off',
					'type' => 'file',
				]) ?>
					<?php if ($canEditFully): ?>
						<div class="form-group row mb-3">
							<?= $this->Form->adminLabel('iso2', __('ISO:'), ['for' => 'iso2']) ?>
							<div class="col-12 col-md-10 col-xl-2">
								<?= $this->Form->control('iso2', [
									'label' => false,
									'class' => 'form-control text-uppercase',
									'id' => 'iso2',
									'maxlength' => 2,
									'placeholder' => $countryExamples['iso2'],
								]) ?>
							</div>
						</div>

						<div class="form-group row mb-3">
							<?= $this->Form->adminLabel('name', __('Name:'), ['for' => 'name']) ?>
							<div class="col-12 col-md-10 col-xl-5">
								<?= $this->Form->control('name', [
									'label' => false,
									'class' => 'form-control',
									'id' => 'name',
									'placeholder' => $countryExamples['name'] !== '' ? $countryExamples['name'] : null,
								]) ?>
							</div>
						</div>

						<div class="form-group row mb-3">
							<?= $this->Form->adminLabel('endonim_name', __('Endonym:'), ['for' => 'endonim-name']) ?>
							<div class="col-12 col-md-10 col-xl-5">
								<?= $this->Form->control('endonim_name', [
									'label' => false,
									'class' => 'form-control',
									'id' => 'endonim-name',
									'placeholder' => $countryExamples['endonim_name'] !== '' ? $countryExamples['endonim_name'] : null,
								]) ?>
								<div class="form-text">
									<?php if ($countryExamples['endonim_name'] !== ''): ?>
										<?= __('Endonym — how the country name is written in its own language/script (e.g. {0}).', $countryExamples['endonim_name']) ?>
									<?php else: ?>
										<?= __('Endonym — how the country name is written in its own language/script.') ?>
									<?php endif; ?>
								</div>
							</div>
						</div>

						<div class="form-group row mb-3">
							<?= $this->Form->adminLabel('locale', __('Primary locale:'), ['for' => 'locale']) ?>
							<div class="col-12 col-md-10 col-xl-4">
								<?= $this->Form->control('locale', [
									'label' => false,
									'class' => 'form-control',
									'id' => 'locale',
									'placeholder' => $countryExamples['locale'],
								]) ?>
							</div>
						</div>

						<div class="form-group row mb-3">
							<?= $this->Form->adminLabel('timezone', __('Timezone:'), ['for' => 'timezone']) ?>
							<div class="col-12 col-md-10 col-xl-5">
								<?= $this->Form->control('timezone', [
									'label' => false,
									'class' => 'form-control',
									'id' => 'timezone',
									'placeholder' => $countryExamples['timezone'],
								]) ?>
								<div class="form-text">
									<?= __('IANA timezone used for datetime display for users registered in this country (e.g. {0}).', $countryExamples['timezone']) ?>
								</div>
							</div>
						</div>

						<div class="form-group row mb-3">
							<?= $this->Form->adminLabel('phone_prefix', __('Phone prefix:'), ['for' => 'phone-prefix']) ?>
							<div class="col-12 col-md-10 col-xl-3">
								<?= $this->Form->control('phone_prefix', [
									'label' => false,
									'class' => 'form-control js-phone-prefix',
									'id' => 'phone-prefix',
									'placeholder' => '+36',
								]) ?>
								<div class="form-text">
									<?= __('International calling code for user phone inputs (E.164, e.g. +36).') ?>
								</div>
							</div>
						</div>

						<div class="form-group row mb-3">
							<?= $this->Form->adminLabel('logo_file', __('National association logo:'), ['for' => 'logo-file', 'required' => false]) ?>
							<div class="col-12 col-md-10 col-xl-6">
								<?php
								$countryLogoUrl = $isEdit
									? \App\Utility\CountryLogo::publicUrlFor(
										(int)$country->id,
										is_string($country->get('logo')) ? (string)$country->get('logo') : null
									)
									: '';
								?>
								<?php if ($countryLogoUrl !== ''): ?>
									<div class="mb-2">
										<img src="<?= h($countryLogoUrl) ?>" alt="" class="img-fluid" style="max-width:120px;max-height:120px;object-fit:contain;background:transparent;">
									</div>
								<?php endif; ?>
								<?= $this->Form->control('logo_file', [
									'label' => false,
									'type' => 'file',
									'class' => 'form-control',
									'id' => 'logo-file',
									'accept' => 'image/png,image/jpeg,image/webp',
								]) ?>
								<div class="form-text">
									<?= __('PNG recommended (transparency kept). Used as {{national_association_logo}} in competition announcements.') ?>
								</div>
							</div>
						</div>

						<div class="form-group row mb-3">
							<?= $this->Form->adminLabel('continent_id', __('Continent:'), ['for' => 'continent-id']) ?>
							<div class="col-12 col-md-10 col-xl-5">
								<?= $this->Form->control('continent_id', [
									'label' => false,
									'options' => $continents,
									'empty' => __('Select continent...'),
									'class' => 'form-select',
									'id' => 'continent-id',
								]) ?>
							</div>
						</div>

						<div class="form-group row mb-3">
							<label for="visible-countries-ids" class="col-sm-3 col-md-2 col-form-label"><?= __('Additional languages:') ?></label>
							<div class="col-12 col-md-10 col-xl-10 col-xxl-9">
								<?= $this->Form->control('visible_countries._ids', [
									'label' => false,
									'options' => $visibleCountrySelectOptions,
									'multiple' => true,
									'class' => 'js-example-basic-multiple form-select',
									'id' => 'visible-countries-ids',
									'value' => $selectedVisibleIds,
									'data-placeholder' => __('Select additional languages...'),
								]) ?>
								<div class="form-text">
									<?= __('Your own country language is always available on form tabs and is not listed here. Pick other countries whose languages should also appear as translation tabs when this country is active.') ?>
								</div>
							</div>
						</div>
					<?php else: ?>
						<div class="form-group row mb-3">
							<label class="col-sm-3 col-md-2 col-form-label"><?= __('ISO:') ?></label>
							<div class="col-12 col-md-10 col-xl-2">
								<p class="form-control-plaintext mb-0"><code><?= h($country->iso2) ?></code></p>
							</div>
						</div>

						<div class="form-group row mb-3">
							<label class="col-sm-3 col-md-2 col-form-label"><?= __('Name:') ?></label>
							<div class="col-12 col-md-10 col-xl-5">
								<p class="form-control-plaintext mb-0"><?= h($country->name) ?></p>
							</div>
						</div>

						<div class="form-group row mb-3">
							<label class="col-sm-3 col-md-2 col-form-label"><?= __('Endonym:') ?></label>
							<div class="col-12 col-md-10 col-xl-5">
								<p class="form-control-plaintext mb-0"><?= h($country->endonim_name) ?></p>
							</div>
						</div>

						<div class="form-group row mb-3">
							<label class="col-sm-3 col-md-2 col-form-label"><?= __('Primary locale:') ?></label>
							<div class="col-12 col-md-10 col-xl-4">
								<p class="form-control-plaintext mb-0"><code><?= h($country->locale) ?></code></p>
							</div>
						</div>

						<div class="form-group row mb-3">
							<label class="col-sm-3 col-md-2 col-form-label"><?= __('Timezone:') ?></label>
							<div class="col-12 col-md-10 col-xl-5">
								<p class="form-control-plaintext mb-0"><code><?= h($country->timezone) ?></code></p>
							</div>
						</div>

						<div class="form-group row mb-3">
							<label class="col-sm-3 col-md-2 col-form-label"><?= __('Phone prefix:') ?></label>
							<div class="col-12 col-md-10 col-xl-3">
								<p class="form-control-plaintext mb-0"><code><?= h((string)$country->phone_prefix) ?></code></p>
							</div>
						</div>

						<div class="form-group row mb-3">
							<label class="col-sm-3 col-md-2 col-form-label"><?= __('Continent:') ?></label>
							<div class="col-12 col-md-10 col-xl-5">
								<p class="form-control-plaintext mb-0"><?= h($country->continent->name ?? '') ?></p>
							</div>
						</div>
					<?php endif; ?>

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
								$country->pos,
								['id' => 'pos', 'autofocus' => !$canEditFully]
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
