<?php
/**
 * Global Admin search (header) — all models / text fields from config/admin_search.php
 *
 * @var \App\View\AppView $this
 */
$qKey = \App\Utility\AdminSearch::queryParam();
$currentQ = (string)$this->getRequest()->getQuery($qKey);
if ($this->getRequest()->getParam('controller') !== 'Search') {
	$currentQ = '';
}
$tooltipSearch = h('<b>' . __('Start search') . '</b><br>' . __('Search in the text fields of all configured tables.'));
$tooltipClear = h('<b>' . __('Clear search') . '</b><br>' . __('Clear the search and reset the results.'));
$action = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Search', 'action' => 'index']);
$clearUrl = $action;
$hasSearch = $currentQ !== '';
?>
			<li class="list-inline-item notif header-search-item">
				<form method="get" class="header-search" role="search" action="<?= h($action) ?>">
					<input type="search"
						class="form-control form-control-sm header-search-input"
						id="global-search-input"
						name="<?= h($qKey) ?>"
						value="<?= h($currentQ) ?>"
						placeholder="<?= h(__('Search...')) ?>"
						autocomplete="off">
					<button type="submit"
						class="btn btn-sm header-search-btn"
						data-bs-toggle="tooltip"
						data-bs-placement="bottom"
						data-bs-html="true"
						title="<?= $tooltipSearch ?>">
						<i class="fa fa-search" aria-hidden="true"></i>
						<span class="visually-hidden"><?= h(__('Start search')) ?></span>
					</button>
					<?php if ($hasSearch): ?>
						<a href="<?= h($clearUrl) ?>"
							class="btn btn-sm header-search-btn header-search-clear"
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
			</li>
