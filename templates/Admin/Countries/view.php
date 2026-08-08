<?php
/**
 * Country view — main fields + related Users / Setups tabs.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Country $country
 * @var bool $canDeleteSetup
 */
use App\Auth\AppRoles;
use App\Utility\SetupValue;

$this->Html->css(['pages/index'], ['block' => true]);

$rowDoubleClickAction = 'modal';

$setupsGetUrl = $this->Url->build(['controller' => 'Setups', 'action' => 'recordGet']);
$setupsEditUrl = $this->Url->build(['controller' => 'Setups', 'action' => 'edit']);
$setupsViewUrl = $this->Url->build(['controller' => 'Setups', 'action' => 'view']);
$setupsDeleteUrl = $this->Url->build(['controller' => 'Setups', 'action' => 'delete']);

$usersGetUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'recordGet']);
$usersEditUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'edit']);
$usersViewUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'view']);
$usersDeleteUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'delete']);

$countiesGetUrl = $this->Url->build(['controller' => 'Counties', 'action' => 'recordGet']);
$countiesEditUrl = $this->Url->build(['controller' => 'Counties', 'action' => 'edit']);
$countiesViewUrl = $this->Url->build(['controller' => 'Counties', 'action' => 'view']);
$countiesDeleteUrl = $this->Url->build(['controller' => 'Counties', 'action' => 'delete']);

$citiesGetUrl = $this->Url->build(['controller' => 'Cities', 'action' => 'recordGet']);
$citiesEditUrl = $this->Url->build(['controller' => 'Cities', 'action' => 'edit']);
$citiesViewUrl = $this->Url->build(['controller' => 'Cities', 'action' => 'view']);
$citiesDeleteUrl = $this->Url->build(['controller' => 'Cities', 'action' => 'delete']);

$clubsGetUrl = $this->Url->build(['controller' => 'Clubs', 'action' => 'recordGet']);
$clubsEditUrl = $this->Url->build(['controller' => 'Clubs', 'action' => 'edit']);
$clubsViewUrl = $this->Url->build(['controller' => 'Clubs', 'action' => 'view']);
$clubsDeleteUrl = $this->Url->build(['controller' => 'Clubs', 'action' => 'delete']);

$tooltipDetails = '<b>' . __('View details') . '</b><br>' . __('View the selected record details.');
$tooltipEdit = '<b>' . __('Edit') . '</b><br>' . __('Edit the selected record.');
$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.');
$tooltipDeleteBlocked = '<b>' . __('Delete') . '</b><br>' . __('Cannot delete this record because it has related child records.');

$canDeleteSetup = (bool)$this->get('canDeleteSetup', false);

