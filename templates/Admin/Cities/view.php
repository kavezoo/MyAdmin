<?php
/**
 * City view — main fields + related Samples tab.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\City $city
 */
$this->Html->css(['pages/index'], ['block' => true]);

$samples = $city->samples ?? [];
$samplesCount = is_countable($samples) ? count($samples) : 0;

ob_start();
if ($samplesCount > 0):
?>
<table class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table">
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
			<tr id="related-sample-<?= (int)$sample->id ?>" data-id="<?= (int)$sample->id ?>">
				<td class="number id"><?= h($sample->id) ?></td>
				<td class="string name"><?= h($sample->name) ?></td>
				<td class="number szam text-end"><?= h(\App\Utility\LocaleNumberParser::format($sample->szam, decimals: 0)) ?></td>
				<td class="currency netto text-end">
					<span class="currency-amount"><?= h(\App\Utility\LocaleNumberParser::format($sample->netto, decimals: 2)) ?></span> HUF
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
					<h3><i class="fa fa-eye"></i> <?= __('City details') ?></h3>
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
					<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h($city->id) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Name') ?></dt><dd><?= h($city->name) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Position') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($city->pos, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Visible') ?></dt><dd><?= $city->visible ? __('Yes') : __('No') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Samples') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($city->sample_count, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Created') ?></dt><dd><?= $city->created ? h($city->created->format('Y.m.d. H:i')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Modified') ?></dt><dd><?= $city->modified ? h($city->modified->format('Y.m.d. H:i')) : '—' ?></dd></div>
				</dl>
			</div>
			<div class="card-footer">
				<?= $this->Html->link(
					'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . __('Edit'),
					['action' => 'edit', $city->id],
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
