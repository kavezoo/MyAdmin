<?php
/**
 * Global Admin search results (Google-style hit list + AJAX modal).
 *
 * @var \App\View\AppView $this
 * @var string $q
 * @var list<array{model: string, label: string, controller: string, labelsKey: string, id: int, title: string}> $results
 * @var \Cake\Datasource\Paging\PaginatedResultSet $resultsPaginated
 */
$qKey = \App\Utility\AdminSearch::queryParam();

$this->Html->css(['pages/index', 'pages/search'], ['block' => true]);
$this->Paginator->setPaginated($resultsPaginated);

$tooltipSearch = h('<b>' . __('Start search') . '</b><br>' . __('Search in the text fields of all configured tables.'));
$tooltipClear = h('<b>' . __('Clear search') . '</b><br>' . __('Clear the search and reset the results.'));
$tooltipView = h('<b>' . __('View details') . '</b><br>' . __('View the selected record.'));
$tooltipEdit = h('<b>' . __('Edit') . '</b><br>' . __('Edit the selected record.'));

$config = [
	'rowDoubleClickAction' => 'modal',
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
		'country' => [
			'id' => __('ID'),
			'iso2' => __('ISO'),
			'name' => __('Name'),
			'locale' => __('Locale'),
			'continent' => __('Continent'),
			'visible' => __('Visible'),
			'pos' => __('Position'),
			'user_count' => __('Users'),
			'created' => __('Created'),
			'modified' => __('Modified'),
		],
		'setup' => [
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
	],
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);
$this->Html->script(['pages/index'], ['block' => 'scriptBottom']);

$searchAction = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Search', 'action' => 'index']);
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card-box search-page">
			<div class="card-header">
				<h3 class="fw-bold search-page-heading"><i class="fa fa-search" aria-hidden="true"></i> <?= __('Search') ?></h3>
			</div>
			<div class="card-body">
				<form method="get" class="search-page-form mb-4" role="search" action="<?= h($searchAction) ?>">
					<input type="search" class="form-control search-page-input" name="<?= h($qKey) ?>" value="<?= h($q) ?>" placeholder="<?= h(__('Search...')) ?>" autocomplete="off">
					<button type="submit" class="btn btn-sm btn-outline-secondary search-page-btn" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-html="true" title="<?= $tooltipSearch ?>">
						<i class="fa fa-search" aria-hidden="true"></i>
						<span class="visually-hidden"><?= h(__('Start search')) ?></span>
					</button>
					<?php if ($q !== ''): ?>
						<a href="<?= h($searchAction) ?>"
							class="btn btn-sm btn-outline-secondary search-page-btn"
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

				<?php if ($q === ''): ?>
					<p class="text-muted mb-0"><?= __('Enter a search term to find records in all configured tables.') ?></p>
				<?php elseif ((int)$resultsPaginated->totalCount() === 0): ?>
					<p class="text-muted mb-0"><?= __('No results found.') ?></p>
				<?php else: ?>
					<div class="search-results-toolbar d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
						<p class="search-results-stats text-muted mb-0"><?= h(__('About {0} results', [(int)$resultsPaginated->totalCount()])) ?></p>
						<?= $this->element('admin/index_pagination') ?>
					</div>
					<div class="search-results">
						<?php foreach ($results as $row): ?>
							<?php
							$tableLabel = __($row['label']);
							$labelsKey = $row['labelsKey'] !== '' ? $row['labelsKey'] : strtolower($row['model']);
							$getUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => $row['controller'], 'action' => 'recordGet']);
							$editUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => $row['controller'], 'action' => 'edit']);
							$viewUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => $row['controller'], 'action' => 'view']);
							$deleteUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => $row['controller'], 'action' => 'delete']);
							$detailsTitle = __('Record details');
							?>
							<article class="search-result">
								<div class="search-result-cite">
									<?= h($tableLabel) ?>
									<span class="search-result-cite-sep">·</span>
									#<?= (int)$row['id'] ?>
								</div>
								<div class="search-result-main">
									<h3 class="search-result-title">
										<a href="#"
											class="record-modal-link search-result-title-link"
											data-id="<?= (int)$row['id'] ?>"
											data-get-url="<?= h($getUrl) ?>"
											data-edit-url="<?= h($editUrl) ?>"
											data-view-url="<?= h($viewUrl) ?>"
											data-delete-url="<?= h($deleteUrl) ?>"
											data-labels="<?= h($labelsKey) ?>"
											data-title="<?= h($detailsTitle) ?>"
											data-source-table="<?= h($tableLabel) ?>">
											<?= h($row['title']) ?>
										</a>
									</h3>
									<div class="search-result-actions">
										<a href="<?= h($this->Url->build(['prefix' => 'Admin', 'controller' => $row['controller'], 'action' => 'view', $row['id']])) ?>"
											class="btn btn-sm btn-outline-info search-result-icon"
											data-bs-toggle="tooltip"
											data-bs-placement="top"
											data-bs-html="true"
											title="<?= $tooltipView ?>">
											<i class="fa fa-eye" aria-hidden="true"></i>
											<span class="visually-hidden"><?= h(__('View details')) ?></span>
										</a>
										<a href="<?= h($this->Url->build(['prefix' => 'Admin', 'controller' => $row['controller'], 'action' => 'edit', $row['id']])) ?>"
											class="btn btn-sm btn-outline-primary search-result-icon"
											data-bs-toggle="tooltip"
											data-bs-placement="top"
											data-bs-html="true"
											title="<?= $tooltipEdit ?>">
											<i class="fa fa-pencil" aria-hidden="true"></i>
											<span class="visually-hidden"><?= h(__('Edit')) ?></span>
										</a>
									</div>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<?php if ($q !== '' && (int)$resultsPaginated->totalCount() > 0): ?>
				<div class="card-footer">
					<?= $this->element('admin/index_footer') ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<?= $this->element('admin/modal_linked_record_view') ?>