$config = [
	'rowDoubleClickAction' => $rowDoubleClickAction,
	'entityFieldLabels' => [
		'user' => [
			'id' => __('ID'),
			'email' => __('Email'),
			'first_name' => __('Name'),
			'role' => __('Role'),
			'active' => __('Active'),
			'enabled' => __('Enabled'),
			'created' => __('Created'),
			'modified' => __('Modified'),
		],
		'setup' => [
			'id' => __('ID'),
			'name' => __('Name'),
			'slug' => __('Slug'),
			'type' => __('Type'),
			'edit_by' => __('Edit by'),
			'value' => __('Value'),
			'pos' => __('Position'),
			'visible' => __('Visible'),
			'created' => __('Created'),
			'modified' => __('Modified'),
		],
		'county' => [
			'id' => __('ID'),
			'name' => __('Name'),
			'shortname' => __('Short name'),
			'country' => __('Country'),
			'visible' => __('Visible'),
			'pos' => __('Position'),
		],
		'city' => [
			'id' => __('ID'),
			'name' => __('Name'),
			'zip' => __('ZIP'),
			'county' => __('County'),
			'country' => __('Country'),
		],
		'club' => [
			'id' => __('ID'),
			'name' => __('Name'),
			'short_name' => __('Short name'),
			'city' => __('City'),
			'user_count' => __('Members'),
			'enabled' => __('Enabled'),
			'visible' => __('Visible'),
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

$users = $country->users ?? [];
$usersCount = is_countable($users) ? count($users) : 0;
$setups = $country->setups ?? [];
$setupsCount = is_countable($setups) ? count($setups) : 0;
$counties = $country->counties ?? [];
$countiesCount = is_countable($counties) ? count($counties) : 0;
$cities = $country->cities ?? [];
$citiesCount = is_countable($cities) ? count($cities) : 0;
$clubs = $country->clubs ?? [];
$clubsCount = is_countable($clubs) ? count($clubs) : 0;
$typeLabels = SetupValue::typeOptions();

ob_start();
if ($usersCount > 0):
?>
<table
	class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table related-records-table"
	data-get-url="<?= h($usersGetUrl) ?>"
	data-edit-url="<?= h($usersEditUrl) ?>"
	data-view-url="<?= h($usersViewUrl) ?>"
	data-delete-url="<?= h($usersDeleteUrl) ?>"
	data-delete-form-prefix="user"
	data-labels="user"
	data-title="<?= h(__('User details')) ?>"
>
	<thead>
		<tr>
			<th scope="col" class="string email"><?= __('Email') ?></th>
			<th scope="col" class="string name"><?= __('Name') ?></th>
			<th scope="col" class="string role"><?= __('Role') ?></th>
			<th scope="col" class="boolean active"><?= __('Active') ?></th>
			<th scope="col" class="datetime created modified"><?= __('Created') ?><br><?= __('Modified') ?></th>
			<th scope="col" class="actions"><?= __('Actions') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($users as $user): ?>
			<?php
			$userName = trim((string)($user->first_name ?? '') . ' ' . (string)($user->last_name ?? ''));
			if ($userName === '') {
				$userName = (string)($user->username ?? '');
			}
			$roleKey = strtolower(trim((string)($user->role ?? '')));
			$roleLabel = $roleKey !== '' ? AppRoles::labeled($roleKey) : '—';
			$userId = (string)$user->id;
			?>
			<tr id="related-user-<?= h($userId) ?>" data-id="<?= h($userId) ?>" data-can-delete="0">
				<td class="string email">
					<a href="#"
						class="record-modal-link fw-bold"
						data-id="<?= h($userId) ?>"
						data-labels="user"
						data-title="<?= h(__('User details')) ?>"
					><?= h((string)$user->email) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>
				</td>
				<td class="string name"><?= h($userName !== '' ? $userName : '—') ?></td>
				<td class="string role"><?= h($roleLabel) ?></td>
				<td class="boolean active">
					<?= !empty($user->active)
						? '<i class="fa fa-check text-success"></i>'
						: '<i class="fa fa-times text-danger"></i>' ?>
				</td>
				<td class="datetime created modified">
					<?= $user->created ? h(\App\Utility\LocaleDateParser::format($user->created, 'datetime_short')) : '' ?>
					<?php if (!empty($user->modified)): ?>
						<br><?= h(\App\Utility\LocaleDateParser::format($user->modified, 'datetime_short')) ?>
					<?php endif; ?>
				</td>
				<td class="actions">
					<?= $this->Html->link(
						'<i class="fa fa-eye"></i>',
						['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'view', $userId],
						['escape' => false, 'class' => 'btn btn-sm btn-outline-info', 'title' => $tooltipDetails, 'data-bs-toggle' => 'tooltip', 'data-bs-html' => 'true']
					) ?>
					<?= $this->Html->link(
						'<i class="fa fa-pencil"></i>',
						['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'edit', $userId],
						['escape' => false, 'class' => 'btn btn-sm btn-outline-primary', 'title' => $tooltipEdit, 'data-bs-toggle' => 'tooltip', 'data-bs-html' => 'true']
					) ?>
					<span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-html="true" title="<?= h($tooltipDeleteBlocked) ?>">
						<a role="button" href="#" class="btn btn-sm btn-secondary disabled" tabindex="-1" aria-disabled="true"><i class="fa fa-trash"></i></a>
					</span>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php
endif;
$usersTable = ob_get_clean();

ob_start();
if ($setupsCount > 0):
?>
<table
	class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table related-records-table"
	data-get-url="<?= h($setupsGetUrl) ?>"
	data-edit-url="<?= h($setupsEditUrl) ?>"
	data-view-url="<?= h($setupsViewUrl) ?>"
	data-delete-url="<?= h($setupsDeleteUrl) ?>"
	data-delete-form-prefix="setup"
	data-labels="setup"
	data-title="<?= h(__('Setup details')) ?>"
>
	<thead>
		<tr>
			<th scope="col" class="number id">#</th>
			<th scope="col" class="string name"><?= __('Name') ?></th>
			<th scope="col" class="string slug"><?= __('Slug') ?></th>
			<th scope="col" class="string type"><?= __('Type') ?></th>
			<th scope="col" class="boolean visible"><?= __('Visible') ?></th>
			<th scope="col" class="datetime created modified"><?= __('Created') ?><br><?= __('Modified') ?></th>
			<th scope="col" class="actions"><?= __('Actions') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($setups as $setup): ?>
			<?php
			$type = (string)$setup->type;
			$typeLabel = $typeLabels[$type] ?? $type;
			?>
			<tr id="related-setup-<?= (int)$setup->id ?>" data-id="<?= (int)$setup->id ?>" data-can-delete="<?= $canDeleteSetup ? '1' : '0' ?>">
				<td class="number id"><?= h($setup->id) ?></td>
				<td class="string name">
					<a href="#"
						class="record-modal-link"
						data-id="<?= (int)$setup->id ?>"
						data-labels="setup"
						data-title="<?= h(__('Setup details')) ?>"
					><?= h($setup->name) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>
				</td>
				<td class="string slug"><code><?= h($setup->slug) ?></code></td>
				<td class="string type"><?= h($typeLabel) ?></td>
				<td class="boolean visible">
					<?= $setup->visible
						? '<i class="fa fa-check text-success"></i>'
						: '<i class="fa fa-times text-danger"></i>' ?>
				</td>
				<td class="datetime created modified">
					<?= $setup->created ? h(\App\Utility\LocaleDateParser::format($setup->created, 'datetime_short')) : '' ?>
					<?php if ($setup->modified): ?>
						<br><?= h(\App\Utility\LocaleDateParser::format($setup->modified, 'datetime_short')) ?>
					<?php endif; ?>
				</td>
				<td class="actions">
					<?= $this->Html->link(
						'<i class="fa fa-eye"></i>',
						['controller' => 'Setups', 'action' => 'view', $setup->id],
						['escape' => false, 'class' => 'btn btn-outline-info', 'role' => 'button', 'title' => __('View details')]
					) ?>
					<?= $this->Html->link(
						'<i class="fa fa-pencil"></i>',
						['controller' => 'Setups', 'action' => 'edit', $setup->id],
						['escape' => false, 'class' => 'btn btn-outline-primary', 'role' => 'button', 'title' => __('Edit')]
					) ?>
					<?php if ($canDeleteSetup): ?>
						<a role="button" href="#" class="btn btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDelete) ?>" data-id="<?= (int)$setup->id ?>">
							<i class="fa fa-trash"></i>
						</a>
						<?= $this->Form->create(null, [
							'url' => ['controller' => 'Setups', 'action' => 'delete', $setup->id],
							'id' => 'delete-form-setup-' . $setup->id,
							'class' => 'd-none js-row-delete-form',
						]) ?>
						<?= $this->Form->end() ?>
					<?php else: ?>
						<span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDeleteBlocked) ?>">
							<a role="button" href="#" class="btn btn-secondary disabled" tabindex="-1" aria-disabled="true">
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
$setupsTable = ob_get_clean();

ob_start();
if ($countiesCount > 0):
?>
<table
	class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table related-records-table"
	data-get-url="<?= h($countiesGetUrl) ?>"
	data-edit-url="<?= h($countiesEditUrl) ?>"
	data-view-url="<?= h($countiesViewUrl) ?>"
	data-delete-url="<?= h($countiesDeleteUrl) ?>"
	data-delete-form-prefix="county"
	data-labels="county"
	data-title="<?= h(__('County details')) ?>"
>
	<thead>
		<tr>
			<th scope="col" class="string name"><?= __('Name') ?></th>
			<th scope="col" class="string shortname"><?= __('Short name') ?></th>
			<th scope="col" class="actions"><?= __('Actions') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($counties as $county): ?>
			<tr id="related-county-<?= (int)$county->id ?>" data-id="<?= (int)$county->id ?>" data-can-delete="1">
				<td class="string name">
					<a href="#" class="record-modal-link fw-bold" data-id="<?= (int)$county->id ?>" data-labels="county" data-title="<?= h(__('County details')) ?>">
						<?= h((string)$county->name) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span>
					</a>
				</td>
				<td class="string shortname"><?= h((string)($county->shortname ?? '')) ?: '—' ?></td>
				<td class="actions">
					<?= $this->Html->link('<i class="fa fa-eye"></i>', ['controller' => 'Counties', 'action' => 'view', $county->id], ['escape' => false, 'class' => 'btn btn-sm btn-outline-info', 'title' => $tooltipDetails, 'data-bs-toggle' => 'tooltip', 'data-bs-html' => 'true']) ?>
					<?= $this->Html->link('<i class="fa fa-pencil"></i>', ['controller' => 'Counties', 'action' => 'edit', $county->id], ['escape' => false, 'class' => 'btn btn-sm btn-outline-primary', 'title' => $tooltipEdit, 'data-bs-toggle' => 'tooltip', 'data-bs-html' => 'true']) ?>
					<a role="button" href="#" class="btn btn-sm btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-html="true" title="<?= h($tooltipDelete) ?>" data-id="<?= (int)$county->id ?>"><i class="fa fa-trash"></i></a>
					<?= $this->Form->create(null, ['url' => ['controller' => 'Counties', 'action' => 'delete', $county->id], 'id' => 'delete-form-county-' . $county->id, 'class' => 'd-none js-row-delete-form']) ?>
					<?= $this->Form->end() ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php
endif;
$countiesTable = ob_get_clean();

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
		<?php foreach ($cities as $city): ?>
			<tr id="related-city-<?= (int)$city->id ?>" data-id="<?= (int)$city->id ?>" data-can-delete="1">
				<td class="string name">
					<a href="#" class="record-modal-link fw-bold" data-id="<?= (int)$city->id ?>" data-labels="city" data-title="<?= h(__('City details')) ?>">
						<?= h((string)$city->name) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span>
					</a>
				</td>
				<td class="string zip"><?= h((string)($city->zip ?? '')) ?: '—' ?></td>
				<td class="actions">
					<?= $this->Html->link('<i class="fa fa-eye"></i>', ['controller' => 'Cities', 'action' => 'view', $city->id], ['escape' => false, 'class' => 'btn btn-sm btn-outline-info', 'title' => $tooltipDetails, 'data-bs-toggle' => 'tooltip', 'data-bs-html' => 'true']) ?>
					<?= $this->Html->link('<i class="fa fa-pencil"></i>', ['controller' => 'Cities', 'action' => 'edit', $city->id], ['escape' => false, 'class' => 'btn btn-sm btn-outline-primary', 'title' => $tooltipEdit, 'data-bs-toggle' => 'tooltip', 'data-bs-html' => 'true']) ?>
					<a role="button" href="#" class="btn btn-sm btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-html="true" title="<?= h($tooltipDelete) ?>" data-id="<?= (int)$city->id ?>"><i class="fa fa-trash"></i></a>
					<?= $this->Form->create(null, ['url' => ['controller' => 'Cities', 'action' => 'delete', $city->id], 'id' => 'delete-form-city-' . $city->id, 'class' => 'd-none js-row-delete-form']) ?>
					<?= $this->Form->end() ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php
endif;
$citiesTable = ob_get_clean();

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
		<?php foreach ($clubs as $club):
			$clubCanDelete = (int)($club->user_count ?? 0) === 0;
			?>
			<tr id="related-club-<?= (int)$club->id ?>" data-id="<?= (int)$club->id ?>" data-can-delete="<?= $clubCanDelete ? '1' : '0' ?>">
				<td class="string name">
					<a href="#" class="record-modal-link fw-bold" data-id="<?= (int)$club->id ?>" data-labels="club" data-title="<?= h(__('Club details')) ?>">
						<?= h((string)$club->name) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span>
					</a>
				</td>
				<td class="string short_name"><?= h((string)($club->short_name ?? '')) ?: '—' ?></td>
				<td class="number user_count"><?= h(\App\Utility\LocaleNumberParser::formatCount((int)($club->user_count ?? 0), decimals: 0)) ?></td>
				<td class="actions">
					<?= $this->Html->link('<i class="fa fa-eye"></i>', ['controller' => 'Clubs', 'action' => 'view', $club->id], ['escape' => false, 'class' => 'btn btn-sm btn-outline-info', 'title' => $tooltipDetails, 'data-bs-toggle' => 'tooltip', 'data-bs-html' => 'true']) ?>
					<?= $this->Html->link('<i class="fa fa-pencil"></i>', ['controller' => 'Clubs', 'action' => 'edit', $club->id], ['escape' => false, 'class' => 'btn btn-sm btn-outline-primary', 'title' => $tooltipEdit, 'data-bs-toggle' => 'tooltip', 'data-bs-html' => 'true']) ?>
					<?php if ($clubCanDelete): ?>
						<a role="button" href="#" class="btn btn-sm btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-html="true" title="<?= h($tooltipDelete) ?>" data-id="<?= (int)$club->id ?>"><i class="fa fa-trash"></i></a>
						<?= $this->Form->create(null, ['url' => ['controller' => 'Clubs', 'action' => 'delete', $club->id], 'id' => 'delete-form-club-' . $club->id, 'class' => 'd-none js-row-delete-form']) ?>
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
$clubsTable = ob_get_clean();
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-eye"></i> <?= __('Country details') ?></h3>
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
					<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h($country->id) ?></dd></div>
					<div class="record-view-row"><dt><?= __('ISO') ?></dt><dd><code><?= h($country->iso2) ?></code></dd></div>
					<div class="record-view-row"><dt><?= __('Name') ?></dt><dd><?= h($country->name) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Endonym') ?></dt><dd><?= h($country->endonim_name) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Primary locale') ?></dt><dd><code><?= h($country->locale) ?></code></dd></div>
					<div class="record-view-row"><dt><?= __('Timezone') ?></dt><dd><code><?= h($country->timezone) ?></code></dd></div>
					<div class="record-view-row"><dt><?= __('Phone prefix') ?></dt><dd><code><?= h((string)$country->phone_prefix) ?></code></dd></div>
					<div class="record-view-row"><dt><?= __('Continent') ?></dt><dd><?= h($country->continent->name ?? '') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Visible') ?></dt><dd><?= $country->visible ? __('Yes') : __('No') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Position') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($country->pos, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Users') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::formatCount($country->user_count, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Created') ?></dt><dd><?= $country->created ? h(\App\Utility\LocaleDateParser::format($country->created, 'datetime_short')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Modified') ?></dt><dd><?= $country->modified ? h(\App\Utility\LocaleDateParser::format($country->modified, 'datetime_short')) : '—' ?></dd></div>
				</dl>
			</div>
			<div class="card-footer">
				<div class="record-view-footer-actions">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . __('Edit'),
						['action' => 'edit', $country->id],
						['escape' => false, 'class' => 'btn btn-primary']
					) ?>
				</div>
			</div>
		</div>

		<?= $this->element('admin/view_related_tabs', [
			'relatedTabs' => [
				[
					'id' => 'users',
					'title' => __('Users'),
					'count' => $usersCount,
					'table' => $usersTable,
				],
				[
					'id' => 'setups',
					'title' => __('Setups'),
					'count' => $setupsCount,
					'table' => $setupsTable,
				],
				[
					'id' => 'counties',
					'title' => __('Counties'),
					'count' => $countiesCount,
					'table' => $countiesTable,
				],
				[
					'id' => 'cities',
					'title' => __('Cities'),
					'count' => $citiesCount,
					'table' => $citiesTable,
				],
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
