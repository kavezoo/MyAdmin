<?php
/**
 * City view + related Clubs tab.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\City $city
 * @var string $countryLabel
 * @var bool $canDelete
 */
$this->Html->css(['pages/index'], ['block' => true]);
$this->Html->script(['pages/index'], ['block' => 'scriptBottom']);

$countryLabel = (string)($countryLabel ?? \App\Utility\AdminCountry::label((int)$city->country_id));
$canDelete = (bool)($canDelete ?? false);

$clubsGetUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Clubs', 'action' => 'recordGet']);
$clubsEditUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Clubs', 'action' => 'edit']);
$clubsViewUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Clubs', 'action' => 'view']);
$clubsDeleteUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Clubs', 'action' => 'delete']);

$tooltipDetails = '<b>' . __('View details') . '</b><br>' . __('View the selected record details.');
$tooltipEdit = '<b>' . __('Edit') . '</b><br>' . __('Edit the selected record.');
$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.');
$tooltipDeleteBlocked = '<b>' . __('Delete') . '</b><br>' . __('Cannot delete this record because it has related child records.');

$config = [
	'rowDoubleClickAction' => 'modal',
	'entityFieldLabels' => [
		'club' => [
			'id' => __('ID'),
			'name' => __('Name'),
			'short_name' => __('Short name'),
			'country' => __('Country'),
			'city' => __('City'),
			'address' => __('Address'),
			'email' => __('Email'),
			'phone' => __('Phone'),
			'web' => __('Web'),
			'enabled' => __('Enabled'),
			'visible' => __('Visible'),
			'pos' => __('Position'),
			'user_count' => __('Members'),
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

$clubs = $city->clubs ?? [];
$clubsList = is_array($clubs) ? $clubs : iterator_to_array($clubs);
$clubsCount = count($clubsList);

ob_start();
if ($clubsCount > 0):
?>
<table
	class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table related-records-table"
	data-get-url="<?= h($clubsGetUrl) ?>"
	data-edit-url="<?= h($clubsEditUrl) ?>"
	data-view-url="<?= h($clubsViewUrl) ?>"
	data-delete-url="<?= h($clubsDeleteUrl) ?>"
	data-delete-form-prefix="club"
	data-labels="club"
	data-title="<?= h(__('Club details')) ?>"
>
	<thead>
		<tr>
			<th scope="col" class="string name"><?= __('Name') ?></th>
			<th scope="col" class="string short_name"><?= __('Short name') ?></th>
			<th scope="col" class="number user_count"><?= __('Members') ?></th>
			<th scope="col" class="actions"><?= __('Actions') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($clubsList as $club):
			$clubCanDelete = (int)($club->user_count ?? 0) === 0;
			?>
			<tr id="related-club-<?= (int)$club->id ?>" data-id="<?= (int)$club->id ?>" data-can-delete="<?= $clubCanDelete ? '1' : '0' ?>">
				<td class="string name">
					<a href="#"
						class="record-modal-link fw-bold"
						data-id="<?= (int)$club->id ?>"
						data-labels="club"
						data-title="<?= h(__('Club details')) ?>"
					><?= h((string)$club->name) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>
				</td>
				<td class="string short_name"><?= h((string)($club->short_name ?? '')) ?: '—' ?></td>
				<td class="number user_count"><?= h(\App\Utility\LocaleNumberParser::formatCount((int)($club->user_count ?? 0), decimals: 0)) ?></td>
				<td class="actions">
					<?= $this->Html->link(
						'<i class="fa fa-eye"></i>',
						['prefix' => 'Admin', 'controller' => 'Clubs', 'action' => 'view', $club->id],
						['escape' => false, 'class' => 'btn btn-sm btn-outline-info', 'title' => $tooltipDetails, 'data-bs-toggle' => 'tooltip', 'data-bs-html' => 'true']
					) ?>
					<?= $this->Html->link(
						'<i class="fa fa-pencil"></i>',
						['prefix' => 'Admin', 'controller' => 'Clubs', 'action' => 'edit', $club->id],
						['escape' => false, 'class' => 'btn btn-sm btn-outline-primary', 'title' => $tooltipEdit, 'data-bs-toggle' => 'tooltip', 'data-bs-html' => 'true']
					) ?>
					<?php if ($clubCanDelete): ?>
						<a role="button" href="#" class="btn btn-sm btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-html="true" title="<?= h($tooltipDelete) ?>" data-id="<?= (int)$club->id ?>">
							<i class="fa fa-trash"></i>
						</a>
						<?= $this->Form->create(null, [
							'url' => ['prefix' => 'Admin', 'controller' => 'Clubs', 'action' => 'delete', $club->id],
							'id' => 'delete-form-club-' . $club->id,
							'class' => 'd-none js-row-delete-form',
						]) ?>
						<?= $this->Form->end() ?>
					<?php else: ?>
						<span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-html="true" title="<?= h($tooltipDeleteBlocked) ?>">
							<a role="button" href="#" class="btn btn-sm btn-secondary disabled" tabindex="-1" aria-disabled="true"><i class="fa fa-trash"></i></a>
						</span>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php
endif;
$clubsTable = (string)ob_get_clean();
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-building"></i> <?= __('City details') ?></h3>
					<?= h((string)$city->name) ?>
					<?php if (!empty($city->zip)): ?>
						— <code><?= h((string)$city->zip) ?></code>
					<?php endif; ?>
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
					<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h((string)$city->id) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Country') ?></dt><dd><?= h($countryLabel) ?></dd></div>
					<div class="record-view-row"><dt><?= __('County') ?></dt><dd><?= h($city->county !== null ? (string)$city->county->name : '—') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Name') ?></dt><dd><?= h((string)$city->name) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Short name') ?></dt><dd><?= h((string)$city->shortname) ?: '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('ZIP') ?></dt><dd><?= h((string)($city->zip ?? '')) ?: '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Latitude') ?></dt><dd><?= h((string)$city->lat) ?: '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Longitude') ?></dt><dd><?= h((string)$city->lng) ?: '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Latitude (import)') ?></dt><dd><?= h((string)$city->lat2) ?: '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Longitude (import)') ?></dt><dd><?= h((string)$city->lng2) ?: '—' ?></dd></div>
				</dl>
			</div>
			<div class="card-footer">
				<div class="record-view-footer-actions">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . __('Edit'),
						['action' => 'edit', $city->id],
						['escape' => false, 'class' => 'btn btn-primary']
					) ?>
				</div>
			</div>
		</div>

		<?= $this->element('admin/view_related_tabs', [
			'relatedTabs' => [
				[
					'id' => 'clubs',
					'title' => __('Clubs'),
					'count' => $clubsCount,
					'table' => $clubsTable,
				],
			],
		]) ?>
	</div>
</div>

<?= $this->element('admin/modal_linked_record_view') ?>
