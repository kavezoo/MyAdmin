<?php
/**
 * Parent view — main fields + related Samples tab.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ParentRecord $parent
 */
$this->Html->css(['pages/index'], ['block' => true]);

/**
 * Related-tab row double-click: 'modal' | 'edit' | 'none'
 */
$rowDoubleClickAction = 'modal';

$samplesGetUrl = $this->Url->build(['controller' => 'Samples', 'action' => 'recordGet']);
$samplesEditUrl = $this->Url->build(['controller' => 'Samples', 'action' => 'edit']);
$samplesViewUrl = $this->Url->build(['controller' => 'Samples', 'action' => 'view']);
$samplesDeleteUrl = $this->Url->build(['controller' => 'Samples', 'action' => 'delete']);

$config = [
	'rowDoubleClickAction' => $rowDoubleClickAction,
	'entityFieldLabels' => [
		'sample' => [
			'id' => __('ID'),
			'parent' => __('Parent'),
			'name' => __('Name'),
			'szam' => __('Number'),
			'netto' => __('Net'),
			'datum' => __('Date'),
			'ido' => __('Time'),
			'datumido' => __('Date and time'),
			'logikai' => __('Boolean'),
			'pos' => __('Position'),
			'visible' => __('Visible'),
			'city_count' => __('Cities'),
			'cities' => __('City list'),
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

$samples = $parent->samples ?? [];
$samplesCount = is_countable($samples) ? count($samples) : 0;

ob_start();
if ($samplesCount > 0):
?>
<table
	class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table related-records-table"
	data-get-url="<?= h($samplesGetUrl) ?>"
	data-edit-url="<?= h($samplesEditUrl) ?>"
	data-view-url="<?= h($samplesViewUrl) ?>"
	data-delete-url="<?= h($samplesDeleteUrl) ?>"
	data-delete-form-prefix="sample"
	data-labels="sample"
	data-title="<?= h(__('Sample details')) ?>"
>
	<thead>
		<tr>
			<th scope="col" class="number id">#</th>
			<th scope="col" class="string name"><?= __('Name') ?></th>
			<th scope="col" class="number szam"><?= __('Number') ?></th>
			<th scope="col" class="currency netto"><?= __('Net') ?></th>
			<th scope="col" class="boolean visible"><?= __('Visible') ?></th>
			<th scope="col" class="datetime created modified"><?= __('Created') ?><br><?= __('Modified') ?></th>
			<th scope="col" class="actions"><?= __('Actions') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($samples as $sample): ?>
			<tr id="related-sample-<?= (int)$sample->id ?>" data-id="<?= (int)$sample->id ?>" data-can-delete="<?= ((int)($sample->city_count ?? 0) === 0) ? '1' : '0' ?>">
				<td class="number id"><?= h($sample->id) ?></td>
				<td class="string name">
					<a href="#"
						class="record-modal-link"
						data-id="<?= (int)$sample->id ?>"
						data-labels="sample"
						data-title="<?= h(__('Sample details')) ?>"
					><?= h($sample->name) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>
				</td>
				<td class="number szam text-end"><?= h(\App\Utility\LocaleNumberParser::format($sample->szam, decimals: 0)) ?></td>
				<td class="currency netto text-end">
					<span class="currency-amount"><?= h(\App\Utility\LocaleNumberParser::format($sample->netto, decimals: 2)) ?></span> <?= h(\App\Utility\LocaleNumberParser::currencySymbol()) ?>
				</td>
				<td class="boolean visible">
					<?= $sample->visible
						? '<i class="fa fa-check text-success"></i>'
						: '<i class="fa fa-times text-danger"></i>' ?>
				</td>
				<td class="datetime created modified">
					<?= $sample->created ? h($sample->created->format('Y.m.d. H:i')) : '' ?>
					<?php if ($sample->modified): ?>
						<br><?= h($sample->modified->format('Y.m.d. H:i')) ?>
					<?php endif; ?>
				</td>
				<td class="actions">
					<?= $this->Html->link(
						'<i class="fa fa-eye"></i>',
						['controller' => 'Samples', 'action' => 'view', $sample->id],
						['escape' => false, 'class' => 'btn btn-outline-info', 'role' => 'button', 'title' => __('View details')]
					) ?>
					<?= $this->Html->link(
						'<i class="fa fa-pencil"></i>',
						['controller' => 'Samples', 'action' => 'edit', $sample->id],
						['escape' => false, 'class' => 'btn btn-outline-primary', 'role' => 'button', 'title' => __('Edit')]
					) ?>
					<?= $this->Form->create(null, [
						'url' => ['controller' => 'Samples', 'action' => 'delete', $sample->id],
						'id' => 'delete-form-sample-' . $sample->id,
						'class' => 'd-none js-row-delete-form',
					]) ?>
					<?= $this->Form->end() ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php
endif;
$samplesTable = ob_get_clean();
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-eye"></i> <?= __('Parent details') ?></h3>
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
					<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h($parent->id) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Name') ?></dt><dd><?= h($parent->name) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Position') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($parent->pos, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Visible') ?></dt><dd><?= $parent->visible ? __('Yes') : __('No') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Samples') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::formatCount($parent->sample_count, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Created') ?></dt><dd><?= $parent->created ? h($parent->created->format('Y.m.d. H:i')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Modified') ?></dt><dd><?= $parent->modified ? h($parent->modified->format('Y.m.d. H:i')) : '—' ?></dd></div>
				</dl>
			</div>
			<div class="card-footer">
				<?= $this->Html->link(
					'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . __('Edit'),
					['action' => 'edit', $parent->id],
					['escape' => false, 'class' => 'btn btn-primary']
				) ?>
				<?= $this->Html->link(
					'<span class="btn-label"><i class="fa fa-arrow-left"></i></span>' . __('Back to list'),
					['action' => 'index'],
					['escape' => false, 'class' => 'btn btn-outline-secondary ms-2']
				) ?>
			</div>
		</div>

		<?= $this->element('admin/view_related_tabs', [
			'relatedTabs' => [
				[
					'id' => 'samples',
					'title' => __('Samples'),
					'count' => $samplesCount,
					'table' => $samplesTable,
				],
			],
		]) ?>
	</div>
</div>

<?= $this->element('admin/modal_linked_record_view') ?>
