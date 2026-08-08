<?php
/**
 * County view + related Cities tab.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\County $county
 * @var string $countryLabel
 * @var bool $canDelete
 */
$this->Html->css(['pages/index'], ['block' => true]);
$this->Html->script(['pages/index'], ['block' => 'scriptBottom']);

$countryLabel = (string)($countryLabel ?? \App\Utility\AdminCountry::label((int)$county->country_id));
$canDelete = (bool)($canDelete ?? false);

$citiesGetUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Cities', 'action' => 'recordGet']);
$citiesEditUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Cities', 'action' => 'edit']);
$citiesViewUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Cities', 'action' => 'view']);
$citiesDeleteUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Cities', 'action' => 'delete']);

$tooltipDetails = '<b>' . __('View details') . '</b><br>' . __('View the selected record details.');
$tooltipEdit = '<b>' . __('Edit') . '</b><br>' . __('Edit the selected record.');
$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.');

$config = [
	'rowDoubleClickAction' => 'modal',
	'entityFieldLabels' => [
		'city' => [
			'id' => __('ID'),
			'name' => __('Name'),
			'shortname' => __('Short name'),
			'zip' => __('ZIP'),
			'country' => __('Country'),
			'county' => __('County'),
		],
	],
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);

$cities = $county->cities ?? [];
$citiesList = is_array($cities) ? $cities : iterator_to_array($cities);
$citiesCount = count($citiesList);

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
			<th scope="col" class="string name"><?= __('Name') ?></th>
			<th scope="col" class="string zip"><?= __('ZIP') ?></th>
			<th scope="col" class="actions"><?= __('Actions') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($citiesList as $city): ?>
			<tr id="related-city-<?= (int)$city->id ?>" data-id="<?= (int)$city->id ?>" data-can-delete="1">
				<td class="string name">
					<a href="#"
						class="record-modal-link fw-bold"
						data-id="<?= (int)$city->id ?>"
						data-labels="city"
						data-title="<?= h(__('City details')) ?>"
					><?= h((string)$city->name) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>
				</td>
				<td class="string zip"><?= h((string)($city->zip ?? '')) ?: '—' ?></td>
				<td class="actions">
					<?= $this->Html->link(
						'<i class="fa fa-eye"></i>',
						['prefix' => 'Admin', 'controller' => 'Cities', 'action' => 'view', $city->id],
						['escape' => false, 'class' => 'btn btn-sm btn-outline-info', 'title' => $tooltipDetails, 'data-bs-toggle' => 'tooltip', 'data-bs-html' => 'true']
					) ?>
					<?= $this->Html->link(
						'<i class="fa fa-pencil"></i>',
						['prefix' => 'Admin', 'controller' => 'Cities', 'action' => 'edit', $city->id],
						['escape' => false, 'class' => 'btn btn-sm btn-outline-primary', 'title' => $tooltipEdit, 'data-bs-toggle' => 'tooltip', 'data-bs-html' => 'true']
					) ?>
					<a role="button" href="#" class="btn btn-sm btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-html="true" title="<?= h($tooltipDelete) ?>" data-id="<?= (int)$city->id ?>">
						<i class="fa fa-trash"></i>
					</a>
					<?= $this->Form->create(null, [
						'url' => ['prefix' => 'Admin', 'controller' => 'Cities', 'action' => 'delete', $city->id],
						'id' => 'delete-form-city-' . $city->id,
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
$citiesTable = (string)ob_get_clean();
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-map"></i> <?= __('County details') ?></h3>
					<?= h((string)$county->name) ?>
				</div>
				<div class="float-right">
					<a role="button" href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<dl class="row record-view-fields mb-0">
					<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h((string)$county->id) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Country') ?></dt><dd><?= h($countryLabel) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Name') ?></dt><dd><?= h((string)$county->name) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Short name') ?></dt><dd><?= h((string)$county->shortname) ?: '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Capital city') ?></dt><dd><?= h((string)$county->capitalcity) ?: '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Region') ?></dt><dd><?= h((string)$county->region) ?: '—' ?></dd></div>
					<div class="record-view-row">
						<dt><?= __('Visible') ?></dt>
						<dd>
							<?= !empty($county->visible)
								? '<i class="fa fa-check text-success"></i> ' . h(__('Yes'))
								: '<i class="fa fa-times text-danger"></i> ' . h(__('No')) ?>
						</dd>
					</div>
					<div class="record-view-row"><dt><?= __('Position') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($county->pos, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Created') ?></dt><dd><?= $county->created ? h(\App\Utility\LocaleDateParser::format($county->created, 'datetime_short')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Modified') ?></dt><dd><?= $county->modified ? h(\App\Utility\LocaleDateParser::format($county->modified, 'datetime_short')) : '—' ?></dd></div>
				</dl>
			</div>
			<div class="card-footer">
				<div class="record-view-footer-actions">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . __('Edit'),
						['action' => 'edit', $county->id],
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
