<?php
/**
 * Admin — global competitions index (optional country filter).
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Competition> $competitions
 * @var int $filterCountryId
 * @var string $filterCountryLabel
 * @var array<int, string> $countryOptions
 * @var int|null $lastVisitedId
 */
$filterCountryId = (int)($filterCountryId ?? 0);
$filterCountryLabel = (string)($filterCountryLabel ?? '');
$countryOptions = $countryOptions ?? [];

$this->Html->css(['pages/index'], ['block' => true]);

$filterQuery = $this->request->getQueryParams();
unset($filterQuery['country_id']);
$filterQuery['page'] = '1';

$rowDoubleClickAction = 'modal';
$showIdColumn = false; // UUID PK — too wide for the list
$showVisibleColumn = true;
$showCreatedColumn = true;
$showModifiedColumn = true;
$indexColspan = 8;
if ($showIdColumn) {
	$indexColspan++;
}
if ($showVisibleColumn) {
	$indexColspan++;
}
if ($showCreatedColumn) {
	$indexColspan++;
}
if ($showModifiedColumn) {
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
		'country' => __('Country'),
		'club' => __('Club'),
		'national_competition' => __('National'),
		'first_date_of_application' => __('Application from'),
		'application_deadline' => __('Application deadline'),
		'competition_datetime' => __('Competition date'),
		'end_datetime' => __('End'),
		'minimum_team_size' => __('Min. team size'),
		'pipe_type' => __('Pipe type'),
		'pipe_parameters' => __('Pipe parameters'),
		'tobacco_type' => __('Tobacco type'),
		'tobacco_weight' => __('Tobacco weight'),
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

$indexSearch = (string)($this->get('indexSearch') ?? '');
$qKey = \App\Utility\AdminSearch::queryParam();
$tooltipSearch = h('<b>' . __('Start search') . '</b><br>' . __('Search in the text fields of this list.'));
$tooltipClear = h('<b>' . __('Clear search') . '</b><br>' . __('Clear the saved search and return to the last visited record.'));
$hasSearch = $indexSearch !== '';
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-trophy"></i> <?= __('Competitions') ?></h3>
					<?= h($rowDoubleClickHint) ?>
					<?php if ($filterCountryLabel !== ''): ?>
						<div class="text-muted"><?= h(__('Showing competitions for {0}', $filterCountryLabel)) ?></div>
					<?php endif; ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<?= $this->element('admin/index_country_scope') ?>
					<span class="index-header-sep" aria-hidden="true">|</span>
					<form method="get" class="table-search" role="search">
						<input type="hidden" name="country_id" value="<?= (int)$filterCountryId ?>">
						<input type="search"
							class="form-control form-control-sm table-search-input"
							id="table-search-input"
							name="<?= h($qKey) ?>"
							value="<?= h($indexSearch) ?>"
							placeholder="<?= h(__('Search...')) ?>"
							autocomplete="off">
						<button type="submit"
							class="btn btn-sm btn-outline-secondary table-search-btn"
							data-bs-toggle="tooltip"
							data-bs-placement="bottom"
							data-bs-html="true"
							title="<?= $tooltipSearch ?>">
							<i class="fa fa-search" aria-hidden="true"></i>
							<span class="visually-hidden"><?= h(__('Start search')) ?></span>
						</button>
						<?php if ($hasSearch): ?>
							<a href="<?= h($this->Url->build(['?' => ['clear_search' => '1', 'country_id' => (string)$filterCountryId]])) ?>"
								class="btn btn-sm btn-outline-secondary table-search-btn table-search-clear"
								role="button"
								data-bs-toggle="tooltip"
								data-bs-placement="bottom"
								data-bs-html="true"
								title="<?= $tooltipClear ?>">
								<i class="fa fa-times" aria-hidden="true"></i>
								<span class="visually-hidden"><?= h(__('Clear search')) ?></span>
							</a>
						<?php endif; ?>
					</form>
					<?= $this->element('admin/index_pagination', ['leadingSep' => true]) ?>
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
							<th scope="col" class="datetime competition_datetime"><?= $this->Paginator->sort('competition_datetime', __('Competition date')) ?></th>
							<th scope="col" class="string name"><?= $this->Paginator->sort('name', __('Name')) ?></th>
							<th scope="col" class="string country"><?= $this->Paginator->sort('Countries.name', __('Country')) ?></th>
							<th scope="col" class="string club"><?= $this->Paginator->sort('Clubs.name', __('Club')) ?></th>
							<th scope="col" class="date first_date_of_application"><?= $this->Paginator->sort('first_date_of_application', __('Application from')) ?></th>
							<th scope="col" class="date application_deadline"><?= $this->Paginator->sort('application_deadline', __('Deadline')) ?></th>
							<th scope="col" class="number count"><?= $this->Paginator->sort('user_count', __('Applicants')) ?></th>
							<?php if ($showVisibleColumn): ?>
								<th scope="col" class="boolean visible"><?= $this->Paginator->sort('visible', __('Visible')) ?></th>
							<?php endif; ?>
							<?php if ($showCreatedColumn): ?>
								<th scope="col" class="datetime created"><?= $this->Paginator->sort('created', __('Created')) ?></th>
							<?php endif; ?>
							<?php if ($showModifiedColumn): ?>
								<th scope="col" class="datetime modified"><?= $this->Paginator->sort('modified', __('Modified')) ?></th>
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
									<td class="datetime competition_datetime"><?= $row->competition_datetime ? h(\App\Utility\LocaleDateParser::format($row->competition_datetime, 'datetime_short')) : '' ?></td>
									<td class="string name">
										<div class="fw-bold"><?= h((string)$row->name) ?></div>
										<?php
										$underName = trim((string)$row->title);
										if ($underName === '') {
											$underName = trim((string)$row->subtitle);
										}
										?>
										<?php if ($underName !== ''): ?>
											<div class="text-muted small"><?= h($underName) ?></div>
										<?php endif; ?>
									</td>
									<td class="string country"><?= h(\App\Utility\AdminCountry::label((int)$row->country_id)) ?></td>
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
									<?php if ($showCreatedColumn): ?>
										<td class="datetime created">
											<?= $row->created ? h(\App\Utility\LocaleDateParser::format($row->created, 'datetime_short')) : '' ?>
											<?php
											$creatorName = $row->user
												? \App\Auth\MembershipProfile::displayName($row->user)
												: '';
											?>
											<?php if ($creatorName !== ''): ?>
												<div class="text-muted small"><?= h($creatorName) ?></div>
											<?php endif; ?>
										</td>
									<?php endif; ?>
									<?php if ($showModifiedColumn): ?>
										<td class="datetime modified">
											<?= $row->modified ? h(\App\Utility\LocaleDateParser::format($row->modified, 'datetime_short')) : '' ?>
											<?php
											$modifierName = '';
											if (!empty($row->modifier)) {
												$modifierName = \App\Auth\MembershipProfile::displayName($row->modifier);
											} elseif (!empty($row->user)) {
												$modifierName = \App\Auth\MembershipProfile::displayName($row->user);
											}
											?>
											<?php if ($modifierName !== ''): ?>
												<div class="text-muted small"><?= h($modifierName) ?></div>
											<?php endif; ?>
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
