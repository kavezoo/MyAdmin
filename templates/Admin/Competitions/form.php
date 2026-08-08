<?php
/**
 * Admin — competition add/edit form.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Competition $competition
 * @var array<int, string> $clubOptions
 * @var array<int, string> $countryOptions
 */
$isEdit = !$competition->isNew();
$clubOptions = $clubOptions ?? [];
$countryOptions = $countryOptions ?? [];

$dateVal = static function ($value): string {
	if ($value instanceof \DateTimeInterface) {
		return $value->format('Y-m-d');
	}
	if (is_object($value) && method_exists($value, 'format')) {
		return (string)$value->format('Y-m-d');
	}
	return is_string($value) ? substr($value, 0, 10) : '';
};
$dtVal = static function ($value): string {
	if ($value instanceof \DateTimeInterface) {
		return $value->format('Y-m-d H:i:s');
	}
	if (is_object($value) && method_exists($value, 'format')) {
		return (string)$value->format('Y-m-d H:i:s');
	}
	return is_string($value) ? trim($value) : '';
};

$this->Html->css([
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
	'/plugins/tempus-dominus/css/tempus-dominus.min',
	'/plugins/trumbowyg/ui/trumbowyg.min',
	'pages/form',
], ['block' => true]);

$config = [
	'indexUrl' => $this->Url->build(['action' => 'index']),
	'dateFormat' => \App\Utility\LocaleDateParser::jsConfig(),
	'numberFormat' => \App\Utility\LocaleNumberParser::jsConfig(),
	'clubsForCountryUrl' => $this->Url->build(['prefix' => false, 'plugin' => false, 'controller' => 'Users', 'action' => 'clubsForCountry']),
	'selectClub' => __('Select club...'),
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);
$this->Html->script([
	'/plugins/select2-4.1.0/js/select2.full.min',
	'popper',
	'/plugins/tempus-dominus/js/tempus-dominus.min',
	'/plugins/inputmask/jquery.inputmask.min',
	'/plugins/trumbowyg/trumbowyg.min',
	'/plugins/trumbowyg/langs/hu',
	'pages/form',
], ['block' => 'scriptBottom']);
$this->Html->scriptBlock(<<<'JS'
(function ($) {
	$(function () {
		var cfg = (window.MyAdmin && window.MyAdmin.config) || {};
		var $country = $('#country-id');
		var $club = $('#club-id');
		if (!$country.length || !$club.length || !cfg.clubsForCountryUrl) {
			return;
		}
		var includeClubId = parseInt($club.data('include-club-id'), 10) || 0;
		function refillClubs(countryId) {
			var params = { country_id: countryId || '' };
			if (includeClubId > 0) {
				params.include_club_id = includeClubId;
			}
			$.ajax({
				url: cfg.clubsForCountryUrl,
				method: 'GET',
				dataType: 'json',
				cache: false,
				data: params
			}).done(function (data) {
				var clubs = (data && data.clubs) ? data.clubs : {};
				var current = $club.val();
				$club.empty();
				$club.append($('<option>', { value: '', text: cfg.selectClub || '' }));
				Object.keys(clubs).forEach(function (id) {
					$club.append($('<option>', { value: id, text: clubs[id] }));
				});
				if (current && clubs[current]) {
					$club.val(String(current));
				} else {
					$club.val('');
				}
				if ($club.hasClass('select2-hidden-accessible')) {
					$club.trigger('change.select2');
				} else {
					$club.trigger('change');
				}
			});
		}
		$country.on('change', function () {
			refillClubs(parseInt($country.val(), 10) || 0);
		});
	});
})(jQuery);
JS
, ['block' => 'scriptBottom']);

