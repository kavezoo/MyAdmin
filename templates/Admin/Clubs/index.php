<?php
/**
 * Admin — global clubs index (optional country filter).
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Club> $clubs
 * @var int $filterCountryId
 * @var string $filterCountryLabel
 * @var array<int, string> $countryOptions
 */
$filterCountryId = (int)($filterCountryId ?? 0);
$filterCountryLabel = (string)($filterCountryLabel ?? '');
$countryOptions = $countryOptions ?? [];

$this->Html->css(['pages/index'], ['block' => true]);

$filterQuery = $this->request->getQueryParams();
unset($filterQuery['country_id']);
$filterQuery['page'] = '1';

$rowDoubleClickAction = 'modal';

$numberDecimals = [
	'integer' => 0,
	'decimal' => 2,
];

$showIdColumn = true;
$showEnabledColumn = true;
$showVisibleColumn = true;
$showCountColumn = true;

$indexColspan = 4; // country, name, city, actions
if ($showIdColumn) {
	$indexColspan++;
}
if ($showCountColumn) {
	$indexColspan += 2; // members + competitions
}
if ($showEnabledColumn) {
	$indexColspan++;
}
if ($showVisibleColumn) {
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
		'short_name' => __('Short name'),
		'country' => __('Country'),
		'city' => __('City'),
		'address' => __('Address'),
		'email' => __('Email'),
		'phone' => __('Phone'),
		'web' => __('Website'),
		'facebook' => __('Facebook'),
		'insta' => __('Instagram'),
		'enabled' => __('Enabled'),
		'visible' => __('Visible'),
		'pos' => __('Position'),
		'user_count' => __('Members'),
		'competition_count' => __('Competitions'),
		'created' => __('Created'),
		'modified' => __('Modified'),
	],
	'entityFieldLabels' => [
		'club' => [
			'id' => __('ID'),
			'name' => __('Name'),
			'short_name' => __('Short name'),
			'country' => __('Country'),
			'city' => __('City'),
			'address' => __('Address'),
			'email' => __('Email'),
			'phone' => __('Phone'),
			'web' => __('Website'),
			'facebook' => __('Facebook'),
			'insta' => __('Instagram'),
			'enabled' => __('Enabled'),
			'visible' => __('Visible'),
			'pos' => __('Position'),
			'user_count' => __('Members'),
			'competition_count' => __('Competitions'),
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
					<h3 class="fw-bold"><i class="fa fa-sitemap"></i> <?= __('Clubs') ?></h3>
					<?= h($rowDoubleClickHint) ?>
					<?php if ($filterCountryLabel !== ''): ?>
						<div class="text-muted"><?= h(__('Showing clubs for {0}', $filterCountryLabel)) ?></div>
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
					<?= $this->element('admin/index_pagination') ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-2">
				<div class="table-responsive">
					<table class="table table-bordered table-hover table-striped mb-0 index-data-table">
						<thead>
							<tr>
								<?php if ($showIdColumn): ?>
									<th scope="col" class="number id"><?= $this->Paginator->sort('id', '#') ?></th>
								<?php endif; ?>
								<th scope="col" class="string country"><?= $this->Paginator->sort('Countries.name', __('Country')) ?></th>
								<th scope="col" class="string name"><?= $this->Paginator->sort('name', __('Name')) ?></th>
								<th scope="col" class="string city"><?= $this->Paginator->sort('Cities.name', __('City')) ?></th>
								<?php if ($showCountColumn): ?>
									<th scope="col" class="number count"><?= $this->Paginator->sort('user_count', __('Members')) ?></th>
									<th scope="col" class="number count"><?= $this->Paginator->sort('competition_count', __('Competitions')) ?></th>
								<?php endif; ?>
								<?php if ($showEnabledColumn): ?>
									<th scope="col" class="boolean enabled"><?= $this->Paginator->sort('enabled', __('Enabled')) ?></th>
								<?php endif; ?>
								<?php if ($showVisibleColumn): ?>
									<th scope="col" class="boolean visible"><?= $this->Paginator->sort('visible', __('Visible')) ?></th>
								<?php endif; ?>
								<th scope="col" class="actions"><?= __('Actions') ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($clubs as $club): ?>
								<?php
								$userCount = (int)($club->user_count ?? 0);
								$competitionCount = (int)($club->competition_count ?? 0);
								$canDeleteRow = $userCount === 0 && $competitionCount === 0;
								$isLastVisited = isset($lastVisitedId) && (int)$lastVisitedId === (int)$club->id;
								?>
								<tr id="record-<?= (int)$club->id ?>"
									data-id="<?= (int)$club->id ?>"
									data-can-delete="<?= $canDeleteRow ? '1' : '0' ?>"<?= $isLastVisited ? ' class="last-visited"' : '' ?>>
									<?php if ($showIdColumn): ?>
										<td class="number id"><?= h($club->id) ?></td>
									<?php endif; ?>
									<td class="string country"><?= h(\App\Utility\AdminCountry::label((int)$club->country_id)) ?></td>
									<td class="string name"><?= h($club->name) ?></td>
									<td class="string city"><?= h($club->city !== null ? (string)$club->city->name : '') ?: '—' ?></td>
									<?php if ($showCountColumn): ?>
										<td class="number count text-end"><?= h(\App\Utility\LocaleNumberParser::formatCount($userCount, decimals: $numberDecimals['integer'])) ?></td>
										<td class="number count text-end"><?= h(\App\Utility\LocaleNumberParser::formatCount($competitionCount, decimals: $numberDecimals['integer'])) ?></td>
									<?php endif; ?>
									<?php if ($showEnabledColumn): ?>
										<td class="boolean enabled">
											<?= $club->enabled
												? '<i class="fa fa-check text-success"></i>'
												: '<i class="fa fa-times text-danger"></i>' ?>
										</td>
									<?php endif; ?>
									<?php if ($showVisibleColumn): ?>
										<td class="boolean visible">
											<?= $club->visible
												? '<i class="fa fa-check text-success"></i>'
												: '<i class="fa fa-times text-danger"></i>' ?>
										</td>
									<?php endif; ?>
									<td class="actions">
										<?= $this->Html->link(
											'<i class="fa fa-eye"></i>',
											['action' => 'view', $club->id],
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
											['action' => 'edit', $club->id],
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
											<a role="button" href="#" class="btn btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDelete) ?>" data-id="<?= (int)$club->id ?>">
												<i class="fa fa-trash"></i>
											</a>
											<?= $this->Form->create(null, [
												'url' => ['action' => 'delete', $club->id],
												'id' => 'delete-form-' . $club->id,
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
									</td>
								</tr>
							<?php endforeach; ?>
							<?php if ($clubs->count() === 0): ?>
								<tr>
									<td colspan="<?= (int)$indexColspan ?>" class="text-center text-muted py-4"><?= __('No records found.') ?></td>
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
