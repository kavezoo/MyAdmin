<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\CompetitionTextTemplate> $competitionTextTemplates
 * @var int $filterCountryId
 * @var string $filterCountryLabel
 * @var int|null $lastVisitedId
 */
$this->Html->css(['pages/index'], ['block' => true]);

$tooltipDetails = '<b>' . __('View details') . '</b><br>' . __('View the selected record details.');
$tooltipEdit = '<b>' . __('Edit') . '</b><br>' . __('Edit the selected record.');
$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.');
$config = [
	'rowDoubleClickAction' => 'modal',
	'recordGetUrl' => $this->Url->build(['action' => 'recordGet']),
	'editUrl' => $this->Url->build(['action' => 'edit']),
	'viewUrl' => $this->Url->build(['action' => 'view']),
	'deleteUrl' => $this->Url->build(['action' => 'delete']),
	'recordHtmlFields' => ['description'],
	'recordFieldLabels' => [
		'id' => __('ID'),
		'country' => __('Country'),
		'label' => __('Label'),
		'description' => __('Description'),
		'enabled' => __('Enabled'),
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
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-file-text-o"></i> <?= __('Competition text templates') ?></h3>
					<?php if (!empty($filterCountryLabel)): ?>
						<div class="text-muted"><?= h(__('Showing templates for {0}', $filterCountryLabel)) ?></div>
					<?php endif; ?>
					<?= h(__('Double-click a row to view the record details.')) ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<?= $this->element('admin/index_country_scope') ?>
					<span class="index-header-sep" aria-hidden="true">|</span>
					<?= $this->element('admin/table_search', [
						'tableSearchHidden' => ['country_id' => (int)($filterCountryId ?? 0)],
					]) ?>
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
				<table class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table" id="competition-text-templates-index-table">
					<thead>
						<tr>
							<th scope="col" class="number id"><?= $this->Paginator->sort('id', '#') ?></th>
							<th scope="col" class="string col-label"><?= $this->Paginator->sort('label', __('Label')) ?></th>
							<th scope="col" class="string country"><?= $this->Paginator->sort('Countries.name', __('Country')) ?></th>
							<th scope="col" class="boolean enabled"><?= $this->Paginator->sort('enabled', __('Enabled')) ?></th>
							<th scope="col" class="boolean visible"><?= $this->Paginator->sort('visible', __('Visible')) ?></th>
							<th scope="col" class="number pos"><?= $this->Paginator->sort('pos', __('Position')) ?></th>
							<th scope="col" class="datetime created"><?= $this->Paginator->sort('created', __('Created')) ?></th>
							<th scope="col" class="datetime modified"><?= $this->Paginator->sort('modified', __('Modified')) ?></th>
							<th scope="col" class="actions"><?= __('Actions') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ($competitionTextTemplates->count() === 0): ?>
							<tr><td colspan="9" class="text-center text-muted py-4"><?= __('No records found.') ?></td></tr>
						<?php else: ?>
							<?php foreach ($competitionTextTemplates as $row): ?>
								<?php $isLastVisited = isset($lastVisitedId) && (string)$lastVisitedId === (string)$row->id; ?>
								<tr id="record-<?= h((string)$row->id) ?>"
									data-id="<?= h((string)$row->id) ?>"
									data-can-delete="1"<?= $isLastVisited ? ' class="last-visited"' : '' ?>>
									<td class="number id"><?= h((string)$row->id) ?></td>
									<td class="string col-label fw-bold"><?= h((string)$row->label) ?></td>
									<td class="string country"><?= h(\App\Utility\AdminCountry::label((int)$row->country_id)) ?></td>
									<td class="boolean enabled"><?= !empty($row->enabled) ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>' ?></td>
									<td class="boolean visible"><?= !empty($row->visible) ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>' ?></td>
									<td class="number pos"><?= h(\App\Utility\LocaleNumberParser::format($row->pos, decimals: 0)) ?></td>
									<td class="datetime created"><?= $row->created ? h(\App\Utility\LocaleDateParser::format($row->created, 'datetime_short')) : '' ?></td>
									<td class="datetime modified"><?= $row->modified ? h(\App\Utility\LocaleDateParser::format($row->modified, 'datetime_short')) : '' ?></td>
									<td class="actions">
										<?= $this->Html->link('<i class="fa fa-eye"></i>', ['action' => 'view', $row->id], ['escape' => false, 'class' => 'btn btn-sm btn-outline-secondary', 'data-bs-toggle' => 'tooltip', 'data-bs-html' => 'true', 'title' => $tooltipDetails]) ?>
										<?= $this->Html->link('<i class="fa fa-pencil"></i>', ['action' => 'edit', $row->id], ['escape' => false, 'class' => 'btn btn-sm btn-outline-primary', 'data-bs-toggle' => 'tooltip', 'data-bs-html' => 'true', 'title' => $tooltipEdit]) ?>
										<?= $this->Form->create(null, ['url' => ['action' => 'delete', $row->id], 'id' => 'delete-form-' . $row->id, 'class' => 'd-inline']) ?>
										<button type="button" class="btn btn-sm btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-html="true" title="<?= h($tooltipDelete) ?>"><i class="fa fa-trash"></i></button>
										<?= $this->Form->end() ?>
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
