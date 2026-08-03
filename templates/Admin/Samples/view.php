<?php
/**
 * Sample view — main fields + related Cities tab (index-like table).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Sample $sample
 */
$this->Html->css(['pages/index'], ['block' => true]);

/**
 * Related-tab row double-click:
 * - 'modal' → AJAX linked modal (recordGet of related entity)
 * - 'edit'  → related entity edit form
 * - 'none'  → no action
 */
$rowDoubleClickAction = 'modal';

$citiesGetUrl = $this->Url->build(['controller' => 'Cities', 'action' => 'recordGet']);
$citiesEditUrl = $this->Url->build(['controller' => 'Cities', 'action' => 'edit']);
$citiesViewUrl = $this->Url->build(['controller' => 'Cities', 'action' => 'view']);
$citiesDeleteUrl = $this->Url->build(['controller' => 'Cities', 'action' => 'delete']);

$parentsGetUrl = $this->Url->build(['controller' => 'Parents', 'action' => 'recordGet']);
$parentsEditUrl = $this->Url->build(['controller' => 'Parents', 'action' => 'edit']);
$parentsViewUrl = $this->Url->build(['controller' => 'Parents', 'action' => 'view']);
$parentsDeleteUrl = $this->Url->build(['controller' => 'Parents', 'action' => 'delete']);

$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.');
$tooltipDeleteBlocked = '<b>' . __('Delete') . '</b><br>' . __('Cannot delete this record because it has related child records.');

