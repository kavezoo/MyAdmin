<?php
/**
 * Cities index.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\City> $cities
 * @var int $filterCountryId
 * @var string $filterCountryLabel
 * @var array<int, string> $countryOptions
 * @var array<int, string> $countryFlags
 */
$filterCountryId = (int)($filterCountryId ?? 0);
$filterCountryLabel = (string)($filterCountryLabel ?? '');
$countryOptions = $countryOptions ?? [];
$countryFlags = $countryFlags ?? [];

$this->Html->css([
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
	'pages/index',
	'pages/users_auth',
], ['block' => true]);

$filterQuery = $this->request->getQueryParams();
unset($filterQuery['country_id']);
$filterQuery['page'] = '1';

$rowDoubleClickAction = 'modal';

$numberDecimals = [
	'integer' => 0,
	'decimal' => 2,
];

$showIdColumn = true;

$indexColspan = 6; // country, county, name, shortname, zip, actions
if ($showIdColumn) {
	$indexColspan++;
}

$tooltipDetails = '<b>' . __('View details') . '</b><br>' . __('View the selected record details.');
$tooltipEdit = '<b>' . __('Edit') . '</b><br>' . __('Edit the selected record.');
$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.');

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
		'shortname' => __('Short name'),
		'zip' => __('ZIP'),
		'country' => __('Country'),
		'county' => __('County'),
		'lat' => __('Latitude'),
		'lng' => __('Longitude'),
		'lat2' => __('Latitude (import)'),
		'lng2' => __('Longitude (import)'),
	],
	'entityFieldLabels' => [
		'city' => [
			'id' => __('ID'),
			'name' => __('Name'),
			'shortname' => __('Short name'),
			'zip' => __('ZIP'),
			'country' => __('Country'),
			'county' => __('County'),
			'lat' => __('Latitude'),
			'lng' => __('Longitude'),
			'lat2' => __('Latitude (import)'),
			'lng2' => __('Longitude (import)'),
		],
	],
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);

$jsCfg = [
	'countryAjaxUrl' => $this->Url->build(['action' => 'countryOptions']),
	'flagBase' => $this->Url->build('/img/flags/'),
	'flags' => $countryFlags,
	'noResults' => __('No results found.'),
	'searching' => __('Search...'),
	'countryPlaceholder' => __('Select country...'),
];
$this->Html->scriptBlock(
	'window.AdminCitiesIndex = ' . json_encode($jsCfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';',
	['block' => 'script']
);

$this->Html->script([
	'/plugins/select2-4.1.0/js/select2.full.min',
	'pages/index',
	'pages/admin_cities_index',
], ['block' => 'scriptBottom']);
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-map-marker"></i> <?= __('Cities') ?></h3>
					<?= h($rowDoubleClickHint) ?>
					<?php if ($filterCountryLabel !== ''): ?>
						<div class="text-muted"><?= h(__('Showing cities for {0}', $filterCountryLabel)) ?></div>
					<?php endif; ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<?= $this->element('admin/index_country_scope') ?>
					<span class="index-header-sep" aria-hidden="true">|</span>
					<?php
					$indexSearch = (string)($this->get('indexSearch') ?? '');
					$qKey = \App\Utility\AdminSearch::queryParam();
					$tooltipSearch = h('<b>' . __('Start search') . '</b><br>' . __('Search in the text fields of this list.'));
					$tooltipClear = h('<b>' . __('Clear search') . '</b><br>' . __('Clear the saved search and return to the last visited record.'));
					$hasSearch = $indexSearch !== '';
					?>
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
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-bordered table-hover table-striped mb-0 index-data-table" id="index-data-table">
						<thead>
							<tr>
								<?php if ($showIdColumn): ?>
									<th scope="col" class="number id"><?= $this->Paginator->sort('id', '#') ?></th>
								<?php endif; ?>
								<th scope="col" class="string country"><?= $this->Paginator->sort('Countries.name', __('Country')) ?></th>
								<th scope="col" class="string county"><?= $this->Paginator->sort('Counties.name', __('County')) ?></th>
								<th scope="col" class="string name"><?= $this->Paginator->sort('name', __('Name')) ?></th>
								<th scope="col" class="string shortname"><?= $this->Paginator->sort('shortname', __('Short name')) ?></th>
								<th scope="col" class="string zip"><?= $this->Paginator->sort('zip', __('ZIP')) ?></th>
								<th scope="col" class="actions"><?= __('Actions') ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($cities as $city): ?>
								<?php
								$isLastVisited = isset($lastVisitedId) && (int)$lastVisitedId === (int)$city->id;
								?>
								<tr id="record-<?= (int)$city->id ?>" data-id="<?= (int)$city->id ?>" data-can-delete="1"<?= $isLastVisited ? ' class="last-visited"' : '' ?>>
									<?php if ($showIdColumn): ?>
										<td class="number id"><?= h($city->id) ?></td>
									<?php endif; ?>
									<td class="string country"><?= h(\App\Utility\AdminCountry::label((int)$city->country_id)) ?></td>
									<td class="string county"><?= h($city->county !== null ? (string)$city->county->name : '') ?></td>
									<td class="string name"><?= h($city->name) ?></td>
									<td class="string shortname"><?= h($city->shortname) ?></td>
									<td class="string zip"><?= h($city->zip ?? '') ?></td>
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
										<a role="button" href="#" class="btn btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDelete) ?>" data-id="<?= (int)$city->id ?>">
											<i class="fa fa-trash"></i>
										</a>
										<?= $this->Form->create(null, [
											'url' => ['action' => 'delete', $city->id],
											'id' => 'delete-form-' . $city->id,
											'class' => 'd-none js-row-delete-form',
										]) ?>
										<?= $this->Form->end() ?>
									</td>
								</tr>
							<?php endforeach; ?>
							<?php if ($cities->count() === 0): ?>
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
