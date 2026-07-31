<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\City> $cities
 */
$this->Html->css(['pages/index'], ['block' => true]);

/**
 * Row double-click: 'modal' | 'edit' | 'none'
 */
$rowDoubleClickAction = 'modal';

/**
 * Hány tizedesjeggyel jelenjenek meg a számok az index listában (locale szerint).
 * - integer: egész mezők (pos, count, …)
 * - decimal: tört / pénz (ha van ilyen oszlop)
 */
$numberDecimals = [
	'integer' => 0,
	'decimal' => 2,
];

/**
 * Optional index columns (true = show, false = hide).
 */
$showIdColumn = true;
$showCountColumn = true;
$showVisibleColumn = true;
$showCreatedColumn = true;
$showModifiedColumn = true;

$showTimestampColumn = $showCreatedColumn || $showModifiedColumn;
$indexColspan = 3; // name, pos, actions
if ($showIdColumn) {
	$indexColspan++;
}
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
	'deleteUrl' => $this->Url->build(['action' => 'delete']),
	'recordFieldLabels' => [
		'id' => __('ID'),
		'name' => __('Name'),
		'pos' => __('Position'),
		'visible' => __('Visible'),
		'sample_count' => __('Samples'),
		'samples' => __('Sample list'),
		'created' => __('Created'),
		'modified' => __('Modified'),
	],
	'relatedLinkFields' => [
		'samples' => [
			'getUrl' => $this->Url->build(['controller' => 'Samples', 'action' => 'recordGet']),
			'editUrl' => $this->Url->build(['controller' => 'Samples', 'action' => 'edit']),
			'viewUrl' => $this->Url->build(['controller' => 'Samples', 'action' => 'view']),
			'deleteUrl' => $this->Url->build(['controller' => 'Samples', 'action' => 'delete']),
			'deleteFormPrefix' => 'sample',
			'labels' => 'sample',
			'title' => __('Sample details'),
		],
		'cities' => [
			'getUrl' => $this->Url->build(['controller' => 'Cities', 'action' => 'recordGet']),
			'editUrl' => $this->Url->build(['controller' => 'Cities', 'action' => 'edit']),
			'viewUrl' => $this->Url->build(['controller' => 'Cities', 'action' => 'view']),
			'deleteUrl' => $this->Url->build(['controller' => 'Cities', 'action' => 'delete']),
			'deleteFormPrefix' => 'city',
			'labels' => 'city',
			'title' => __('City details'),
		],
	],
	'entityFieldLabels' => [
		'sample' => [
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
		'city' => [
			'id' => __('ID'),
			'name' => __('Name'),
			'pos' => __('Position'),
			'visible' => __('Visible'),
			'sample_count' => __('Samples'),
			'samples' => __('Sample list'),
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

$paging = $this->Paginator->params();
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-table"></i> <?= __('Cities') ?></h3>
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
							<?php if ($showIdColumn): ?>
								<th scope="col" class="number id"><?= $this->Paginator->sort('id', '#') ?></th>
							<?php endif; ?>
							<th scope="col" class="string name"><?= $this->Paginator->sort('name', __('Name')) ?></th>
							<th scope="col" class="number pos"><?= $this->Paginator->sort('pos', __('Position')) ?></th>
							<?php if ($showVisibleColumn): ?>
								<th scope="col" class="boolean visible"><?= $this->Paginator->sort('visible', __('Visible')) ?></th>
							<?php endif; ?>
							<?php if ($showCountColumn): ?>
								<th scope="col" class="number count"><?= $this->Paginator->sort('sample_count', __('Samples')) ?></th>
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
						<?php foreach ($cities as $city): ?>
							<?php
							$canDeleteRow = (int)($city->sample_count ?? 0) === 0;
							$isLastVisited = isset($lastVisitedId) && (int)$lastVisitedId === (int)$city->id;
							?>
							<tr id="record-<?= (int)$city->id ?>" data-id="<?= (int)$city->id ?>" data-can-delete="<?= $canDeleteRow ? '1' : '0' ?>"<?= $isLastVisited ? ' class="last-visited"' : '' ?>>
								<?php if ($showIdColumn): ?>
									<td class="number id"><?= h($city->id) ?></td>
								<?php endif; ?>
								<td class="string name"><?= h($city->name) ?></td>
								<td class="number pos text-end"><?= h(\App\Utility\LocaleNumberParser::format($city->pos, decimals: $numberDecimals['integer'])) ?></td>
								<?php if ($showVisibleColumn): ?>
									<td class="boolean visible">
										<?= $city->visible
											? '<i class="fa fa-check text-success"></i>'
											: '<i class="fa fa-times text-danger"></i>' ?>
									</td>
								<?php endif; ?>
								<?php if ($showCountColumn): ?>
									<td class="number count text-end"><?= h(\App\Utility\LocaleNumberParser::formatCount($city->sample_count, decimals: $numberDecimals['integer'])) ?></td>
								<?php endif; ?>
								<?php if ($showTimestampColumn): ?>
									<td class="datetime<?= $showCreatedColumn ? ' created' : '' ?><?= $showModifiedColumn ? ' modified' : '' ?>">
										<?php if ($showCreatedColumn): ?>
											<?= $city->created ? h($city->created->format('Y.m.d. H:i')) : '' ?>
										<?php endif; ?>
										<?php if ($showCreatedColumn && $showModifiedColumn && $city->modified): ?>
											<br>
										<?php endif; ?>
										<?php if ($showModifiedColumn): ?>
											<?= $city->modified ? h($city->modified->format('Y.m.d. H:i')) : '' ?>
										<?php endif; ?>
									</td>
								<?php endif; ?>
								<td class="actions">
									<?= $this->Html->link(
										'<i class="fa fa-eye"></i>',
										['action' => 'view', $city->id],
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
										['action' => 'edit', $city->id],
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
									<?php if ($canDeleteRow): ?>
										<a role="button" href="#" class="btn btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDelete) ?>" data-id="<?= (int)$city->id ?>">
											<i class="fa fa-trash"></i>
										</a>
										<?= $this->Form->create(null, [
											'url' => ['action' => 'delete', $city->id],
											'id' => 'delete-form-' . $city->id,
											'class' => 'd-none js-row-delete-form',
										]) ?>
										<?= $this->Form->end() ?>
									<?php else: ?>
										<a role="button" href="#" class="btn btn-outline-danger disabled" aria-disabled="true" tabindex="-1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDeleteBlocked) ?>">
											<i class="fa fa-trash"></i>
										</a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						<?php if ($cities->count() === 0): ?>
							<tr><td colspan="<?= (int)$indexColspan ?>" class="text-center text-muted py-4"><?= __('No records found.') ?></td></tr>
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
				<div class="float-right"><?= $this->element('admin/index_pagination') ?></div>
				<div class="clearfix"></div>
			</div>
		</div>
	</div>
</div>

<?= $this->element('admin/modal_record_view') ?>
<?= $this->element('admin/modal_linked_record_view') ?>
