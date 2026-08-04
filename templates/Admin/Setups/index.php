<?php
/**
 * Setups index — filtered by Admin working country.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Setup> $setups
 * @var array<string, string> $setupTypeOptions
 * @var array<string, string> $setupEditByOptions
 * @var int $workingCountryId
 * @var string $workingCountryLabel
 * @var array<int, string> $countryOptions
 */
use App\Utility\SetupEditBy;
use App\Utility\SetupValue;

$this->Html->css([
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
	'pages/index',
	'pages/setups_index',
], ['block' => true]);

$rowDoubleClickAction = 'modal';
$numberDecimals = ['integer' => 0, 'decimal' => 2];
$showIdColumn = true;
$showVisibleColumn = true;
$showCreatedColumn = true;
$showModifiedColumn = true;
$showTimestampColumn = $showCreatedColumn || $showModifiedColumn;

$indexColspan = 6; // name, slug, type, value, edit_by, actions
if ($showIdColumn) {
	$indexColspan++;
}
$indexColspan++; // pos
if ($showVisibleColumn) {
	$indexColspan++;
}
if ($showTimestampColumn) {
	$indexColspan++;
}

$tooltipDetails = '<b>' . __('View details') . '</b><br>' . __('View the selected record details.');
$tooltipEdit = '<b>' . __('Edit') . '</b><br>' . __('Edit the selected record.');
$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.');

$rowDoubleClickHints = [
	'modal' => __('Double-click a row to view the record details.'),
	'edit' => __('Double-click a row to edit the record.'),
	'none' => '',
];
$rowDoubleClickHint = $rowDoubleClickHints[$rowDoubleClickAction] ?? $rowDoubleClickHints['modal'];

$editByOptions = $setupEditByOptions ?? SetupEditBy::options();

$config = [
	'rowDoubleClickAction' => $rowDoubleClickAction,
	'recordGetUrl' => $this->Url->build(['action' => 'recordGet']),
	'editUrl' => $this->Url->build(['action' => 'edit']),
	'viewUrl' => $this->Url->build(['action' => 'view']),
	'deleteUrl' => $this->Url->build(['action' => 'delete']),
	'recordFieldLabels' => [
		'id' => __('ID'),
		'name' => __('Name'),
		'slug' => __('Slug'),
		'type' => __('Type'),
		'edit_by' => __('Editable by'),
		'value' => __('Value'),
		'pos' => __('Position'),
		'visible' => __('Visible'),
		'created' => __('Created'),
		'modified' => __('Modified'),
	],
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);
$this->Html->script([
	'/plugins/select2-4.1.0/js/select2.full.min',
	'pages/index',
	'pages/setups_index',
], ['block' => 'scriptBottom']);

