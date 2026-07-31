<?php
/**
 * Sample view — main fields + related Cities tab (index-like table).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Sample $sample
 */
$this->Html->css(['pages/index'], ['block' => true]);

$cities = $sample->cities ?? [];
$citiesCount = is_countable($cities) ? count($cities) : 0;

ob_start();
if ($citiesCount > 0):
?>
<table class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table">
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
			<tr id="related-city-<?= (int)$city->id ?>" data-id="<?= (int)$city->id ?>">
				<td class="number id"><?= h($city->id) ?></td>
				<td class="string name"><?= h($city->name) ?></td>
				<td class="number pos text-end"><?= h(\App\Utility\LocaleNumberParser::format($city->pos, decimals: 0)) ?></td>
				<td class="boolean visible">
					<?= $city->visible
						? '<i class="fa fa-check text-success"></i>'
						: '<i class="fa fa-times text-danger"></i>' ?>
				</td>
				<td class="datetime created modified">
					<?= $city->created ? h($city->created->format('Y.m.d. H:i')) : '' ?>
					<?php if ($city->modified): ?>
						<br><?= h($city->modified->format('Y.m.d. H:i')) ?>
					<?php endif; ?>
				</td>
				<td class="actions">
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
					<div class="record-view-row"><dt><?= __('Parent') ?></dt><dd><?= h($sample->parent->name ?? '') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Name') ?></dt><dd><?= h($sample->name) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Number') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($sample->szam, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Net') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($sample->netto, decimals: 2)) ?> HUF</dd></div>
					<div class="record-view-row"><dt><?= __('Date') ?></dt><dd><?= $sample->datum ? h($sample->datum->format('Y.m.d.')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Time') ?></dt><dd><?= $sample->ido ? h($sample->ido->format('H:i')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Date and time') ?></dt><dd><?= $sample->datumido ? h($sample->datumido->format('Y.m.d. H:i')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Boolean') ?></dt><dd><?= $sample->logikai ? __('Yes') : __('No') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Position') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($sample->pos, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Visible') ?></dt><dd><?= $sample->visible ? __('Yes') : __('No') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Cities') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($sample->city_count, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Created') ?></dt><dd><?= $sample->created ? h($sample->created->format('Y.m.d. H:i')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Modified') ?></dt><dd><?= $sample->modified ? h($sample->modified->format('Y.m.d. H:i')) : '—' ?></dd></div>
				</dl>
			</div>
			<div class="card-footer">
				<?= $this->Html->link(
					'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . __('Edit'),
					['action' => 'edit', $sample->id],
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
					'id' => 'cities',
					'title' => __('Cities'),
					'count' => $citiesCount,
					'table' => $citiesTable,
				],
			],
		]) ?>
	</div>
</div>
