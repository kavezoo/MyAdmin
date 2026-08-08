<?php
/**
 * President — competitions index.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Competition> $competitions
 * @var int|null $lastVisitedId
 */
$this->Html->css(['pages/index'], ['block' => true]);

$rowDoubleClickAction = 'modal';
$showIdColumn = false; // UUID PK — too wide for the list
$showVisibleColumn = true;
$showCreatedColumn = true;
$showModifiedColumn = true;
$showTimestampColumn = $showCreatedColumn || $showModifiedColumn;
$indexColspan = 7;
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
$tooltipEdit = '<b>' . __('Edit') . '</b><br>' . __('Edit the selected record.');
$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.');
$tooltipDeleteBlocked = '<b>' . __('Delete') . '</b><br>' . __('Cannot delete this record because it has related child records.');
$rowDoubleClickHint = __('Double-click a row to view the record details.');

$config = [
	'rowDoubleClickAction' => $rowDoubleClickAction,
	'recordGetUrl' => $this->Url->build(['action' => 'recordGet']),
	'editUrl' => $this->Url->build(['action' => 'edit']),
	'viewUrl' => $this->Url->build(['action' => 'view']),
	'deleteUrl' => $this->Url->build(['action' => 'delete']),
	'recordFieldLabels' => [
		'id' => __('ID'),
		'name' => __('Name'),
		'title' => __('Title'),
		'club' => __('Club'),
		'national_competition' => __('National'),
		'first_date_of_application' => __('Application from'),
		'application_deadline' => __('Application deadline'),
		'competition_datetime' => __('Competition date'),
		'end_datetime' => __('End'),
		'minimum_team_size' => __('Min. team size'),
		'user_count' => __('Applicants'),
		'visible' => __('Visible'),
		'pos' => __('Position'),
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
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-trophy"></i> <?= __('Competitions') ?></h3>
					<?= h($rowDoubleClickHint) ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<?= $this->element('admin/table_search') ?>
					<span class="index-header-sep" aria-hidden="true">|</span>
					<?= $this->element('admin/index_pagination') ?>
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-plus"></i></span>' . __('New'),
						['action' => 'add'],
						['escape' => false, 'class' => 'btn btn-primary']
					) ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-2">
				<table class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table" id="competitions-index-table">
					<thead>
						<tr>
							<?php if ($showIdColumn): ?>
								<th scope="col" class="number id"><?= $this->Paginator->sort('id', '#') ?></th>
							<?php endif; ?>
							<th scope="col" class="string name"><?= $this->Paginator->sort('name', __('Name')) ?></th>
							<th scope="col" class="string title"><?= $this->Paginator->sort('title', __('Title')) ?></th>
							<th scope="col" class="string club"><?= $this->Paginator->sort('Clubs.name', __('Club')) ?></th>
							<th scope="col" class="date first_date_of_application"><?= $this->Paginator->sort('first_date_of_application', __('Application from')) ?></th>
							<th scope="col" class="date application_deadline"><?= $this->Paginator->sort('application_deadline', __('Deadline')) ?></th>
							<th scope="col" class="number count"><?= $this->Paginator->sort('user_count', __('Applicants')) ?></th>
							<?php if ($showVisibleColumn): ?>
								<th scope="col" class="boolean visible"><?= $this->Paginator->sort('visible', __('Visible')) ?></th>
							<?php endif; ?>
							<?php if ($showTimestampColumn): ?>
								<th scope="col" class="datetime">
									<?= $this->Paginator->sort('created', __('Created')) ?>
								</th>
							<?php endif; ?>
							<th scope="col" class="actions"><?= __('Actions') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ($competitions->count() === 0): ?>
							<tr>
								<td colspan="<?= (int)$indexColspan ?>" class="text-center text-muted py-4"><?= __('No records found.') ?></td>
							</tr>
						<?php else: ?>
							<?php foreach ($competitions as $row): ?>
								<?php
								$canDelete = (int)$row->user_count === 0;
								$isLastVisited = isset($lastVisitedId) && (string)$lastVisitedId === (string)$row->id;
								?>
								<tr id="record-<?= h((string)$row->id) ?>"
									data-id="<?= h((string)$row->id) ?>"
									data-can-delete="<?= $canDelete ? '1' : '0' ?>"<?= $isLastVisited ? ' class="last-visited"' : '' ?>>
									<?php if ($showIdColumn): ?>
										<td class="number id"><span class="text-muted small"><?= h(substr((string)$row->id, 0, 8)) ?></span></td>
									<?php endif; ?>
									<td class="string name"><?= h((string)$row->name) ?></td>
									<td class="string title"><?= h((string)$row->title) ?></td>
									<td class="string club"><?= h((string)($row->club->name ?? '')) ?></td>
									<td class="date"><?= $row->first_date_of_application ? h(\App\Utility\LocaleDateParser::format($row->first_date_of_application, 'date')) : '' ?></td>
									<td class="date"><?= $row->application_deadline ? h(\App\Utility\LocaleDateParser::format($row->application_deadline, 'date')) : '' ?></td>
									<td class="number count"><?= h(\App\Utility\LocaleNumberParser::format($row->user_count, decimals: 0)) ?></td>
									<?php if ($showVisibleColumn): ?>
										<td class="boolean visible">
											<?= !empty($row->visible)
												? '<i class="fa fa-check text-success"></i>'
												: '<i class="fa fa-times text-danger"></i>' ?>
										</td>
									<?php endif; ?>
									<?php if ($showTimestampColumn): ?>
										<td class="datetime">
											<?= $row->created ? h(\App\Utility\LocaleDateParser::format($row->created, 'datetime_short')) : '' ?>
										</td>
									<?php endif; ?>
									<td class="actions">
										<?= $this->Html->link(
											'<i class="fa fa-eye"></i>',
											['action' => 'view', $row->id],
											['escape' => false, 'class' => 'btn btn-sm btn-outline-secondary', 'data-bs-toggle' => 'tooltip', 'data-bs-html' => 'true', 'title' => $tooltipDetails]
										) ?>
										<?= $this->Html->link(
											'<i class="fa fa-pencil"></i>',
											['action' => 'edit', $row->id],
											['escape' => false, 'class' => 'btn btn-sm btn-outline-primary', 'data-bs-toggle' => 'tooltip', 'data-bs-html' => 'true', 'title' => $tooltipEdit]
										) ?>
										<?php if ($canDelete): ?>
											<?= $this->Form->create(null, ['url' => ['action' => 'delete', $row->id], 'id' => 'delete-form-' . $row->id, 'class' => 'd-inline']) ?>
											<button type="button" class="btn btn-sm btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-html="true" title="<?= h($tooltipDelete) ?>">
												<i class="fa fa-trash"></i>
											</button>
											<?= $this->Form->end() ?>
										<?php else: ?>
											<span class="d-inline-block" tabindex="-1" data-bs-toggle="tooltip" data-bs-html="true" title="<?= h($tooltipDeleteBlocked) ?>">
												<button type="button" class="btn btn-sm btn-secondary disabled" tabindex="-1" aria-disabled="true"><i class="fa fa-trash"></i></button>
											</span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
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
