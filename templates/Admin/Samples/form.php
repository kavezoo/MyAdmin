<?php
/**
 * Shared Samples add/edit form
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Sample $sample
 * @var array<int, string> $parents
 * @var array<int, string> $cities
 */

$this->Html->css([
	'/plugins/tempus-dominus/css/tempus-dominus.min',
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
	'/plugins/trumbowyg/ui/trumbowyg.min',
	'/plugins/trumbowyg/plugins/colors/ui/trumbowyg.colors.min',
	'/plugins/trumbowyg/plugins/table/ui/trumbowyg.table.min',
	'/plugins/trumbowyg/plugins/highlight/ui/trumbowyg.highlight.min',
	'/plugins/trumbowyg/plugins/specialchars/ui/trumbowyg.specialchars.min',
	'pages/form',
], ['block' => true]);

$config = [
	'indexUrl' => $this->Url->build(['action' => 'index']),
	'numberFormat' => \App\Utility\LocaleNumberParser::jsConfig(),
	'dateFormat' => \App\Utility\LocaleDateParser::jsConfig(),
	'trumbowygSvgPath' => $this->Url->build('/plugins/trumbowyg/ui/icons.svg'),
	'trumbowygUploadPath' => $this->Url->build('/plugins/trumbowyg/texteditor-upload.php'),
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);

$this->Html->script([
	'popper',
	'/plugins/tempus-dominus/js/tempus-dominus.min',
	'/plugins/inputmask/jquery.inputmask.min',
	'/plugins/select2-4.1.0/js/select2.full.min',
	'/plugins/trumbowyg/trumbowyg.min',
	'/plugins/trumbowyg/plugins/colors/trumbowyg.colors.min',
	'/plugins/trumbowyg/plugins/fontsize/trumbowyg.fontsize.min',
	'/plugins/trumbowyg/plugins/fontfamily/trumbowyg.fontfamily.min',
	'/plugins/trumbowyg/plugins/lineheight/trumbowyg.lineheight.min',
	'/plugins/trumbowyg/plugins/table/trumbowyg.table.min',
	'/plugins/trumbowyg/plugins/upload/trumbowyg.upload.min',
	'/plugins/trumbowyg/plugins/base64/trumbowyg.base64.min',
	'/plugins/trumbowyg/plugins/noembed/trumbowyg.noembed.min',
	'/plugins/trumbowyg/plugins/preformatted/trumbowyg.preformatted.min',
	'/plugins/trumbowyg/plugins/highlight/trumbowyg.highlight.min',
	'/plugins/trumbowyg/plugins/specialchars/trumbowyg.specialchars.min',
	'/plugins/trumbowyg/plugins/history/trumbowyg.history.min',
	'/plugins/trumbowyg/plugins/pasteimage/trumbowyg.pasteimage.min',
	'pages/form',
], ['block' => 'scriptBottom']);

$isEdit = !$sample->isNew();
?>

