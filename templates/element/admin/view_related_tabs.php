<?php
/**
 * Related child associations as Bootstrap tab sheets (view.php).
 *
 * @var \App\View\AppView $this
 * @var list<array{
 *   id: string,
 *   title: string,
 *   count?: int|null,
 *   table: string
 * }> $relatedTabs Each tab: unique id, title, optional count, HTML table markup (or empty → no-data message)
 */
$relatedTabs = $relatedTabs ?? [];
if ($relatedTabs === []) {
	return;
}
$first = true;
?>
<div class="card mb-3 shadow border border-2 view-related-card">
	<div class="card-header">
		<h3 class="mb-0"><i class="fa fa-sitemap"></i> <?= __('Related records') ?></h3>
	</div>
	<div class="card-body p-2">
		<ul class="nav nav-tabs view-related-tabs" role="tablist">
			<?php foreach ($relatedTabs as $tab): ?>
				<?php
				$tabId = 'related-tab-' . $tab['id'];
				$paneId = 'related-pane-' . $tab['id'];
				$count = $tab['count'] ?? null;
				$label = $tab['title'];
				if ($count !== null) {
					$label .= ' (' . (int)$count . ')';
				}
				?>
				<li class="nav-item" role="presentation">
					<button
						class="nav-link<?= $first ? ' active' : '' ?>"
						id="<?= h($tabId) ?>"
						data-bs-toggle="tab"
						data-bs-target="#<?= h($paneId) ?>"
						type="button"
						role="tab"
						aria-controls="<?= h($paneId) ?>"
						aria-selected="<?= $first ? 'true' : 'false' ?>"
					><?= h($label) ?></button>
				</li>
				<?php $first = false; ?>
			<?php endforeach; ?>
		</ul>
		<div class="tab-content view-related-tab-content border border-top-0 p-2">
			<?php
			$first = true;
			foreach ($relatedTabs as $tab):
				$paneId = 'related-pane-' . $tab['id'];
				$tabId = 'related-tab-' . $tab['id'];
				$tableHtml = trim((string)($tab['table'] ?? ''));
				$isEmpty = $tableHtml === '';
			?>
				<div
					class="tab-pane fade<?= $first ? ' show active' : '' ?>"
					id="<?= h($paneId) ?>"
					role="tabpanel"
					aria-labelledby="<?= h($tabId) ?>"
					tabindex="0"
				>
					<?php if ($isEmpty): ?>
						<p class="text-muted text-center py-4 mb-0"><?= __('No related records.') ?></p>
					<?php else: ?>
						<div class="table-responsive">
							<?= $tableHtml ?>
						</div>
					<?php endif; ?>
				</div>
			<?php
				$first = false;
			endforeach;
			?>
		</div>
	</div>
</div>
