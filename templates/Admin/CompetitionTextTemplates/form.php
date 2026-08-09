<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CompetitionTextTemplate $competitionTextTemplate
 * @var array<int, string> $countryOptions
 * @var list<array{token: string, label: string, help: string}> $placeholderHelp
 * @var list<array{locale: string, code: string, iso2: string, country_id: int, country_name: string}> $formLanguageTabs
 * @var string $formDefaultLocale
 */
$isEdit = !$competitionTextTemplate->isNew();
$countryOptions = $countryOptions ?? [];
$placeholderHelp = $placeholderHelp ?? [];

$this->Html->css([
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
	'pages/form',
], ['block' => true]);
$this->element('admin/form_summernote_assets');
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode([
		'indexUrl' => $this->Url->build(['action' => 'index']),
		'numberFormat' => \App\Utility\LocaleNumberParser::jsConfig(),
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);
$this->Html->script([
	'/plugins/select2-4.1.0/js/select2.full.min',
	'/plugins/inputmask/jquery.inputmask.min',
	'pages/form',
	'pages/competition_template_form',
], ['block' => 'scriptBottom']);
?>
<div class="row">
	<div class="col-12 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-start">
					<h3><i class="fa fa-file-text-o"></i> <?= $isEdit ? __('Edit competition text template') : __('New competition text template') ?></h3>
					<?= $isEdit ? h((string)$competitionTextTemplate->label) : __('Create a new record.') ?>
				</div>
				<div class="float-end">
					<a role="button" href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary" id="btn-close-form"><i class="fa fa-times"></i></a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?= $this->Form->create($competitionTextTemplate, ['id' => 'form-horizontal', 'autocomplete' => 'off']) ?>

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
					<?= $this->Form->adminLabel('label', __('Title:'), ['for' => 'label']) ?>
					<div class="col-12 col-md-10 col-xl-6">
						<?= $this->Form->control('label', ['label' => false, 'id' => 'label', 'class' => 'form-control', 'autofocus' => true]) ?>
						<div class="form-text"><?= __('Admin label for selecting this template (not shown on the competition page).') ?></div>
					</div>
				</div>
				<div class="form-group row mb-3">
					<div class="d-none d-md-block col-md-2"></div>
					<div class="col-12 col-md-10">
						<div class="form-check form-switch">
							<?= $this->Form->checkbox('enabled', ['class' => 'form-check-input', 'id' => 'enabled']) ?>
							<?= $this->Form->adminLabel('enabled', __('Enabled'), ['for' => 'enabled', 'class' => 'form-check-label', 'required' => false]) ?>
						</div>
					</div>
				</div>

				<div class="row mb-2">
					<div class="col-12">
						<label class="form-label fw-bold mb-2"><?= __('Text content') ?></label>
					</div>
				</div>
				<div class="row g-3 mb-3">
					<div class="col-12 col-xl-9">
						<?= $this->element('competitions/form_i18n_tabs', [
							'entity' => $competitionTextTemplate,
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
							'placeholderHelp' => $placeholderHelp,
						]) ?>
					</div>
				</div>

				<div class="row">
					<div class="col-12 col-xxl-11"><hr class="my-4"></div>
				</div>
				<div class="form-group row mb-3">
					<div class="d-none d-md-block col-md-2"></div>
					<div class="col-12 col-md-10">
						<div class="form-check form-switch">
							<?= $this->Form->checkbox('visible', ['class' => 'form-check-input', 'id' => 'visible']) ?>
							<?= $this->Form->adminLabel('visible', __('Visible'), ['for' => 'visible', 'class' => 'form-check-label']) ?>
						</div>
					</div>
				</div>
				<div class="form-group row mb-3">
					<?= $this->Form->adminLabel('pos', __('Position:'), ['for' => 'pos', 'required' => false]) ?>
					<div class="col-12 col-md-10 col-xl-3">
						<?= $this->Form->control('pos', \App\Utility\LocaleNumberParser::formIntegerOptions(
							$competitionTextTemplate->pos,
							['id' => 'pos', 'label' => false]
						)) ?>
					</div>
				</div>

				<?= $this->Form->end() ?>
			</div>
			<div class="card-footer">
				<div class="row">
					<div class="col-12 col-md-10 col-xxl-9 offset-md-2">
						<button type="submit" form="form-horizontal" class="btn btn-success"><span class="btn-label"><i class="fa fa-save"></i></span><?= __('Save') ?></button>
						<a href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary ms-3"><span class="btn-label"><i class="fa fa-times"></i></span><?= __('Cancel') ?></a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
