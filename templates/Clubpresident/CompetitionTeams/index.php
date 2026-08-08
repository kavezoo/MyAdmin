<?php
/**
 * Clubpresident — sub-teams (alcsapatok) index.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\CompetitionsClub> $teams
 * @var int|null $lastVisitedId
 */
$this->Html->css(['pages/index'], ['block' => true]);

$rowDoubleClickAction = 'modal';
$showIdColumn = true;
$showVisibleColumn = true;
$showCreatedColumn = true;
$showTimestampColumn = $showCreatedColumn;
$indexColspan = 5; // competition, name, members, min, actions
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
$tooltipApplicants = '<b>' . __('Assign applicants') . '</b><br>' . __('Assign members to this team.');
$tooltipEdit = '<b>' . __('Edit') . '</b><br>' . __('Edit the selected record.');
$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.');
$tooltipDeleteBlocked = '<b>' . __('Delete') . '</b><br>' . __('Cannot delete this sub-team because it already has assigned members.');
$rowDoubleClickHint = __('Double-click a row to view the record details.');

$competitionGetUrl = $this->Url->build(['action' => 'competitionRecordGet']);

$config = [
	'rowDoubleClickAction' => $rowDoubleClickAction,
	'recordGetUrl' => $this->Url->build(['action' => 'recordGet']),
	'editUrl' => $this->Url->build(['action' => 'edit']),
	'viewUrl' => $this->Url->build(['action' => 'view']),
	'deleteUrl' => $this->Url->build(['action' => 'delete']),
	'recordFieldLabels' => [
		'id' => __('ID'),
		'competition' => __('Competition'),
		'name' => __('Team'),
		'user_count' => __('Members'),
		'minimum_team_size' => __('Min. team size'),
		'application_datetime' => __('Applied'),
		'visible' => __('Visible'),
		'pos' => __('Position'),
		'created' => __('Created'),
		'modified' => __('Modified'),
	],
	'entityFieldLabels' => [
		'competition' => [
			'id' => __('ID'),
			'name' => __('Name'),
			'title' => __('Title'),
			'club' => __('Club'),
			'national_competition' => __('National'),
			'first_date_of_application' => __('Application from'),
			'application_deadline' => __('Application deadline'),
			'competition_datetime' => __('Competition datetime'),
			'end_datetime' => __('End'),
			'minimum_team_size' => __('Min. team size'),
			'user_count' => __('Members'),
			'visible' => __('Visible'),
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
$this->append('css', <<<'CSS'
<style>
.index-data-table th.string.competition,
.index-data-table td.string.competition {
	width: 11rem;
	max-width: 13rem;
	white-space: normal;
	word-break: break-word;
}
.index-data-table th.string.name,
.index-data-table td.string.name {
	min-width: 14rem;
}
</style>
CSS
);
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-users"></i> <?= __('Sub-teams') ?></h3>
					<?= __('Sub-teams (alcsapatok) for competitions') ?>
					<div class="text-muted small"><?= h($rowDoubleClickHint) ?></div>
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
				<table class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table">
					<thead>
						<tr>
							<?php if ($showIdColumn): ?>
								<th scope="col" class="number id"><?= $this->Paginator->sort('id', '#') ?></th>
							<?php endif; ?>
							<th scope="col" class="string competition"><?= $this->Paginator->sort('Competitions.name', __('Competition')) ?></th>
							<th scope="col" class="string name"><?= $this->Paginator->sort('Subclubs.name', __('Team')) ?></th>
							<th scope="col" class="number count"><?= $this->Paginator->sort('user_count', __('Members')) ?></th>
							<th scope="col" class="number"><?= __('Min.') ?></th>
							<?php if ($showVisibleColumn): ?>
								<th scope="col" class="boolean visible"><?= $this->Paginator->sort('visible', __('Visible')) ?></th>
							<?php endif; ?>
							<?php if ($showTimestampColumn): ?>
								<th scope="col" class="datetime"><?= $this->Paginator->sort('created', __('Created')) ?></th>
							<?php endif; ?>
							<th scope="col" class="actions"><?= __('Actions') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ($teams->count() === 0): ?>
							<tr><td colspan="<?= (int)$indexColspan ?>" class="text-center text-muted py-4"><?= __('No records found.') ?></td></tr>
						<?php else: ?>
							<?php foreach ($teams as $row): ?>
								<?php
								$min = (int)($row->competition->minimum_team_size ?? 3);
								$ok = (int)$row->user_count >= $min;
								$canDelete = (int)$row->user_count === 0;
								$isLast = isset($lastVisitedId) && (int)$lastVisitedId === (int)$row->id;
								$competitionId = (string)($row->competition_id ?? $row->competition->id ?? '');
								$competitionName = (string)($row->competition->name ?? '');
								$teamName = (string)($row->subclub->name ?? '');
								?>
								<tr id="record-<?= (int)$row->id ?>"
									data-id="<?= (int)$row->id ?>"
									data-can-delete="<?= $canDelete ? '1' : '0' ?>"<?= $isLast ? ' class="last-visited"' : '' ?>>
									<?php if ($showIdColumn): ?>
										<td class="number id"><?= (int)$row->id ?></td>
									<?php endif; ?>
									<td class="string competition">
										<?php if ($competitionId !== '' && $competitionName !== ''): ?>
											<a href="#"
												class="category-link record-modal-link"
												data-id="<?= h($competitionId) ?>"
												data-get-url="<?= h($competitionGetUrl) ?>"
												data-edit-url=""
												data-view-url=""
												data-delete-url=""
												data-labels="competition"
												data-title="<?= h(__('Competition details')) ?>"
											><?= h($competitionName) ?><span class="category-link-icon record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>
										<?php else: ?>
											—
										<?php endif; ?>
									</td>
									<td class="string name fw-bold"><?= h($teamName) ?></td>
									<td class="number count">
										<?= h(\App\Utility\LocaleNumberParser::format($row->user_count, decimals: 0)) ?>
										<?php if ($ok): ?>
											<i class="fa fa-check text-success ms-1" title="<?= h(__('Minimum reached')) ?>"></i>
										<?php else: ?>
											<i class="fa fa-exclamation-triangle text-warning ms-1" title="<?= h(__('Below minimum')) ?>"></i>
										<?php endif; ?>
									</td>
									<td class="number"><?= h(\App\Utility\LocaleNumberParser::format($min, decimals: 0)) ?></td>
									<?php if ($showVisibleColumn): ?>
										<td class="boolean visible text-center">
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
											'<i class="fa fa-users"></i>',
											['action' => 'applicants', $row->id],
											['escape' => false, 'class' => 'btn btn-sm btn-outline-secondary', 'data-bs-toggle' => 'tooltip', 'data-bs-html' => 'true', 'title' => $tooltipApplicants]
										) ?>
										<?= $this->Html->link(
											'<i class="fa fa-pencil"></i>',
											['action' => 'edit', $row->id],
											['escape' => false, 'class' => 'btn btn-sm btn-outline-primary', 'data-bs-toggle' => 'tooltip', 'data-bs-html' => 'true', 'title' => $tooltipEdit]
										) ?>
										<?php if ($canDelete): ?>
											<?= $this->Form->create(null, ['url' => ['action' => 'delete', $row->id], 'id' => 'delete-form-' . $row->id, 'class' => 'd-inline']) ?>
											<a role="button" href="#" class="btn btn-sm btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-html="true" title="<?= h($tooltipDelete) ?>" data-id="<?= (int)$row->id ?>"><i class="fa fa-trash"></i></a>
											<?= $this->Form->end() ?>
										<?php else: ?>
											<span class="d-inline-block" tabindex="-1" data-bs-toggle="tooltip" data-bs-html="true" title="<?= h($tooltipDeleteBlocked) ?>">
												<a role="button" href="#" class="btn btn-sm btn-secondary disabled" tabindex="-1" aria-disabled="true"><i class="fa fa-trash"></i></a>
											</span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<div class="card-footer"><?= $this->element('admin/index_footer') ?></div>
		</div>
	</div>
</div>
<?= $this->element('admin/modal_record_view') ?>
<?= $this->element('admin/modal_linked_record_view') ?>
