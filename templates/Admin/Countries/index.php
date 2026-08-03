<?php
/**
 * Countries index — reference data; edit only visible + pos.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Country> $countries
 */
$this->Html->css(['pages/index'], ['block' => true]);

$rowDoubleClickAction = 'modal';

$numberDecimals = [
	'integer' => 0,
	'decimal' => 2,
];

$showIdColumn = true;
$showCountColumn = true;
$showVisibleColumn = true;
$showCreatedColumn = true;
$showModifiedColumn = true;

$showTimestampColumn = $showCreatedColumn || $showModifiedColumn;
$indexColspan = 6; // iso2, name, locale, continent, pos, actions
if ($showIdColumn) {
	$indexColspan++;
}
if ($showVisibleColumn) {
	$indexColspan++;
}
if ($showCountColumn) {
	$indexColspan++;
}
if ($showTimestampColumn) {
	$indexColspan++;
}

$tooltipDetails = '<b>' . __('View details') . '</b><br>' . __('View the selected record details.');
$tooltipEdit = '<b>' . __('Edit') . '</b><br>' . __('Only visibility and position can be changed.');

$rowDoubleClickHints = [
	'modal' => __('Double-click a row to view the record details.'),
	'edit' => __('Double-click a row to edit the record.'),
	'none' => '',
];
$rowDoubleClickHint = $rowDoubleClickHints[$rowDoubleClickAction] ?? $rowDoubleClickHints['modal'];

$config = [
	'rowDoubleClickAction' => $rowDoubleClickAction,
	'recordGetUrl' => $this->Url->build(['action' => 'recordGet']),
	'editUrl' => $this->Url->build(['action' => 'edit']),
	'viewUrl' => $this->Url->build(['action' => 'view']),
	'recordFieldLabels' => [
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
	'relatedLinkFields' => [],
	'entityFieldLabels' => [
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
					<h3 class="fw-bold"><i class="fa fa-globe"></i> <?= __('Countries') ?></h3>
					<?php if ($rowDoubleClickHint !== ''): ?>
						<?= h($rowDoubleClickHint) ?>
					<?php endif; ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2">
					<?= $this->element('admin/table_search') ?>
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
								<th scope="col" class="string iso2"><?= $this->Paginator->sort('iso2', __('ISO')) ?></th>
								<th scope="col" class="string name"><?= $this->Paginator->sort('name', __('Name')) ?></th>
								<th scope="col" class="string locale"><?= $this->Paginator->sort('locale', __('Locale')) ?></th>
								<th scope="col" class="string continent"><?= $this->Paginator->sort('Continents.name', __('Continent')) ?></th>
								<?php if ($showVisibleColumn): ?>
									<th scope="col" class="boolean visible"><?= $this->Paginator->sort('visible', __('Visible')) ?></th>
								<?php endif; ?>
								<th scope="col" class="number pos"><?= $this->Paginator->sort('pos', __('Position')) ?></th>
								<?php if ($showCountColumn): ?>
									<th scope="col" class="number count"><?= $this->Paginator->sort('user_count', __('Users')) ?></th>
								<?php endif; ?>
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
							<?php foreach ($countries as $country): ?>
								<?php
								$isLastVisited = isset($lastVisitedId) && (int)$lastVisitedId === (int)$country->id;
								?>
								<tr id="record-<?= (int)$country->id ?>" data-id="<?= (int)$country->id ?>" data-can-delete="0"<?= $isLastVisited ? ' class="last-visited"' : '' ?>>
									<?php if ($showIdColumn): ?>
										<td class="number id"><?= h($country->id) ?></td>
									<?php endif; ?>
									<td class="string iso2"><code><?= h($country->iso2) ?></code></td>
									<td class="string name"><?= h($country->name) ?></td>
									<td class="string locale"><code><?= h($country->locale) ?></code></td>
									<td class="string continent"><?= h($country->continent->name ?? '') ?></td>
									<?php if ($showVisibleColumn): ?>
										<td class="boolean visible">
											<?= $country->visible
												? '<i class="fa fa-check text-success"></i>'
												: '<i class="fa fa-times text-danger"></i>' ?>
										</td>
									<?php endif; ?>
									<td class="number pos text-end"><?= h(\App\Utility\LocaleNumberParser::format($country->pos, decimals: $numberDecimals['integer'])) ?></td>
									<?php if ($showCountColumn): ?>
										<td class="number count text-end"><?= h(\App\Utility\LocaleNumberParser::formatCount($country->user_count, decimals: $numberDecimals['integer'])) ?></td>
									<?php endif; ?>
									<?php if ($showTimestampColumn): ?>
										<td class="datetime<?= $showCreatedColumn ? ' created' : '' ?><?= $showModifiedColumn ? ' modified' : '' ?>">
											<?php if ($showCreatedColumn): ?>
												<?= $country->created ? h(\App\Utility\LocaleDateParser::format($country->created, 'datetime_short')) : '' ?>
											<?php endif; ?>
											<?php if ($showCreatedColumn && $showModifiedColumn && $country->modified): ?>
												<br>
											<?php endif; ?>
											<?php if ($showModifiedColumn): ?>
												<?= $country->modified ? h(\App\Utility\LocaleDateParser::format($country->modified, 'datetime_short')) : '' ?>
											<?php endif; ?>
										</td>
									<?php endif; ?>
									<td class="actions">
										<?= $this->Html->link(
											'<i class="fa fa-eye"></i>',
											['action' => 'view', $country->id],
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
											['action' => 'edit', $country->id],
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
									</td>
								</tr>
							<?php endforeach; ?>
							<?php if ($countries->count() === 0): ?>
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
<?= $this->element('admin/modal_linked_record_view') ?>
