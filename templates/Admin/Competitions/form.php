<?php
/**
 * Admin — competition add/edit form.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Competition $competition
 * @var array<int, string> $clubOptions
 * @var array<int, string> $countryOptions
 * @var list<array{locale: string, code: string, iso2: string, country_id: int, country_name: string}> $formLanguageTabs
 * @var string $formDefaultLocale
 */
$isEdit = !$competition->isNew();
$clubOptions = $clubOptions ?? [];
$countryOptions = $countryOptions ?? [];
$contentLocked = !empty($contentLocked ?? false);

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
	'pages/form',
], ['block' => true]);
$this->element('admin/form_summernote_assets');

$config = [
	'indexUrl' => $this->Url->build(['action' => 'index']),
	'dateFormat' => \App\Utility\LocaleDateParser::jsConfig(),
	'numberFormat' => \App\Utility\LocaleNumberParser::jsConfig(),
	'clubsForCountryUrl' => $this->Url->build(['prefix' => false, 'plugin' => false, 'controller' => 'Users', 'action' => 'clubsForCountry']),
	'selectClub' => __('Select club...'),
	'cityOptionsUrl' => $this->Url->build(['action' => 'cityOptions']),
	'selectCity' => __('Search city (name or ZIP)…'),
	'templateApplyUrl' => $this->Url->build(['controller' => 'CompetitionTextTemplates', 'action' => 'applyData']),
	'contentLocked' => $contentLocked,
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
	'pages/form',
	'pages/competition_form',
	'pages/competition_template_form',
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
	<div class="col-12 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header jeff-form-card-header">
				<div class="jeff-form-card-header__title">
					<h3><i class="fa fa-trophy"></i> <?= $isEdit ? __('Edit competition') : __('New competition') ?></h3>
					<?= $isEdit ? h((string)$competition->name) : __('Create a new record.') ?>
				</div>
				<div class="jeff-form-card-header__actions">
					<div class="form-tab">
						<ul class="nav nav-tabs" id="competitionFormTabs" role="tablist">
							<li class="nav-item" role="presentation">
								<button class="nav-link active" id="tabCompetitionBasic" data-bs-toggle="tab" data-bs-target="#tabPanelCompetitionBasic" type="button" role="tab" aria-controls="tabPanelCompetitionBasic" aria-selected="true"><?= __('Basic data') ?></button>
							</li>
							<li class="nav-item" role="presentation">
								<button class="nav-link" id="tabCompetitionDescription" data-bs-toggle="tab" data-bs-target="#tabPanelCompetitionDescription" type="button" role="tab" aria-controls="tabPanelCompetitionDescription" aria-selected="false"><?= __('Description') ?></button>
							</li>
						</ul>
					</div>
					<a role="button" href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary jeff-form-card-header__close" id="btn-close-form">
						<i class="fa fa-times"></i>
					</a>
				</div>
			</div>
			<div class="card-body">
				<?php if ($contentLocked): ?>
					<div class="alert alert-warning" role="alert">
						<?= __('The application deadline has passed. Competition details can no longer be edited.') ?>
					</div>
				<?php endif; ?>
				<?= $this->Form->create($competition, [
					'id' => 'form-horizontal',
					'autocomplete' => 'off',
					'type' => 'file',
				]) ?>
				<?php if ($contentLocked): ?>
					<fieldset disabled class="competition-form-locked">
				<?php endif; ?>
				<div class="tab-content" id="competitionFormTabContent">
					<div class="tab-pane fade show active" id="tabPanelCompetitionBasic" role="tabpanel" aria-labelledby="tabCompetitionBasic" tabindex="0">
					<?= $this->element('competitions/form_i18n_tabs', [
						'entity' => $competition,
						'formLanguageTabs' => $formLanguageTabs ?? [],
						'defaultLocale' => $formDefaultLocale ?? \App\Utility\FormLanguages::defaultLocaleForForm(),
						'tabsId' => 'formLanguageTabsBasic',
						'i18nFields' => [
							['name' => 'name', 'label' => __('Name:'), 'type' => 'text'],
							['name' => 'title', 'label' => __('Title:'), 'type' => 'text'],
							['name' => 'subtitle', 'label' => __('Subtitle:'), 'type' => 'text'],
							['name' => 'subtitle2', 'label' => __('Subtitle 2:'), 'type' => 'text'],
							['name' => 'racing_pipe_1_title', 'label' => __('Racing pipe {0} title:', 1), 'type' => 'text'],
							['name' => 'racing_pipe_2_title', 'label' => __('Racing pipe {0} title:', 2), 'type' => 'text'],
							['name' => 'racing_pipe_3_title', 'label' => __('Racing pipe {0} title:', 3), 'type' => 'text'],
							['name' => 'pipe_type', 'label' => __('Pipe type:'), 'type' => 'text'],
							['name' => 'pipe_parameters', 'label' => __('Pipe parameters:'), 'type' => 'text'],
							['name' => 'tobacco_type', 'label' => __('Tobacco type:'), 'type' => 'text'],
							['name' => 'lunch_description', 'label' => __('Lunch description:'), 'type' => 'text'],
						],
					]) ?>
					<div class="form-text mb-3 col-12 col-md-10 offset-md-2">
						<?= __('Optional pipe type labels shown on the member application form (leave empty if unused). Quantities are entered per applicant. Pipe type / tobacco / lunch description fields are used in the announcement text ({{placeholders}}).') ?>
					</div>
					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('tobacco_weight', __('Tobacco weight (g):'), ['for' => 'tobacco-weight', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('tobacco_weight', \App\Utility\LocaleNumberParser::formDecimalOptions(
								$competition->tobacco_weight,
								2,
								['id' => 'tobacco-weight', 'label' => false]
							)) ?>
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
					<?= $this->element('competitions/venue_fields', [
						'competition' => $competition,
						'cityOptions' => $cityOptions ?? [],
						'formCountryId' => (int)($formCountryId ?? $competition->country_id ?? 0),
						'cityOptionsUrl' => $this->Url->build(['action' => 'cityOptions']),
					]) ?>
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
					?>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('minimum_team_size', __('Min. team size:'), ['for' => 'minimum-team-size']) ?>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('minimum_team_size', \App\Utility\LocaleNumberParser::formIntegerOptions(
								$competition->minimum_team_size,
								['id' => 'minimum-team-size', 'label' => false]
							)) ?>
						</div>
					</div>

					<?= $this->element('competitions/fee_fields', [
						'competition' => $competition,
						'currencyOptions' => $currencyOptions ?? \App\Utility\CountryCurrency::options(),
						'defaultCurrency' => $defaultCurrency ?? \App\Utility\CountryCurrency::forCountryId((int)($formCountryId ?? $competition->country_id ?? 0)),
					]) ?>

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
					</div><?php /* /#tabPanelCompetitionBasic */ ?>

					<div class="tab-pane fade" id="tabPanelCompetitionDescription" role="tabpanel" aria-labelledby="tabCompetitionDescription" tabindex="0">
						<div class="form-group row mb-3">
							<?= $this->Form->adminLabel('competition_text_template_id', __('Text template:'), [
								'for' => 'competition-text-template-id',
								'required' => false,
							]) ?>
							<div class="col-12 col-md-10 col-xl-6">
								<?= $this->Form->control('competition_text_template_id', [
									'label' => false,
									'type' => 'select',
									'options' => $templateOptions ?? [],
									'empty' => __('Select a template to fill text fields…'),
									'class' => 'js-example-basic-single form-select',
									'id' => 'competition-text-template-id',
								]) ?>
								<div class="form-text">
									<?= __('Choosing a template fills the description text only (with {{placeholders}}). Name, title and other fields are set on Basic data.') ?>
								</div>
							</div>
						</div>
						<div class="row g-3">
							<div class="col-12 col-xl-9">
								<?= $this->element('competitions/form_i18n_tabs', [
									'entity' => $competition,
									'formLanguageTabs' => $formLanguageTabs ?? [],
									'defaultLocale' => $formDefaultLocale ?? \App\Utility\FormLanguages::defaultLocaleForForm(),
									'tabsId' => 'formLanguageTabsDescription',
									'fullWidth' => true,
									'i18nFields' => [
										[
											'name' => 'description',
											'label' => __('Description:'),
											'type' => 'editor',
											'rows' => 20,
											'class' => 'editor-tall',
											'editorHeight' => 520,
										],
									],
								]) ?>
							</div>
							<div class="col-12 col-xl-3">
								<?= $this->element('competitions/placeholder_chips', [
									'placeholderHelp' => $placeholderHelp ?? [],
								]) ?>
							</div>
						</div>
					</div>
				</div><?php /* /.tab-content */ ?>
				<?php if ($contentLocked): ?>
					</fieldset>
				<?php endif; ?>
				<?= $this->Form->end() ?>
			</div>
			<div class="card-footer">
				<div class="row">
					<div class="col-12 col-md-10 col-xxl-9 offset-md-2">
						<?php if (!$contentLocked): ?>
							<button type="submit" form="form-horizontal" class="btn btn-success">
								<span class="btn-label"><i class="fa fa-save"></i></span><?= __('Save') ?>
							</button>
						<?php endif; ?>
						<a href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary<?= $contentLocked ? '' : ' ms-3' ?>">
							<span class="btn-label"><i class="fa fa-times"></i></span><?= $contentLocked ? __('Close') : __('Cancel') ?>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
