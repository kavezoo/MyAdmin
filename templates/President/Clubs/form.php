<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Club $club
 * @var int $countryId
 * @var int $officerCountryId
 * @var string $countryLabel
 * @var array<string, string> $presidentOptions
 * @var array<string, string> $countryOptions
 * @var array<string, string> $cityOptions
 * @var array<int, string> $countryFlags
 * @var string $clubPresidentId
 * @var int|null $cityId
 */
$this->Html->css([
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
	'pages/form',
	'pages/users_auth',
], ['block' => true]);

$isEdit = !$club->isNew();
$presidentOptions = $presidentOptions ?? [];
$countryOptions = $countryOptions ?? [];
$cityOptions = $cityOptions ?? [];
$countryFlags = $countryFlags ?? [];
$clubPresidentId = (string)($clubPresidentId ?? '');
$countryId = (int)($countryId ?? 0);
$officerCountryId = (int)($officerCountryId ?? $countryId);
$cityId = isset($cityId) ? (int)$cityId : (int)($club->city_id ?? 0);
if ($cityId < 1) {
	$cityId = null;
}

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

$jsCfg = [
	'userAjaxUrl' => $this->Url->build(['action' => 'userOptions']),
	'countryAjaxUrl' => $this->Url->build(['action' => 'countryOptions']),
	'cityAjaxUrl' => $this->Url->build(['action' => 'cityOptions']),
	'rememberCountryUrl' => $this->Url->build(['action' => 'rememberCountry']),
	'flagBase' => $this->Url->build('/img/flags/'),
	'flags' => $countryFlags,
	'noResults' => __('No results found.'),
	'searching' => __('Search...'),
	'inputTooShort' => __('Please enter 2 or more characters'),
	'presidentPlaceholder' => __('Select club president...'),
	'countryPlaceholder' => __('Select country...'),
	'cityPlaceholder' => __('Start typing the city name...'),
];
$this->Html->scriptBlock(
	'window.PresidentClubsForm = ' . json_encode($jsCfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';',
	['block' => 'script']
);

$this->Html->script([
	'/plugins/select2-4.1.0/js/select2.full.min',
	'/plugins/inputmask/jquery.inputmask.min',
	'pages/form',
	'pages/president_clubs_form',
], ['block' => 'scriptBottom']);
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-check-square-o"></i> <?= $isEdit ? __('Edit club') : __('New club') ?></h3>
					<?= $isEdit ? __('Edit the selected record.') : __('Create a new record.') ?>
				</div>
				<div class="float-right d-flex align-items-center gap-3">
					<?php if ($isEdit): ?>
						<div class="text-end text-muted small lh-sm">
							<div><?= __('Created:') ?> <b><?= $club->created ? h(\App\Utility\LocaleDateParser::format($club->created, 'date')) : '—' ?></b></div>
							<div><?= __('Modified:') ?> <b><?= $club->modified ? h(\App\Utility\LocaleDateParser::format($club->modified, 'date')) : '—' ?></b></div>
						</div>
					<?php endif; ?>
					<a role="button" href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary" id="btn-close-form" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h('<b>' . __('Close window') . '</b>') ?>">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?= $this->Form->create($club, [
					'id' => 'form-horizontal',
					'autocomplete' => 'off',
				]) ?>
					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('name', __('Name:'), ['for' => 'name']) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('name', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'name',
								'autofocus' => true,
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('short_name', __('Short name:'), ['for' => 'short-name', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('short_name', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'short-name',
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('country_id', __('Country:'), ['for' => 'country-id']) ?>
						<div class="col-12 col-md-10 col-xl-8">
							<?= $this->Form->control('country_id', [
								'label' => false,
								'type' => 'select',
								'options' => $countryOptions,
								'value' => $countryId > 0 ? $countryId : null,
								'class' => 'form-select js-club-country-select',
								'id' => 'country-id',
								'data-placeholder' => __('Select country...'),
								'data-ajax-url' => $this->Url->build(['action' => 'countryOptions']),
							]) ?>
							<div class="form-text"><?= __('Default is your country. The last country you chose is offered first next time.') ?></div>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('city_id', __('City:'), ['for' => 'city-id', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-8">
							<?= $this->Form->control('city_id', [
								'label' => false,
								'type' => 'select',
								'options' => $cityOptions,
								'empty' => true,
								'value' => $cityId,
								'class' => 'form-select js-club-city-select',
								'id' => 'city-id',
								'data-placeholder' => __('Start typing the city name...'),
								'data-ajax-url' => $this->Url->build(['action' => 'cityOptions']),
							]) ?>
							<div class="form-text"><?= __('Cities are listed for the selected country only. Start typing the city name.') ?></div>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('club_president_id', __('Club president:'), ['for' => 'club-president-id', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-8">
							<?= $this->Form->control('club_president_id', [
								'label' => false,
								'type' => 'select',
								'options' => $presidentOptions,
								'empty' => true,
								'value' => $clubPresidentId !== '' ? $clubPresidentId : null,
								'class' => 'form-select js-club-president-select',
								'id' => 'club-president-id',
								'data-placeholder' => __('Select club president...'),
								'data-ajax-url' => $this->Url->build(['action' => 'userOptions']),
							]) ?>
							<div class="form-text"><?= __('Search by name or email (same country as above; not applicants with role new). Members become club president; a president or vice president keeps their role. Leave empty to clear.') ?></div>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('address', __('Address:'), ['for' => 'address', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-8">
							<?= $this->Form->control('address', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'address',
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('email', __('Email:'), ['for' => 'email', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('email', [
								'label' => false,
								'type' => 'email',
								'class' => 'form-control',
								'id' => 'email',
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('phone', __('Phone:'), ['for' => 'phone', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('phone', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'phone',
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('web', __('Website:'), ['for' => 'web', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-8">
							<?= $this->Form->control('web', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'web',
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('facebook', __('Facebook:'), ['for' => 'facebook', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-8">
							<?= $this->Form->control('facebook', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'facebook',
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('insta', __('Instagram:'), ['for' => 'insta', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-8">
							<?= $this->Form->control('insta', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'insta',
							]) ?>
						</div>
					</div>

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
							<div class="form-text"><?= __('Disabled clubs cannot be selected on the membership profile.') ?></div>
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
								$club->pos,
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
						<a href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary ms-3">
							<span class="btn-label"><i class="fa fa-times"></i></span><?= __('Cancel') ?>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