$typeOptions = $setupTypeOptions ?? SetupValue::typeOptions();
$workingCountryId = (int)($workingCountryId ?? 0);
$workingCountryLabel = (string)($workingCountryLabel ?? '');
$countryOptions = $countryOptions ?? [];
$canDeleteRows = (bool)($canDeleteSetup ?? false);
$setupCount = is_object($setups) && method_exists($setups, 'count')
	? $setups->count()
	: (is_countable($setups) ? count($setups) : 0);
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-cogs"></i> <?= __('Setups') ?></h3>
					<?php if ($workingCountryLabel !== ''): ?>
						<div class="text-muted">
							<?= h(__('Listing settings for {0}', $workingCountryLabel)) ?>
						</div>
					<?php endif; ?>
					<?php if ($rowDoubleClickHint !== ''): ?>
						<?= h($rowDoubleClickHint) ?>
					<?php endif; ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<?php if (!empty($canChangeCountry) && $countryOptions !== []): ?>
						<?= $this->element('admin/working_country_select', compact('workingCountryId', 'countryOptions')) ?>
					<?php endif; ?>
					<?= $this->element('admin/table_search') ?>
					<?= $this->element('admin/index_pagination') ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-2">
				<table class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table">
					<thead>
						<tr>
							<?php if ($showIdColumn): ?>
								<th scope="col" class="number id"><?= $this->Paginator->sort('id', '#') ?></th>
							<?php endif; ?>
							<th scope="col" class="string name"><?= $this->Paginator->sort('name', __('Name')) ?></th>
							<th scope="col" class="string slug"><?= $this->Paginator->sort('slug', __('Slug')) ?></th>
							<th scope="col" class="string type"><?= $this->Paginator->sort('type', __('Type')) ?></th>
							<th scope="col" class="string value"><?= __('Value') ?></th>
							<th scope="col" class="string edit-by"><?= $this->Paginator->sort('edit_by', __('Editable by')) ?></th>
							<th scope="col" class="number pos"><?= $this->Paginator->sort('pos', __('Position')) ?></th>
							<?php if ($showVisibleColumn): ?>
								<th scope="col" class="boolean visible"><?= $this->Paginator->sort('visible', __('Visible')) ?></th>
							<?php endif; ?>
							<?php if ($showTimestampColumn): ?>
								<th scope="col" class="datetime<?= $showCreatedColumn ? ' created' : '' ?><?= $showModifiedColumn ? ' modified' : '' ?>">
									<?php if ($showCreatedColumn): ?>
										<?= $this->Paginator->sort('created', __('Created')) ?>
									<?php endif; ?>
									<?php if ($showModifiedColumn): ?>
										<?= $this->Paginator->sort('modified', __('Modified')) ?>
									<?php endif; ?>
								</th>
							<?php endif; ?>
							<th scope="col" class="actions"><?= __('Actions') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($setups as $setup): ?>
							<?php
							$isLastVisited = isset($lastVisitedId) && (int)$lastVisitedId === (int)$setup->id;
							$type = (string)$setup->type;
							$typeLabel = $typeOptions[$type] ?? $type;
							$editBy = (string)($setup->edit_by ?? SetupEditBy::ADMIN);
							$editByLabel = $editByOptions[$editBy] ?? $editBy;
							$valuePreview = SetupValue::formatForDisplay($type, $setup->value);
							?>
							<tr id="record-<?= (int)$setup->id ?>" data-id="<?= (int)$setup->id ?>" data-can-delete="<?= $canDeleteRows ? '1' : '0' ?>"<?= $isLastVisited ? ' class="last-visited"' : '' ?>>
								<?php if ($showIdColumn): ?>
									<td class="number id"><?= h($setup->id) ?></td>
								<?php endif; ?>
								<td class="string name"><?= h($setup->name) ?></td>
								<td class="string slug"><code><?= h($setup->slug) ?></code></td>
								<td class="string type"><span class="badge text-bg-secondary"><?= h($typeLabel) ?></span></td>
								<td class="string value">
									<?php if ($type === SetupValue::TYPE_BOOLEAN): ?>
										<?= ((string)$setup->value === '1')
											? '<i class="fa fa-check text-success"></i>'
											: '<i class="fa fa-times text-danger"></i>' ?>
									<?php elseif ($type === SetupValue::TYPE_SECRET): ?>
										<code><?= h($valuePreview) ?></code>
									<?php else: ?>
										<?= h($valuePreview) ?>
									<?php endif; ?>
								</td>
								<td class="string edit-by"><span class="small"><?= h($editByLabel) ?></span></td>
								<td class="number pos text-end"><?= h(\App\Utility\LocaleNumberParser::format($setup->pos, decimals: $numberDecimals['integer'])) ?></td>
								<?php if ($showVisibleColumn): ?>
									<td class="boolean visible">
										<?= $setup->visible
											? '<i class="fa fa-check text-success"></i>'
											: '<i class="fa fa-times text-danger"></i>' ?>
									</td>
								<?php endif; ?>
								<?php if ($showTimestampColumn): ?>
									<td class="datetime<?= $showCreatedColumn ? ' created' : '' ?><?= $showModifiedColumn ? ' modified' : '' ?>">
										<?php if ($showCreatedColumn): ?>
											<?= $setup->created ? h(\App\Utility\LocaleDateParser::format($setup->created, 'datetime_short')) : '' ?>
										<?php endif; ?>
										<?php if ($showCreatedColumn && $showModifiedColumn && $setup->modified): ?>
											<br>
										<?php endif; ?>
										<?php if ($showModifiedColumn): ?>
											<?= $setup->modified ? h(\App\Utility\LocaleDateParser::format($setup->modified, 'datetime_short')) : '' ?>
										<?php endif; ?>
									</td>
								<?php endif; ?>
								<td class="actions">
									<?= $this->Html->link(
										'<i class="fa fa-eye"></i>',
										['action' => 'view', $setup->id],
										[
											'escape' => false,
											'role' => 'button',
											'class' => 'btn btn-outline-info',
											'data-bs-toggle' => 'tooltip',
											'data-bs-placement' => 'top',
											'data-bs-html' => 'true',
											'title' => $tooltipDetails,
										]
									) ?>
									<?= $this->Html->link(
										'<i class="fa fa-pencil"></i>',
										['action' => 'edit', $setup->id],
										[
											'escape' => false,
											'role' => 'button',
											'class' => 'btn btn-outline-primary',
											'data-bs-toggle' => 'tooltip',
											'data-bs-placement' => 'top',
											'data-bs-html' => 'true',
											'title' => $tooltipEdit,
										]
									) ?>
									<?php if ($canDeleteRows): ?>
									<a role="button" href="#" class="btn btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDelete) ?>" data-id="<?= (int)$setup->id ?>">
										<i class="fa fa-trash"></i>
									</a>
									<?= $this->Form->create(null, [
										'url' => ['action' => 'delete', $setup->id],
										'id' => 'delete-form-' . $setup->id,
										'class' => 'd-none js-row-delete-form',
									]) ?>
									<?= $this->Form->end() ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						<?php if ($setupCount === 0): ?>
							<tr><td colspan="<?= (int)$indexColspan ?>" class="text-center text-muted py-4"><?= __('No records found.') ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<div class="card-footer">
				<?= $this->element('admin/index_footer') ?>
			</div>
		</div>
	</div>
</div>

<?= $this->element('admin/modal_record_view') ?>
