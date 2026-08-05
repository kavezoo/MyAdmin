<?php
/**
 * Languages index — superuser: full CRUD; admin: visible + pos.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Language> $languages
 * @var bool $canDeleteLanguage
 * @var bool $languagesVisibleOnly
 * @var array<int, true> $deletableLanguageIds
 */
$canDeleteLanguage = (bool)$this->get('canDeleteLanguage', false);
$languagesVisibleOnly = (bool)$this->get('languagesVisibleOnly', true);
$deletableLanguageIds = $deletableLanguageIds ?? [];
$this->Html->css(['pages/index'], ['block' => true]);

$visibleFilterQuery = $this->request->getQueryParams();
unset($visibleFilterQuery['visible_only']);
$visibleFilterQuery['page'] = '1';

$rowDoubleClickAction = 'modal'; // 'modal' | 'edit' | 'none'

$numberDecimals = [
	'integer' => 0,
	'decimal' => 2,
];

$showIdColumn = true;
$showVisibleColumn = true;
$showCreatedColumn = true;
$showModifiedColumn = true;

$showTimestampColumn = $showCreatedColumn || $showModifiedColumn;
$indexColspan = 5; // code, name, endonim_name, pos, actions
if ($showIdColumn) {
	$indexColspan++;
}
if ($showVisibleColumn) {
	$indexColspan++;
}
if ($showTimestampColumn) {
	$indexColspan++;
}

$tooltipDetails = '<b>' . __('View details') . '</b><br>' . __('View the selected record details.');
$tooltipEdit = '<b>' . __('Edit') . '</b><br>' . (
	$canDeleteLanguage
		? __('Edit the selected record.')
		: __('Only visibility and position can be changed.')
);
$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.');
$tooltipDeleteBlocked = '<b>' . __('Delete') . '</b><br>' . __('Cannot delete this record because it has related child records.');

$rowDoubleClickHints = [
	'modal' => __('Double-click a row to view the record details.'),
	'edit' => __('Double-click a row to edit the record.'),
	'none' => '',
];
$rowDoubleClickHint = $rowDoubleClickHints[$rowDoubleClickAction] ?? $rowDoubleClickHints['modal'];