<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-check-square-o"></i> <?= $isEdit ? __('Edit sample') : __('New sample') ?></h3>
					<?= $isEdit ? __('Edit the selected record.') : __('Create a new record.') ?>
				</div>
				<div class="float-right d-flex align-items-center gap-3">
					<?php if ($isEdit): ?>
						<div class="text-end text-muted small lh-sm">
							<div><?= __('Created:') ?> <b><?= $sample->created ? h(\App\Utility\LocaleDateParser::format($sample->created, 'date')) : '—' ?></b></div>
							<div><?= __('Modified:') ?> <b><?= $sample->modified ? h(\App\Utility\LocaleDateParser::format($sample->modified, 'date')) : '—' ?></b></div>
						</div>
					<?php endif; ?>
					<a role="button" href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary" id="btn-close-form" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h('<b>' . __('Close window') . '</b>') ?>">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>

			<div class="card-body">
				<?= $this->Form->create($sample, [
					'id' => 'form-horizontal',
					'autocomplete' => 'off',
				]) ?>

					<div class="form-group row">
						<label for="parent-id" class="col-sm-3 col-md-2 col-form-label"><?= __('Parent:') ?></label>
						<div class="col-12 col-md-10 col-xl-5">
							<div class="select2-with-add">
								<div class="select2-with-add-field">
									<?= $this->Form->control('parent_id', [
										'label' => false,
										'options' => $parents,
										'empty' => __('Select parent...'),
										'class' => 'js-example-basic-single form-select',
										'id' => 'parent-id',
										'error' => false,
									]) ?>
								</div>
								<button
									type="button"
									class="btn btn-outline-secondary btn-select2-add"
									data-select2-target="#parent-id"
									data-create-url="<?= h($this->Url->build(['action' => 'select2Create'])) ?>"
									data-bs-toggle="modal"
									data-bs-target="#modalSelect2AddParent"
									aria-label="<?= h(__('Add a new value to the list')) ?>"
								>
									<i class="fa fa-plus" aria-hidden="true"></i>
								</button>
							</div>
							<?= $this->element('admin/field_error', ['field' => 'parent_id']) ?>
						</div>
					</div>

					<?= $this->element('admin/form_language_fields', [
						'entity' => $sample,
						'formLanguageTabs' => $formLanguageTabs ?? [],
						'defaultLocale' => $formDefaultLocale ?? 'en_GB',
						'i18nFields' => [
							[
								'name' => 'name',
								'label' => __('Name:'),
								'type' => 'text',
							],
							[
								'name' => 'description',
								'label' => __('Description:'),
								'type' => 'editor',
								'rows' => 8,
							],
						],
					]) ?>

					<div class="form-group row">
						<label for="szam" class="col-sm-3 col-md-2 col-form-label"><?= __('Number:') ?></label>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('szam', \App\Utility\LocaleNumberParser::formIntegerOptions(
								$sample->szam,
								['id' => 'szam']
							)) ?>
						</div>
					</div>

					<div class="form-group row">
						<label for="netto" class="col-sm-3 col-md-2 col-form-label"><?= __('Net:') ?></label>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('netto', \App\Utility\LocaleNumberParser::formDecimalOptions(
								$sample->netto,
								2,
								['id' => 'netto']
							)) ?>
						</div>
					</div>

					<div class="form-group row">
						<label for="datum" class="col-sm-3 col-md-2 col-form-label"><?= __('Date:') ?></label>
						<div class="col-12 col-md-10 col-xl-4">
							<div class="form-group date mb-0">
								<div
									class="input-group js-tempus-picker"
									id="picker-datum"
									data-td-target-input="nearest"
									data-td-target-toggle="nearest"
									data-picker-type="date"
									data-picker-value="<?= $sample->datum ? h($sample->datum->format('Y-m-d')) : '' ?>"
								>
									<?= $this->Form->control('datum', [
										'label' => false,
										'type' => 'text',
										'class' => 'form-control',
										'placeholder' => __('Date'),
										'id' => 'datum',
										'data-td-target' => '#picker-datum',
										'value' => \App\Utility\LocaleDateParser::format($sample->datum, 'date'),
										'autocomplete' => 'off',
										'error' => false,
									]) ?>
									<span class="input-group-text" data-td-target="#picker-datum" data-td-toggle="datetimepicker" role="button" tabindex="0">
										<i class="fa fa-calendar" aria-hidden="true"></i>
									</span>
								</div>
								<?= $this->element('admin/field_error', ['field' => 'datum']) ?>
							</div>
						</div>
					</div>

					<div class="form-group row">
						<label for="ido" class="col-sm-3 col-md-2 col-form-label"><?= __('Time:') ?></label>
						<div class="col-12 col-md-10 col-xl-4">
							<div class="form-group time mb-0">
								<div
									class="input-group js-tempus-picker"
									id="picker-ido"
									data-td-target-input="nearest"
									data-td-target-toggle="nearest"
									data-picker-type="time"
									data-picker-value="<?= $sample->ido ? h($sample->ido->format('H:i:s')) : '' ?>"
								>
									<?= $this->Form->control('ido', [
										'label' => false,
										'type' => 'text',
										'class' => 'form-control',
										'placeholder' => __('Time'),
										'id' => 'ido',
										'data-td-target' => '#picker-ido',
										'value' => \App\Utility\LocaleDateParser::format($sample->ido, 'time'),
										'autocomplete' => 'off',
										'error' => false,
									]) ?>
									<span class="input-group-text" data-td-target="#picker-ido" data-td-toggle="datetimepicker" role="button" tabindex="0">
										<i class="fa fa-clock-o" aria-hidden="true"></i>
									</span>
								</div>
								<?= $this->element('admin/field_error', ['field' => 'ido']) ?>
							</div>
						</div>
					</div>

					<div class="form-group row">
						<label for="datumido" class="col-sm-3 col-md-2 col-form-label"><?= __('Date and time:') ?></label>
						<div class="col-12 col-md-10 col-xl-4">
							<div class="form-group datetime mb-0">
								<div
									class="input-group js-tempus-picker"
									id="picker-datumido"
									data-td-target-input="nearest"
									data-td-target-toggle="nearest"
									data-picker-type="datetime"
									data-picker-value="<?= $sample->datumido ? h($sample->datumido->format('Y-m-d H:i:s')) : '' ?>"
								>
									<?= $this->Form->control('datumido', [
										'label' => false,
										'type' => 'text',
										'class' => 'form-control',
										'placeholder' => __('Date and time'),
										'id' => 'datumido',
										'data-td-target' => '#picker-datumido',
										'value' => \App\Utility\LocaleDateParser::format($sample->datumido, 'datetime'),
										'autocomplete' => 'off',
										'error' => false,
									]) ?>
									<span class="input-group-text" data-td-target="#picker-datumido" data-td-toggle="datetimepicker" role="button" tabindex="0">
										<i class="fa fa-calendar" aria-hidden="true"></i>
									</span>
								</div>
								<?= $this->element('admin/field_error', ['field' => 'datumido']) ?>
							</div>
						</div>
					</div>

					<div class="form-group row">
						<label for="cities-ids" class="col-sm-3 col-md-2 col-form-label"><?= __('Cities:') ?></label>
						<div class="col-12 col-md-10 col-xl-10 col-xxl-9">
							<div class="select2-with-add">
								<div class="select2-with-add-field">
									<?= $this->Form->control('cities._ids', [
										'label' => false,
										'options' => $cities,
										'multiple' => true,
										'class' => 'js-example-basic-multiple form-select',
										'id' => 'cities-ids',
										'data-placeholder' => __('Select cities...'),
										'error' => false,
									]) ?>
								</div>
								<button
									type="button"
									class="btn btn-outline-secondary btn-select2-add"
									data-select2-target="#cities-ids"
									data-create-url="<?= h($this->Url->build(['action' => 'select2CreateCity'])) ?>"
									data-bs-toggle="modal"
									data-bs-target="#modalSelect2AddCity"
									aria-label="<?= h(__('Add a new value to the list')) ?>"
								>
									<i class="fa fa-plus" aria-hidden="true"></i>
								</button>
							</div>
							<?= $this->element('admin/field_error', ['field' => 'cities._ids']) ?>
						</div>
					</div>

					<div class="form-group row">
						<div class="d-none d-md-block col-md-2 col-form-label"></div>
						<div class="col-12 col-md-10 col-xxl-9">
							<div class="form-check form-switch">
								<?= $this->Form->checkbox('logikai', [
									'class' => 'form-check-input',
									'id' => 'logikai',
								]) ?>
								<label class="form-check-label" for="logikai"><?= __('Boolean') ?></label>
							</div>
							<?= $this->element('admin/field_error', ['field' => 'logikai']) ?>
						</div>
					</div>

					<div class="row">
						<div class="col-12 col-xxl-11">
							<hr class="my-4">
						</div>
					</div>
					<div class="form-group row">
						<div class="d-none d-md-block col-md-2 col-form-label"></div>
						<div class="col-12 col-md-10 col-xxl-9">
							<div class="form-check form-switch">
								<?= $this->Form->checkbox('visible', [
									'class' => 'form-check-input',
									'id' => 'visible',
								]) ?>
								<label class="form-check-label" for="visible"><?= __('Visible') ?></label>
							</div>
							<?= $this->element('admin/field_error', ['field' => 'visible']) ?>
						</div>
					</div>

					<div class="form-group row">
						<label for="pos" class="col-sm-3 col-md-2 col-form-label"><?= __('Position:') ?></label>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('pos', \App\Utility\LocaleNumberParser::formIntegerOptions(
								$sample->pos,
								['id' => 'pos']
							)) ?>
						</div>
					</div>

				<?= $this->Form->end() ?>
			</div>

			<div class="card-footer">
				<div class="row">
					<div class="col-12 col-md-10 col-xxl-9 offset-md-2">
						<button type="submit" form="form-horizontal" class="btn btn-success" id="btn-save">
							<span class="btn-label"><i class="fa fa-save"></i></span><?= __('Save') ?>
						</button>
						<a role="button" href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary ms-3" id="btn-cancel">
							<span class="btn-label"><i class="fa fa-times"></i></span><?= __('Cancel') ?>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