$config = [
	'rowDoubleClickAction' => $rowDoubleClickAction,
	'entityFieldLabels' => [
		'city' => [
			'id' => __('ID'),
			'name' => __('Name'),
			'pos' => __('Position'),
			'visible' => __('Visible'),
			'sample_count' => __('Samples'),
			'created' => __('Created'),
			'modified' => __('Modified'),
		],
		'parent' => [
			'id' => __('ID'),
			'name' => __('Name'),
			'pos' => __('Position'),
			'visible' => __('Visible'),
			'sample_count' => __('Samples'),
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

$cities = $sample->cities ?? [];
$citiesCount = is_countable($cities) ? count($cities) : 0;

ob_start();
if ($citiesCount > 0):
?>
<table
	class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table related-records-table"
	data-get-url="<?= h($citiesGetUrl) ?>"
	data-edit-url="<?= h($citiesEditUrl) ?>"
	data-view-url="<?= h($citiesViewUrl) ?>"
	data-delete-url="<?= h($citiesDeleteUrl) ?>"
	data-delete-form-prefix="city"
	data-labels="city"
	data-title="<?= h(__('City details')) ?>"
>
	<thead>
		<tr>
			<th scope="col" class="number id">#</th>
			<th scope="col" class="string name"><?= __('Name') ?></th>
			<th scope="col" class="number pos"><?= __('Pos') ?></th>
			<th scope="col" class="boolean visible"><?= __('Visible') ?></th>
			<th scope="col" class="datetime created modified"><?= __('Created') ?><br><?= __('Modified') ?></th>
			<th scope="col" class="actions"><?= __('Actions') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($cities as $city): ?>
			<tr id="related-city-<?= (int)$city->id ?>" data-id="<?= (int)$city->id ?>" data-can-delete="<?= ((int)($city->sample_count ?? 0) === 0) ? '1' : '0' ?>">
				<td class="number id"><?= h($city->id) ?></td>
				<td class="string name">
					<a href="#"
						class="record-modal-link"
						data-id="<?= (int)$city->id ?>"
						data-get-url="<?= h($citiesGetUrl) ?>"
						data-edit-url="<?= h($citiesEditUrl) ?>"
						data-view-url="<?= h($citiesViewUrl) ?>"
						data-delete-url="<?= h($citiesDeleteUrl) ?>"
						data-delete-form-prefix="city"
						data-labels="city"
						data-title="<?= h(__('City details')) ?>"
					><?= h($city->name) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>
				</td>
				<td class="number pos text-end"><?= h(\App\Utility\LocaleNumberParser::format($city->pos, decimals: 0)) ?></td>
				<td class="boolean visible">
					<?= $city->visible
						? '<i class="fa fa-check text-success"></i>'
						: '<i class="fa fa-times text-danger"></i>' ?>
				</td>
				<td class="datetime created modified">
					<?= $city->created ? h(\App\Utility\LocaleDateParser::format($city->created, 'datetime_short')) : '' ?>
					<?php if ($city->modified): ?>
						<br><?= h(\App\Utility\LocaleDateParser::format($city->modified, 'datetime_short')) ?>
					<?php endif; ?>
				</td>
				<td class="actions">
					<?php $canDeleteRelated = ((int)($city->sample_count ?? 0) === 0); ?>
					<?= $this->Html->link(
						'<i class="fa fa-eye"></i>',
						['controller' => 'Cities', 'action' => 'view', $city->id],
						[
							'escape' => false,
							'class' => 'btn btn-outline-info',
							'role' => 'button',
							'title' => __('View details'),
						]
					) ?>
					<?= $this->Html->link(
						'<i class="fa fa-pencil"></i>',
						['controller' => 'Cities', 'action' => 'edit', $city->id],
						[
							'escape' => false,
							'class' => 'btn btn-outline-primary',
							'role' => 'button',
							'title' => __('Edit'),
						]
					) ?>
					<?php if ($canDeleteRelated): ?>
						<a role="button" href="#" class="btn btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDelete) ?>" data-id="<?= (int)$city->id ?>">
							<i class="fa fa-trash"></i>
						</a>
						<?= $this->Form->create(null, [
							'url' => ['controller' => 'Cities', 'action' => 'delete', $city->id],
							'id' => 'delete-form-city-' . $city->id,
							'class' => 'd-none js-row-delete-form',
						]) ?>
						<?= $this->Form->end() ?>
					<?php else: ?>
						<span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDeleteBlocked) ?>">
							<a role="button" href="#" class="btn btn-outline-secondary disabled" tabindex="-1" aria-disabled="true">
								<i class="fa fa-trash"></i>
							</a>
						</span>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php
endif;
$citiesTable = ob_get_clean();
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-eye"></i> <?= __('Sample details') ?></h3>
					<?= __('View the selected record (read-only).') ?>
				</div>
				<div class="float-right">
					<a role="button" href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<dl class="record-view-fields mb-0">
					<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h($sample->id) ?></dd></div>
					<div class="record-view-row">
						<dt><?= __('Parent') ?></dt>
						<dd>
							<?php if (!empty($sample->parent_id) && !empty($sample->parent->name)): ?>
								<a href="#"
									class="record-modal-link"
									data-id="<?= (int)$sample->parent_id ?>"
									data-get-url="<?= h($parentsGetUrl) ?>"
									data-edit-url="<?= h($parentsEditUrl) ?>"
									data-view-url="<?= h($parentsViewUrl) ?>"
									data-delete-url="<?= h($parentsDeleteUrl) ?>"
									data-delete-form-prefix="parent"
									data-labels="parent"
									data-title="<?= h(__('Parent details')) ?>"
								><?= h($sample->parent->name) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>
								<?= $this->Form->create(null, [
									'url' => ['controller' => 'Parents', 'action' => 'delete', $sample->parent_id],
									'id' => 'delete-form-parent-' . (int)$sample->parent_id,
									'class' => 'd-none js-row-delete-form',
								]) ?>
								<?= $this->Form->end() ?>
							<?php else: ?>
								—
							<?php endif; ?>
						</dd>
					</div>
					<div class="record-view-row"><dt><?= __('Name') ?></dt><dd><?= h($sample->name) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Number') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($sample->szam, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Net') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::formatCurrency($sample->netto, decimals: 2)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Date') ?></dt><dd><?= $sample->datum ? h(\App\Utility\LocaleDateParser::format($sample->datum, 'date')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Time') ?></dt><dd><?= $sample->ido ? h(\App\Utility\LocaleDateParser::format($sample->ido, 'time_short')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Date and time') ?></dt><dd><?= $sample->datumido ? h(\App\Utility\LocaleDateParser::format($sample->datumido, 'datetime_short')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Boolean') ?></dt><dd><?= $sample->logikai ? __('Yes') : __('No') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Position') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($sample->pos, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Visible') ?></dt><dd><?= $sample->visible ? __('Yes') : __('No') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Cities') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::formatCount($sample->city_count, decimals: 0)) ?></dd></div>
					<?php if ($citiesCount > 0): ?>
						<div class="record-view-row">
							<dt><?= __('City list') ?></dt>
							<dd class="record-related-list">
								<?php
								$cityLinks = [];
								foreach ($cities as $city) {
									$cityLinks[] = '<a href="#" class="record-modal-link"'
										. ' data-id="' . (int)$city->id . '"'
										. ' data-get-url="' . h($citiesGetUrl) . '"'
										. ' data-edit-url="' . h($citiesEditUrl) . '"'
										. ' data-view-url="' . h($citiesViewUrl) . '"'
										. ' data-delete-url="' . h($citiesDeleteUrl) . '"'
										. ' data-delete-form-prefix="city"'
										. ' data-labels="city"'
										. ' data-title="' . h(__('City details')) . '"'
										. '>' . h($city->name)
										. '<span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>';
								}
								echo implode(', ', $cityLinks);
								?>
							</dd>
						</div>
					<?php endif; ?>
					<div class="record-view-row"><dt><?= __('Created') ?></dt><dd><?= $sample->created ? h(\App\Utility\LocaleDateParser::format($sample->created, 'datetime_short')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Modified') ?></dt><dd><?= $sample->modified ? h(\App\Utility\LocaleDateParser::format($sample->modified, 'datetime_short')) : '—' ?></dd></div>
				</dl>
			</div>
			<div class="card-footer">
				<div class="record-view-footer-actions">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . __('Edit'),
						['action' => 'edit', $sample->id],
						['escape' => false, 'class' => 'btn btn-primary']
					) ?>
				</div>
			</div>
		</div>

		<?= $this->element('admin/view_related_tabs', [
			'relatedTabs' => [
				[
					'id' => 'cities',
					'title' => __('Cities'),
					'count' => $citiesCount,
					'table' => $citiesTable,
				],
			],
		]) ?>
	</div>
</div>

<?= $this->element('admin/modal_linked_record_view') ?>