$config = [
	'rowDoubleClickAction' => $rowDoubleClickAction,
	'recordGetUrl' => $this->Url->build(['action' => 'recordGet']),
	'editUrl' => $this->Url->build(['action' => 'edit']),
	'viewUrl' => $this->Url->build(['action' => 'view']),
	'deleteUrl' => $canDeleteLanguage ? $this->Url->build(['action' => 'delete']) : '',
	'recordFieldLabels' => [
		'id' => __('ID'),
		'code' => __('Code'),
		'name' => __('Name'),
		'endonim_name' => __('Endonym'),
		'visible' => __('Visible'),
		'pos' => __('Position'),
		'created' => __('Created'),
		'modified' => __('Modified'),
	],
	'entityFieldLabels' => [
		'language' => [
			'id' => __('ID'),
			'code' => __('Code'),
			'name' => __('Name'),
			'endonim_name' => __('Endonym'),
			'visible' => __('Visible'),
			'pos' => __('Position'),
			'created' => __('Created'),
			'modified' => __('Modified'),
		],
	],
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);
$this->Html->script(['pages/index'], ['block' => 'scriptBottom']);
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-language"></i> <?= __('Languages') ?></h3>
					<?php if ($rowDoubleClickHint !== ''): ?>
						<?= h($rowDoubleClickHint) ?>
					<?php endif; ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<form method="get" action="<?= h($this->Url->build(['action' => 'index'])) ?>"
						class="languages-visible-filter mb-0"
						id="languages-visible-filter">
						<?php foreach ($visibleFilterQuery as $name => $value): ?>
							<?php if (!is_scalar($value)) {
								continue;
							} ?>
							<input type="hidden" name="<?= h((string)$name) ?>" value="<?= h((string)$value) ?>">
						<?php endforeach; ?>
						<input type="hidden" name="visible_only" value="0" id="languages-visible-only-off"
							<?= $languagesVisibleOnly ? 'disabled' : '' ?>>
						<div class="form-check form-switch mb-0">
							<input type="checkbox"
								class="form-check-input"
								id="languages-visible-only"
								name="visible_only"
								value="1"
								<?= $languagesVisibleOnly ? 'checked' : '' ?>
								onchange="document.getElementById('languages-visible-only-off').disabled = this.checked; this.form.submit();">
							<label class="form-check-label text-nowrap" for="languages-visible-only"><?= __('Only visible languages') ?></label>
						</div>
					</form>
					<span class="index-header-sep" aria-hidden="true">|</span>
					<?= $this->element('admin/table_search') ?>
					<?= $this->element('admin/index_pagination') ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-bordered table-hover table-striped mb-0 index-data-table" id="index-data-table">
						<thead>
							<tr>
								<?php if ($showIdColumn): ?>
									<th scope="col" class="number id"><?= $this->Paginator->sort('id', '#') ?></th>
								<?php endif; ?>
								<th scope="col" class="string locale"><?= $this->Paginator->sort('code', __('Code')) ?></th>
								<th scope="col" class="string name"><?= $this->Paginator->sort('name', __('Name')) ?></th>
								<th scope="col" class="string endonim"><?= $this->Paginator->sort('endonim_name', __('Endonym')) ?></th>
								<?php if ($showVisibleColumn): ?>
									<th scope="col" class="boolean visible"><?= $this->Paginator->sort('visible', __('Visible')) ?></th>
								<?php endif; ?>
								<th scope="col" class="number pos"><?= $this->Paginator->sort('pos', __('Position')) ?></th>
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
							<?php foreach ($languages as $language): ?>
								<?php
								$isLastVisited = isset($lastVisitedId) && (int)$lastVisitedId === (int)$language->id;
								$rowCanDelete = $canDeleteLanguage && !empty($deletableLanguageIds[(int)$language->id]);
								?>
								<tr id="record-<?= (int)$language->id ?>" data-id="<?= (int)$language->id ?>" data-can-delete="<?= $rowCanDelete ? '1' : '0' ?>"<?= $isLastVisited ? ' class="last-visited"' : '' ?>>
									<?php if ($showIdColumn): ?>
										<td class="number id"><?= h($language->id) ?></td>
									<?php endif; ?>
									<td class="string locale"><code><?= h($language->code) ?></code></td>
									<td class="string name"><?= h($language->name) ?></td>
									<td class="string endonim"><?= h($language->endonim_name) ?></td>
									<?php if ($showVisibleColumn): ?>
										<td class="boolean visible">
											<?= $language->visible
												? '<i class="fa fa-check text-success"></i>'
												: '<i class="fa fa-times text-danger"></i>' ?>
										</td>
									<?php endif; ?>
									<td class="number pos text-end"><?= h(\App\Utility\LocaleNumberParser::format($language->pos, decimals: $numberDecimals['integer'])) ?></td>
									<?php if ($showTimestampColumn): ?>
										<td class="datetime<?= $showCreatedColumn ? ' created' : '' ?><?= $showModifiedColumn ? ' modified' : '' ?>">
											<?php if ($showCreatedColumn): ?>
												<?= $language->created ? h(\App\Utility\LocaleDateParser::format($language->created, 'datetime_short')) : '' ?>
											<?php endif; ?>
											<?php if ($showCreatedColumn && $showModifiedColumn && $language->modified): ?>
												<br>
											<?php endif; ?>
											<?php if ($showModifiedColumn): ?>
												<?= $language->modified ? h(\App\Utility\LocaleDateParser::format($language->modified, 'datetime_short')) : '' ?>
											<?php endif; ?>
										</td>
									<?php endif; ?>
									<td class="actions">
										<?= $this->Html->link(
											'<i class="fa fa-eye"></i>',
											['action' => 'view', $language->id],
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
											['action' => 'edit', $language->id],
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
										<?php if ($canDeleteLanguage): ?>
											<?php if ($rowCanDelete): ?>
												<a role="button" href="#" class="btn btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDelete) ?>" data-id="<?= (int)$language->id ?>">
													<i class="fa fa-trash"></i>
												</a>
												<?= $this->Form->create(null, [
													'url' => ['action' => 'delete', $language->id],
													'id' => 'delete-form-' . $language->id,
													'class' => 'd-none js-row-delete-form',
												]) ?>
												<?= $this->Form->end() ?>
											<?php else: ?>
												<span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDeleteBlocked) ?>">
													<a role="button" href="#" class="btn btn-secondary disabled" tabindex="-1" aria-disabled="true">
														<i class="fa fa-trash"></i>
													</a>
												</span>
											<?php endif; ?>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
							<?php if ($languages->count() === 0): ?>
								<tr>
									<td colspan="<?= (int)$indexColspan ?>" class="text-center text-muted py-4"><?= __('No data.') ?></td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
			<div class="card-footer">
				<?= $this->element('admin/index_footer') ?>
			</div>
		</div>
	</div>
</div>
<?= $this->element('admin/modal_record_view') ?>