/**
 * Select2 „+” modals — single + multiple.
 * Class `.modal-select2-add` + `.select2-add-form`: future multi-field create;
 * list display name = field with `data-select2-text="1"` (usually `name`).
 */
?>
<div class="modal fade modal-select2-add" id="modalSelect2AddParent" tabindex="-1" aria-labelledby="modalSelect2AddParentLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modalSelect2AddParentLabel"><?= __('New parent') ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(__('Close')) ?>"></button>
			</div>
			<div class="modal-body">
				<form class="select2-add-form" id="formSelect2AddParent" onsubmit="return false;">
					<div class="mb-0">
						<label for="inputSelect2ParentName" class="form-label"><?= __('Name') ?></label>
						<input type="text" class="form-control" id="inputSelect2ParentName" name="name" data-select2-text="1" required autofocus placeholder="<?= h(__('Enter a new value...')) ?>" autocomplete="off">
						<div class="invalid-feedback"><?= __('Please enter a value.') ?></div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('Cancel') ?></button>
				<button type="button" class="btn btn-primary btn-select2-add-save"><?= __('Save') ?></button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade modal-select2-add" id="modalSelect2AddCity" tabindex="-1" aria-labelledby="modalSelect2AddCityLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="modalSelect2AddCityLabel"><?= __('New city') ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(__('Close')) ?>"></button>
			</div>
			<div class="modal-body">
				<form class="select2-add-form" id="formSelect2AddCity" onsubmit="return false;">
					<div class="mb-0">
						<label for="inputSelect2CityName" class="form-label"><?= __('Name') ?></label>
						<input type="text" class="form-control" id="inputSelect2CityName" name="name" data-select2-text="1" required autofocus placeholder="<?= h(__('Enter a new value...')) ?>" autocomplete="off">
						<div class="invalid-feedback"><?= __('Please enter a value.') ?></div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('Cancel') ?></button>
				<button type="button" class="btn btn-primary btn-select2-add-save"><?= __('Save') ?></button>
			</div>
		</div>
	</div>
</div>
