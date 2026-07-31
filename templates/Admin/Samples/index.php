<?php
/**
 * Samples index
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Sample> $samples
 */

$this->Html->css(['pages/index'], ['block' => true]);

/**
 * Row double-click action on the index table:
 * - 'modal' → quick view modal (recordGet)
 * - 'edit'  → open edit form (same as Edit button)
 * - 'none'  → no action
 */
$rowDoubleClickAction = 'modal';

/**
 * Hány tizedesjeggyel jelenjenek meg a számok az index listában (locale szerint).
 * - integer: egész mezők (szam, pos, count, …)
 * - decimal: tört / pénz (netto, …)
 */
$numberDecimals = [
	'integer' => 0,
	'decimal' => 2,
];

/**
 * Optional index columns (true = show, false = hide).
 * Created / Modified can be toggled independently; if either is on, one timestamp column is rendered.
 */
$showCountColumn = true;
$showVisibleColumn = true;
$showCreatedColumn = true;
$showModifiedColumn = true;

$showTimestampColumn = $showCreatedColumn || $showModifiedColumn;
$indexColspan = 11; // id, parent, name, szam, netto, datum, ido, datumido, logikai, pos, actions
if ($showVisibleColumn) {
	$indexColspan++;
}
if ($showCountColumn) {
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

$config = [
	'rowDoubleClickAction' => $rowDoubleClickAction,
	'recordGetUrl' => $this->Url->build(['action' => 'recordGet']),
	'categoryGetUrl' => $this->Url->build(['action' => 'parentGet']),
	'editUrl' => $this->Url->build(['action' => 'edit']),
	'viewUrl' => $this->Url->build(['action' => 'view']),
	'parentEditUrl' => $this->Url->build(['controller' => 'Parents', 'action' => 'edit']),
	'parentViewUrl' => $this->Url->build(['controller' => 'Parents', 'action' => 'view']),
	'deleteUrl' => $this->Url->build(['action' => 'delete']),
	'recordFieldLabels' => [
		'id' => __('ID'),
		'parent' => __('Parent'),
		'name' => __('Name'),
		'szam' => __('Number'),
		'netto' => __('Net'),
		'datum' => __('Date'),
		'ido' => __('Time'),
		'datumido' => __('Date and time'),
		'logikai' => __('Boolean'),
		'pos' => __('Position'),
		'visible' => __('Visible'),
		'city_count' => __('Cities'),
		'cities' => __('City list'),
		'created' => __('Created'),
		'modified' => __('Modified'),
	],
	'categoryFieldLabels' => [
		'id' => __('ID'),
		'name' => __('Name'),
		'pos' => __('Position'),
		'visible' => __('Visible'),
		'sample_count' => __('Samples'),
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
$this->Html->script(['pages/index'], ['block' => 'scriptBottom']);

$paging = $this->Paginator->params();
?>

<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-table"></i> <?= __('Samples') ?></h3>
					<?php if ($rowDoubleClickHint !== ''): ?>
						<?= h($rowDoubleClickHint) ?>
					<?php endif; ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2">
					<div class="table-search">
						<input type="search" class="form-control form-control-sm table-search-input" id="table-search-input" name="table_search" placeholder="<?= h(__('Search...')) ?>" autocomplete="off">
					</div>
					<?= $this->element('admin/index_pagination') ?>
				</div>
				<div class="clearfix"></div>
			</div>

			<div class="card-body p-2">
				<table class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table">
					<thead>
						<tr>
							<th scope="col" class="number id"><?= $this->Paginator->sort('id', '#') ?></th>
							<th scope="col" class="string category-id"><?= $this->Paginator->sort('Parents.name', __('Parent')) ?></th>
							<th scope="col" class="string name"><?= $this->Paginator->sort('name', __('Name')) ?></th>
							<th scope="col" class="number szam"><?= $this->Paginator->sort('szam', __('Number')) ?></th>
							<th scope="col" class="currency netto"><?= $this->Paginator->sort('netto', __('Net')) ?></th>
							<th scope="col" class="date datum"><?= $this->Paginator->sort('datum', __('Date')) ?></th>
							<th scope="col" class="time ido"><?= $this->Paginator->sort('ido', __('Time')) ?></th>
							<th scope="col" class="datetime datumido"><?= $this->Paginator->sort('datumido', __('Date and time')) ?></th>
							<th scope="col" class="boolean logikai"><?= $this->Paginator->sort('logikai', __('Boolean')) ?></th>
							<th scope="col" class="number pos"><?= $this->Paginator->sort('pos', __('Position')) ?></th>
							<?php if ($showVisibleColumn): ?>
								<th scope="col" class="boolean visible"><?= $this->Paginator->sort('visible', __('Visible')) ?></th>
							<?php endif; ?>
							<?php if ($showCountColumn): ?>
								<th scope="col" class="number count"><?= $this->Paginator->sort('city_count', __('Cities')) ?></th>
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
						<?php foreach ($samples as $sample): ?>
							<tr id="record-<?= (int)$sample->id ?>" data-id="<?= (int)$sample->id ?>">
								<td class="number id"><?= h($sample->id) ?></td>
								<td class="string category-id">
									<?php if ($sample->parent): ?>
										<a href="#" class="category-link" data-id="<?= (int)$sample->parent_id ?>">
											<?= h($sample->parent->name) ?><span class="category-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span>
										</a>
									<?php endif; ?>
								</td>
								<td class="string name"><?= h($sample->name) ?></td>
								<td class="number szam text-end"><?= h(\App\Utility\LocaleNumberParser::format($sample->szam, decimals: $numberDecimals['integer'])) ?></td>
								<td class="currency netto text-end">
									<span class="currency-amount"><?= h(\App\Utility\LocaleNumberParser::format($sample->netto, decimals: $numberDecimals['decimal'])) ?></span> HUF
								</td>
								<td class="date datum"><?= $sample->datum ? h($sample->datum->format('Y.m.d.')) : '' ?></td>
								<td class="time ido"><?= $sample->ido ? h($sample->ido->format('H:i')) : '' ?></td>
								<td class="datetime datumido"><?= $sample->datumido ? h($sample->datumido->format('Y.m.d. H:i')) : '' ?></td>
								<td class="boolean logikai">
									<?= $sample->logikai
										? '<i class="fa fa-check text-success"></i>'
										: '<i class="fa fa-times text-danger"></i>' ?>
								</td>
								<td class="number pos text-end"><?= h(\App\Utility\LocaleNumberParser::format($sample->pos, decimals: $numberDecimals['integer'])) ?></td>
								<?php if ($showVisibleColumn): ?>
									<td class="boolean visible">
										<?= $sample->visible
											? '<i class="fa fa-check text-success"></i>'
											: '<i class="fa fa-times text-danger"></i>' ?>
									</td>
								<?php endif; ?>
								<?php if ($showCountColumn): ?>
									<td class="number count text-end"><?= h(\App\Utility\LocaleNumberParser::format($sample->city_count, decimals: $numberDecimals['integer'])) ?></td>
								<?php endif; ?>
								<?php if ($showTimestampColumn): ?>
									<td class="datetime<?= $showCreatedColumn ? ' created' : '' ?><?= $showModifiedColumn ? ' modified' : '' ?>">
										<?php if ($showCreatedColumn): ?>
											<?= $sample->created ? h($sample->created->format('Y.m.d. H:i')) : '' ?>
										<?php endif; ?>
										<?php if ($showCreatedColumn && $showModifiedColumn && $sample->modified): ?>
											<br>
										<?php endif; ?>
										<?php if ($showModifiedColumn): ?>
											<?= $sample->modified ? h($sample->modified->format('Y.m.d. H:i')) : '' ?>
										<?php endif; ?>
									</td>
								<?php endif; ?>
								<td class="actions">
									<?= $this->Html->link(
										'<i class="fa fa-eye"></i>',
										['action' => 'view', $sample->id],
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
										['action' => 'edit', $sample->id],
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
									<a role="button" href="#" class="btn btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDelete) ?>" data-id="<?= (int)$sample->id ?>">
										<i class="fa fa-trash"></i>
									</a>
									<?= $this->Form->postLink(
										'',
										['action' => 'delete', $sample->id],
										[
											'class' => 'd-none js-row-delete-form',
											'id' => 'delete-form-' . $sample->id,
										]
									) ?>
								</td>
							</tr>
						<?php endforeach; ?>
						<?php if ($samples->count() === 0): ?>
							<tr>
								<td colspan="<?= (int)$indexColspan ?>" class="text-center text-muted py-4"><?= __('No records found.') ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<div class="card-footer">
				<div class="float-left text-muted">
					<?php if (!empty($paging['count'])): ?>
						<strong><?= (int)($paging['start'] ?? 0) ?>–<?= (int)($paging['end'] ?? 0) ?></strong>
						/ <strong><?= (int)$paging['count'] ?></strong> <?= __('records') ?>
						&nbsp;|&nbsp;
						<strong><?= (int)($paging['page'] ?? 1) ?></strong>. <?= __('page') ?> / <strong><?= (int)($paging['pageCount'] ?? 1) ?></strong>
					<?php else: ?>
						<strong>0</strong> <?= __('records') ?>
					<?php endif; ?>
				</div>
				<div class="float-right">
					<?= $this->element('admin/index_pagination') ?>
				</div>
				<div class="clearfix"></div>
			</div>
		</div>
	</div>
</div>

<?= $this->element('admin/modal_record_view') ?>
<?= $this->element('admin/modal_linked_record_view') ?>