$picker = function (
	string $field,
	string $label,
	string $type,
	mixed $raw,
	string $format
) use ($dateVal, $dtVal): void {
	$pickerId = 'picker-' . str_replace('_', '-', $field);
	$inputId = str_replace('_', '-', $field);
	$iso = $type === 'date' ? $dateVal($raw) : $dtVal($raw);
	?>
	<div class="form-group row mb-3">
		<?= $this->Form->adminLabel($field, $label, ['for' => $inputId]) ?>
		<div class="col-12 col-md-10 col-xl-4">
			<div class="form-group date mb-0">
				<div
					class="input-group js-tempus-picker"
					id="<?= h($pickerId) ?>"
					data-td-target-input="nearest"
					data-td-target-toggle="nearest"
					data-picker-type="<?= h($type) ?>"
					data-picker-value="<?= h($iso) ?>"
				>
					<?= $this->Form->control($field, [
						'label' => false,
						'type' => 'text',
						'class' => 'form-control',
						'id' => $inputId,
						'data-td-target' => '#' . $pickerId,
						'value' => \App\Utility\LocaleDateParser::format($raw, $format),
						'autocomplete' => 'off',
						'error' => false,
					]) ?>
					<span class="input-group-text" data-td-target="#<?= h($pickerId) ?>" data-td-toggle="datetimepicker" role="button" tabindex="0">
						<i class="fa fa-calendar" aria-hidden="true"></i>
					</span>
				</div>
				<?= $this->element('admin/field_error', ['field' => $field]) ?>
			</div>
		</div>
	</div>
	<?php
};
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-trophy"></i> <?= $isEdit ? __('Edit competition') : __('New competition') ?></h3>
					<?= $isEdit ? h((string)$competition->name) : __('Create a new record.') ?>
				</div>
				<div class="float-right">
					<a role="button" href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary" id="btn-close-form">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?= $this->Form->create($competition, ['id' => 'form-horizontal', 'autocomplete' => 'off']) ?>
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
						<?= $this->Form->adminLabel('title', __('Title:'), ['for' => 'title']) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('title', ['label' => false, 'class' => 'form-control', 'id' => 'title']) ?>
						</div>
					</div>
					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('subtitle', __('Subtitle:'), ['for' => 'subtitle', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('subtitle', ['label' => false, 'class' => 'form-control', 'id' => 'subtitle']) ?>
						</div>
					</div>
					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('country_id', __('Country:'), ['for' => 'country-id']) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('country_id', [
								'label' => false,
								'type' => 'select',
								'options' => $countryOptions,
								'empty' => __('Select country...'),
								'class' => 'js-example-basic-single form-select',
								'id' => 'country-id',
							]) ?>
						</div>
					</div>
					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('club_id', __('Organizer club:'), ['for' => 'club-id']) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('club_id', [
								'label' => false,
								'type' => 'select',
								'options' => $clubOptions,
								'empty' => __('Select club...'),
								'class' => 'js-example-basic-single form-select',
								'id' => 'club-id',
								'data-include-club-id' => $isEdit ? (int)$competition->club_id : 0,
							]) ?>
							<div class="form-text">
								<?= __('Only clubs with national membership fee paid for this year can organize a competition.') ?>
							</div>
						</div>
					</div>
					<div class="form-group row mb-3">
						<div class="d-none d-md-block col-md-2"></div>
						<div class="col-12 col-md-10">
							<div class="form-check form-switch">
								<?= $this->Form->checkbox('national_competition', ['class' => 'form-check-input', 'id' => 'national-competition']) ?>
								<?= $this->Form->adminLabel('national_competition', __('National competition'), [
									'for' => 'national-competition',
									'class' => 'form-check-label',
									'required' => false,
								]) ?>
							</div>
						</div>
					</div>

					<?php
					$picker('first_date_of_application', __('Application from:'), 'date', $competition->first_date_of_application, 'date');
					$picker('application_deadline', __('Application deadline:'), 'date', $competition->application_deadline, 'date');
					$picker('competition_datetime', __('Competition datetime:'), 'datetime', $competition->competition_datetime, 'datetime_short');
					$picker('start_datetime', __('Start:'), 'datetime', $competition->start_datetime, 'datetime_short');
					$picker('end_datetime', __('End:'), 'datetime', $competition->end_datetime, 'datetime_short');
					?>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('description', __('Description:'), ['for' => 'description', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xxl-9">
							<?= $this->Form->control('description', [
								'label' => false,
								'type' => 'textarea',
								'rows' => 8,
								'class' => 'form-control editor',
								'id' => 'description',
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('minimum_team_size', __('Min. team size:'), ['for' => 'minimum-team-size']) ?>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('minimum_team_size', \App\Utility\LocaleNumberParser::formIntegerOptions(
								$competition->minimum_team_size,
								['id' => 'minimum-team-size', 'label' => false]
							)) ?>
						</div>
					</div>

					<div class="form-text mb-2 col-12 col-md-10 offset-md-2">
						<?= __('Optional pipe type labels shown on the member application form (leave empty if unused). Quantities are entered per applicant.') ?>
					</div>
					<?php for ($i = 1; $i <= 3; $i++): ?>
						<?php
						$titleField = 'racing_pipe_' . $i . '_title';
						$defaultHint = $i === 1 ? __('e.g. Racing pipe request') : '';
						?>
						<div class="form-group row mb-3">
							<?= $this->Form->adminLabel($titleField, __('Racing pipe {0} title:', $i), ['for' => str_replace('_', '-', $titleField), 'required' => false]) ?>
							<div class="col-12 col-md-10 col-xl-5">
								<?= $this->Form->control($titleField, [
									'label' => false,
									'class' => 'form-control',
									'id' => str_replace('_', '-', $titleField),
									'placeholder' => $defaultHint,
								]) ?>
							</div>
						</div>
					<?php endfor; ?>

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
						</div>
					</div>
					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('pos', __('Position:'), ['for' => 'pos', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('pos', \App\Utility\LocaleNumberParser::formIntegerOptions(
								$competition->pos,
								['id' => 'pos', 'label' => false]
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
