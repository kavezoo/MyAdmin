<?php
/**
 * Counties index.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\County> $counties
 * @var bool $canDeleteCounty
 * @var array<int, true> $deletableCountyIds
 * @var int $filterCountryId
 * @var string $filterCountryLabel
 * @var array<int, string> $countryOptions
 * @var array<int, string> $countryFlags
 */
$canDeleteCounty = (bool)($canDeleteCounty ?? true);
$deletableCountyIds = $deletableCountyIds ?? [];
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
$showVisibleColumn = true;
$showCreatedColumn = true;
$showModifiedColumn = true;

$showTimestampColumn = $showCreatedColumn || $showModifiedColumn;
$indexColspan = 6; // country, name, shortname, region, pos, actions
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
	'deleteUrl' => $canDeleteCounty ? $this->Url->build(['action' => 'delete']) : '',
	'recordFieldLabels' => [
		'id' => __('ID'),
		'name' => __('Name'),
		'shortname' => __('Short name'),
		'capitalcity' => __('Capital city'),
		'region' => __('Region'),
		'country' => __('Country'),
		'visible' => __('Visible'),
		'pos' => __('Position'),
		'created' => __('Created'),
		'modified' => __('Modified'),
	],
	'entityFieldLabels' => [
		'county' => [
			'id' => __('ID'),
			'name' => __('Name'),
			'shortname' => __('Short name'),
			'capitalcity' => __('Capital city'),
			'region' => __('Region'),
			'country' => __('Country'),
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

$jsCfg = [
	'countryAjaxUrl' => $this->Url->build(['action' => 'countryOptions']),
	'flagBase' => $this->Url->build('/img/flags/'),
	'flags' => $countryFlags,
	'noResults' => __('No results found.'),
	'searching' => __('Search...'),
	'countryPlaceholder' => __('Select country...'),
];
$this->Html->scriptBlock(
	'window.AdminCountiesIndex = ' . json_encode($jsCfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';',
	['block' => 'script']
);

$this->Html->script([
	'/plugins/select2-4.1.0/js/select2.full.min',
	'pages/index',
	'pages/admin_counties_index',
], ['block' => 'scriptBottom']);
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-map"></i> <?= __('Counties') ?></h3>
					<?= h($rowDoubleClickHint) ?>
					<?php if ($filterCountryLabel !== ''): ?>
						<div class="text-muted"><?= h(__('Showing counties for {0}', $filterCountryLabel)) ?></div>
					<?php elseif ($filterCountryId < 1): ?>
						<div class="text-muted"><?= h(__('Showing counties for all countries')) ?></div>
					<?php endif; ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<form method="get" action="<?= h($this->Url->build(['action' => 'index'])) ?>"
						class="counties-country-filter mb-0"
						id="counties-country-filter">
						<?php foreach ($filterQuery as $name => $value): ?>
							<?php if (!is_scalar($value)) {
								continue;
							} ?>
							<input type="hidden" name="<?= h((string)$name) ?>" value="<?= h((string)$value) ?>">
						<?php endforeach; ?>
						<select name="country_id"
							id="counties-country-id"
							class="form-select"
							style="min-width: 14rem;"
							aria-label="<?= h(__('Country')) ?>"
							data-ajax-url="<?= h($this->Url->build(['action' => 'countryOptions'])) ?>"
							data-placeholder="<?= h(__('Select country...')) ?>">
							<?php if ($filterCountryId < 1): ?>
								<option value="0" selected><?= h(__('All countries')) ?></option>
							<?php else: ?>
								<option value="<?= (int)$filterCountryId ?>" selected>
									<?= h($filterCountryLabel !== '' ? $filterCountryLabel : (string)$filterCountryId) ?>
								</option>
							<?php endif; ?>
						</select>
					</form>
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
								<th scope="col" class="string name"><?= $this->Paginator->sort('name', __('Name')) ?></th>
								<th scope="col" class="string shortname"><?= $this->Paginator->sort('shortname', __('Short name')) ?></th>
								<th scope="col" class="string region"><?= $this->Paginator->sort('region', __('Region')) ?></th>
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
							<?php foreach ($counties as $county): ?>
								<?php
								$isLastVisited = isset($lastVisitedId) && (int)$lastVisitedId === (int)$county->id;
								$rowCanDelete = $canDeleteCounty && !empty($deletableCountyIds[(int)$county->id]);
								?>
								<tr id="record-<?= (int)$county->id ?>" data-id="<?= (int)$county->id ?>" data-can-delete="<?= $rowCanDelete ? '1' : '0' ?>"<?= $isLastVisited ? ' class="last-visited"' : '' ?>>
									<?php if ($showIdColumn): ?>
										<td class="number id"><?= h($county->id) ?></td>
									<?php endif; ?>
									<td class="string country"><?= h(\App\Utility\AdminCountry::label((int)$county->country_id)) ?></td>
									<td class="string name"><?= h($county->name) ?></td>
									<td class="string shortname"><?= h($county->shortname) ?></td>
									<td class="string region"><?= h($county->region) ?></td>
									<?php if ($showVisibleColumn): ?>
										<td class="boolean visible">
											<?= $county->visible
												? '<i class="fa fa-check text-success"></i>'
												: '<i class="fa fa-times text-danger"></i>' ?>
										</td>
									<?php endif; ?>
									<td class="number pos text-end"><?= h(\App\Utility\LocaleNumberParser::format($county->pos, decimals: $numberDecimals['integer'])) ?></td>
									<?php if ($showTimestampColumn): ?>
										<td class="datetime<?= $showCreatedColumn ? ' created' : '' ?><?= $showModifiedColumn ? ' modified' : '' ?>">
											<?php if ($showCreatedColumn): ?>
												<?= $county->created ? h(\App\Utility\LocaleDateParser::format($county->created, 'datetime_short')) : '' ?>
											<?php endif; ?>
											<?php if ($showCreatedColumn && $showModifiedColumn && $county->modified): ?>
												<br>
											<?php endif; ?>
											<?php if ($showModifiedColumn): ?>
												<?= $county->modified ? h(\App\Utility\LocaleDateParser::format($county->modified, 'datetime_short')) : '' ?>
											<?php endif; ?>
										</td>
									<?php endif; ?>
									<td class="actions">
										<?= $this->Html->link(
											'<i class="fa fa-eye"></i>',
											['action' => 'view', $county->id],
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
											['action' => 'edit', $county->id],
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
										<?php if ($canDeleteCounty): ?>
											<?php if ($rowCanDelete): ?>
												<a role="button" href="#" class="btn btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDelete) ?>" data-id="<?= (int)$county->id ?>">
													<i class="fa fa-trash"></i>
												</a>
												<?= $this->Form->create(null, [
													'url' => ['action' => 'delete', $county->id],
													'id' => 'delete-form-' . $county->id,
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
							<?php if ($counties->count() === 0): ?>
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
