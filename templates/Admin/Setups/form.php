<?php
/**
 * Setup add/edit — type-dependent value widgets.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Setup $setup
 * @var array<string, string> $setupTypeOptions
 * @var string $setupValueForm
 */
use App\Utility\SetupEditBy;
use App\Utility\SetupValue;

$this->Html->css(['pages/form', 'pages/setups_form'], ['block' => true]);

$isEdit = !$setup->isNew();
$type = (string)($setup->type ?: SetupValue::TYPE_STRING);
$rawValue = $setup->value !== null ? (string)$setup->value : '';
$formValue = $setupValueForm ?? '';
$boolChecked = ($rawValue === '1' || $rawValue === 'true');
$editByOptions = $setupEditByOptions ?? SetupEditBy::options();
$hasSecretStored = $isEdit && $type === SetupValue::TYPE_SECRET && $rawValue !== '';
$canEditMeta = (bool)($canEditSetupMetadata ?? true);
$metaReadonly = $isEdit && !$canEditMeta;

$config = [
	'indexUrl' => $this->Url->build($indexListUrl ?? ['action' => 'index']),
	'numberFormat' => \App\Utility\LocaleNumberParser::jsConfig(),
	'dateFormat' => \App\Utility\LocaleDateParser::jsConfig(),
	'setupTypes' => SetupValue::typeList(),
	'currentType' => $type,
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
	'pages/form',
	'pages/setups_form',
], ['block' => 'scriptBottom']);
$this->Html->css(['/plugins/tempus-dominus/css/tempus-dominus.min'], ['block' => true]);
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-check-square-o"></i> <?= $isEdit ? __('Edit setup') : __('New setup') ?></h3>
					<?= $isEdit ? __('Edit the selected record.') : __('Create a new record.') ?>
					<?php if (!$isEdit): ?>
						<div class="form-text mb-0"><?= __('A matching record will be created for every visible country.') ?></div>
					<?php endif; ?>
				</div>
				<div class="float-right">
					<a role="button" href="<?= h($this->Url->build($indexListUrl ?? ['action' => 'index'])) ?>" class="btn btn-outline-secondary"><i class="fa fa-times"></i></a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?= $this->Form->create($setup, ['id' => 'form-horizontal', 'autocomplete' => 'off']) ?>

					<div class="form-group row mb-3">
						<label class="col-sm-3 col-md-2 col-form-label"><?= __('Country:') ?></label>
						<div class="col-12 col-md-10 col-xl-5">
							<p class="form-control-plaintext mb-0"><?= h($workingCountryLabel ?? '') ?></p>
							<?= $this->Form->hidden('country_id') ?>
							<?php if (!$isEdit): ?>
								<div class="form-text"><?= __('New setups are saved for all countries; the list shows the working country.') ?></div>
							<?php endif; ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('name', __('Name:'), ['for' => 'name']) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('name', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'name',
								'autofocus' => !$metaReadonly,
								'readonly' => $metaReadonly,
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('slug', __('Slug:'), ['for' => 'slug']) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('slug', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'slug',
								'placeholder' => __('e.g. site_title'),
								'readonly' => $metaReadonly,
							]) ?>
							<?php if (!$metaReadonly): ?>
								<div class="form-text"><?= __('Lowercase letters, numbers and underscores only. Suggested from the name.') ?></div>
							<?php endif; ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('type', __('Type:'), ['for' => 'type']) ?>
						<div class="col-12 col-md-10 col-xl-4">
							<?= $this->Form->control('type', [
								'label' => false,
								'type' => 'select',
								'options' => $setupTypeOptions,
								'class' => 'form-select',
								'id' => 'type',
								'disabled' => $metaReadonly,
							]) ?>
							<?php if ($metaReadonly): ?>
								<?= $this->Form->hidden('type') ?>
							<?php endif; ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('edit_by', __('Editable by:'), ['for' => 'edit-by']) ?>
						<div class="col-12 col-md-10 col-xl-7">
							<?= $this->Form->control('edit_by', [
								'label' => false,
								'type' => 'select',
								'options' => $editByOptions,
								'class' => 'form-select',
								'id' => 'edit-by',
								'disabled' => $metaReadonly,
							]) ?>
							<?php if ($metaReadonly): ?>
								<?= $this->Form->hidden('edit_by') ?>
							<?php else: ?>
								<div class="form-text"><?= SetupEditBy::helpText() ?></div>
							<?php endif; ?>
						</div>
					</div>

					<div class="form-group row mb-3" id="setup-value-row">
						<?= $this->Form->adminLabel('value', __('Value:')) ?>
						<div class="col-12 col-md-10 col-xl-7" id="setup-value-widgets">

							<div class="setup-value-panel" data-setup-type="<?= h(SetupValue::TYPE_STRING) ?>">
								<input type="text" class="form-control js-setup-value" id="value-string" value="<?= h($type === SetupValue::TYPE_STRING ? $formValue : '') ?>" autocomplete="off">
							</div>

							<div class="setup-value-panel" data-setup-type="<?= h(SetupValue::TYPE_TEXT) ?>" hidden>
								<textarea class="form-control js-setup-value" id="value-text" rows="5"><?= h($type === SetupValue::TYPE_TEXT ? $formValue : '') ?></textarea>
							</div>

							<div class="setup-value-panel" data-setup-type="<?= h(SetupValue::TYPE_INTEGER) ?>" hidden>
								<input type="text" class="form-control js-setup-value js-input-integer" id="value-integer" value="<?= h($type === SetupValue::TYPE_INTEGER ? $formValue : '') ?>" autocomplete="off">
							</div>

							<div class="setup-value-panel" data-setup-type="<?= h(SetupValue::TYPE_FLOAT) ?>" hidden>
								<input type="text" class="form-control js-setup-value js-input-decimal" id="value-float" value="<?= h($type === SetupValue::TYPE_FLOAT ? $formValue : '') ?>" autocomplete="off">
							</div>

							<div class="setup-value-panel" data-setup-type="<?= h(SetupValue::TYPE_BOOLEAN) ?>" hidden>
								<div class="form-check form-switch pt-2">
									<input type="checkbox" class="form-check-input js-setup-value" id="value-boolean" value="1"<?= $type === SetupValue::TYPE_BOOLEAN && $boolChecked ? ' checked' : '' ?>>
									<label class="form-check-label" for="value-boolean"><?= __('Enabled') ?></label>
								</div>
							</div>

							<div class="setup-value-panel" data-setup-type="<?= h(SetupValue::TYPE_DATE) ?>" hidden>
								<div class="form-group date mb-0">
									<div
										class="input-group js-tempus-picker"
										id="picker-setup-date"
										data-td-target-input="nearest"
										data-td-target-toggle="nearest"
										data-picker-type="date"
										data-picker-value="<?= $type === SetupValue::TYPE_DATE ? h($rawValue) : '' ?>"
									>
										<input type="text" class="form-control js-setup-value" id="value-date" data-td-target="#picker-setup-date" value="<?= h($type === SetupValue::TYPE_DATE ? $formValue : '') ?>" autocomplete="off">
										<span class="input-group-text" data-td-target="#picker-setup-date" data-td-toggle="datetimepicker" role="button" tabindex="0">
											<i class="fa fa-calendar" aria-hidden="true"></i>
										</span>
									</div>
								</div>
							</div>

							<div class="setup-value-panel" data-setup-type="<?= h(SetupValue::TYPE_TIME) ?>" hidden>
								<div class="form-group time mb-0">
									<div
										class="input-group js-tempus-picker"
										id="picker-setup-time"
										data-td-target-input="nearest"
										data-td-target-toggle="nearest"
										data-picker-type="time"
										data-picker-value="<?= $type === SetupValue::TYPE_TIME ? h($rawValue) : '' ?>"
									>
										<input type="text" class="form-control js-setup-value" id="value-time" data-td-target="#picker-setup-time" value="<?= h($type === SetupValue::TYPE_TIME ? $formValue : '') ?>" autocomplete="off">
										<span class="input-group-text" data-td-target="#picker-setup-time" data-td-toggle="datetimepicker" role="button" tabindex="0">
											<i class="fa fa-clock-o" aria-hidden="true"></i>
										</span>
									</div>
								</div>
							</div>

							<div class="setup-value-panel" data-setup-type="<?= h(SetupValue::TYPE_DATETIME) ?>" hidden>
								<div class="form-group datetime mb-0">
									<div
										class="input-group js-tempus-picker"
										id="picker-setup-datetime"
										data-td-target-input="nearest"
										data-td-target-toggle="nearest"
										data-picker-type="datetime"
										data-picker-value="<?= $type === SetupValue::TYPE_DATETIME ? h($rawValue) : '' ?>"
									>
										<input type="text" class="form-control js-setup-value" id="value-datetime" data-td-target="#picker-setup-datetime" value="<?= h($type === SetupValue::TYPE_DATETIME ? $formValue : '') ?>" autocomplete="off">
										<span class="input-group-text" data-td-target="#picker-setup-datetime" data-td-toggle="datetimepicker" role="button" tabindex="0">
											<i class="fa fa-calendar" aria-hidden="true"></i>
										</span>
									</div>
								</div>
							</div>

							<div class="setup-value-panel" data-setup-type="<?= h(SetupValue::TYPE_JSON) ?>" hidden>
								<textarea class="form-control font-monospace js-setup-value" id="value-json" rows="8" spellcheck="false"><?= h($type === SetupValue::TYPE_JSON ? $formValue : '') ?></textarea>
								<div class="form-text"><?= __('Enter a JSON object or array.') ?></div>
							</div>

							<div class="setup-value-panel" data-setup-type="<?= h(SetupValue::TYPE_ARRAY) ?>" hidden>
								<textarea class="form-control font-monospace js-setup-value" id="value-array" rows="6" spellcheck="false"><?= h($type === SetupValue::TYPE_ARRAY ? $formValue : '') ?></textarea>
								<div class="form-text"><?= __('One value per line (or a JSON array).') ?></div>
							</div>

							<div class="setup-value-panel" data-setup-type="<?= h(SetupValue::TYPE_SECRET) ?>" hidden>
								<input type="password" class="form-control js-setup-value" id="value-secret" value="" autocomplete="new-password" placeholder="<?= $hasSecretStored ? h(__('Leave blank to keep the current value')) : '' ?>">
								<div class="form-text"><?= __('Stored encrypted. Leave blank when editing to keep the current secret.') ?></div>
							</div>

							<?= $this->element('admin/field_error', ['field' => 'value']) ?>
						</div>
					</div>

				<?= $this->Form->end() ?>
			</div>
			<div class="card-footer">
				<div class="row">
					<div class="col-12 col-md-10 col-xxl-9 offset-md-2">
						<button type="submit" form="form-horizontal" class="btn btn-success"><span class="btn-label"><i class="fa fa-save"></i></span><?= __('Save') ?></button>
						<a href="<?= h($this->Url->build($indexListUrl ?? ['action' => 'index'])) ?>" class="btn btn-outline-secondary ms-3"><span class="btn-label"><i class="fa fa-times"></i></span><?= __('Cancel') ?></a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
