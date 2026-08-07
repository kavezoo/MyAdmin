<?php
/**
 * Cities add/edit.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\City $city
 * @var array<int, string> $countryOptions
 * @var array<int, string> $countyOptions
 * @var int $formCountryId
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

$isEdit = !$city->isNew();
$countryOptions = $countryOptions ?? [];
$countyOptions = $countyOptions ?? [];
$formCountryId = (int)($formCountryId ?? ($city->country_id ?? 0));
if ($formCountryId > 0 && empty($city->country_id)) {
	$city->country_id = $formCountryId;
}
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-check-square-o"></i> <?= $isEdit ? __('Edit city') : __('New city') ?></h3>
					<?= $isEdit ? __('Edit the selected record.') : __('Create a new record.') ?>
				</div>
				<div class="float-right">
					<a role="button" href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary" id="btn-close-form" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h('<b>' . __('Close window') . '</b>') ?>">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?= $this->Form->create($city, [
					'id' => 'form-horizontal',
					'autocomplete' => 'off',
				]) ?>
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

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('county_id', __('County:'), ['for' => 'county-id', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('county_id', [
								'label' => false,
								'options' => $countyOptions,
								'empty' => __('Select county...'),
								'class' => 'js-example-basic-single form-select',
								'id' => 'county-id',
								'data-placeholder' => __('Select county...'),
							]) ?>
							<div class="form-text"><?= __('Counties are listed for the selected country. Changing country reloads the county list.') ?></div>
						</div>
					</div>

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
						<?= $this->Form->adminLabel('shortname', __('Short name:'), ['for' => 'shortname', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('shortname', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'shortname',
								'maxlength' => 10,
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('zip', __('ZIP:'), ['for' => 'zip', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('zip', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'zip',
								'maxlength' => 10,
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('lat', __('Latitude:'), ['for' => 'lat', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('lat', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'lat',
								'maxlength' => 20,
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('lng', __('Longitude:'), ['for' => 'lng', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('lng', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'lng',
								'maxlength' => 20,
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('lat2', __('Latitude (import):'), ['for' => 'lat2', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('lat2', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'lat2',
								'maxlength' => 20,
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('lng2', __('Longitude (import):'), ['for' => 'lng2', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('lng2', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'lng2',
								'maxlength' => 20,
							]) ?>
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
$formReloadBase = $this->Url->build($isEdit ? ['action' => 'edit', $city->id] : ['action' => 'add']);
$this->Html->scriptBlock(<<<JS
(function ($) {
	$('#country-id').on('change', function () {
		var countryId = $(this).val();
		if (!countryId) {
			return;
		}
		var url = new URL('{$formReloadBase}', window.location.origin);
		url.searchParams.set('form_country_id', countryId);
		window.location.href = url.toString();
	});
})(jQuery);
JS, ['block' => 'scriptBottom']);
?>
