<?php
/**
 * Index card header table search (current model text fields only).
 *
 * @var \App\View\AppView $this
 * @var string|null $indexSearch
 * @var array<string, scalar>|null $tableSearchHidden Extra GET fields preserved on search/clear (e.g. language_id).
 */
$indexSearch = $indexSearch ?? '';
/** @var array<string, scalar> $tableSearchHidden */
$tableSearchHidden = $tableSearchHidden ?? [];
$qKey = \App\Utility\AdminSearch::queryParam();
$tooltipSearch = h('<b>' . __('Start search') . '</b><br>' . __('Search in the text fields of this list.'));
$tooltipClear = h('<b>' . __('Clear search') . '</b><br>' . __('Clear the saved search and return to the last visited record.'));
$clearQuery = ['clear_search' => '1'];
foreach ($tableSearchHidden as $hiddenName => $hiddenValue) {
	$clearQuery[(string)$hiddenName] = $hiddenValue;
}
$clearUrl = $this->Url->build(['?' => $clearQuery]);
$hasSearch = $indexSearch !== '';
?>
<form method="get" class="table-search" role="search">
	<?php foreach ($tableSearchHidden as $hiddenName => $hiddenValue): ?>
		<input type="hidden" name="<?= h((string)$hiddenName) ?>" value="<?= h((string)$hiddenValue) ?>">
	<?php endforeach; ?>
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
		<a href="<?= h($clearUrl) ?>"
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
