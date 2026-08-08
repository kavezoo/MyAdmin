<?php
/**
 * Admin club view — main fields + related Users tab.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Club $club
 * @var string $countryLabel
 */
use App\Auth\AppRoles;
use App\Auth\MembershipProfile;

$this->Html->css(['pages/index'], ['block' => true]);
$this->Html->script(['pages/index'], ['block' => 'scriptBottom']);

$countryLabel = (string)($countryLabel ?? \App\Utility\AdminCountry::label((int)$club->country_id));

$usersGetUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'recordGet']);
$usersEditUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'edit']);
$usersViewUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'view']);
$usersDeleteUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'delete']);

$competitionsGetUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Competitions', 'action' => 'recordGet']);
$competitionsEditUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Competitions', 'action' => 'edit']);
$competitionsViewUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Competitions', 'action' => 'view']);
$competitionsDeleteUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Competitions', 'action' => 'delete']);

$tooltipDetails = '<b>' . __('View details') . '</b><br>' . __('View the selected record details.');
$tooltipEdit = '<b>' . __('Edit') . '</b><br>' . __('Edit the selected record.');
$tooltipDeleteBlocked = '<b>' . __('Delete') . '</b><br>' . __('Cannot delete this record because it has related child records.');

$userLabels = [
	'id' => __('ID'),
	'first_name' => __('Name'),
	'email' => __('Email'),
	'phone' => __('Phone'),
	'role' => __('Role'),
	'country' => __('Country'),
	'club' => __('Club'),
	'active' => __('Active'),
	'enabled' => __('Enabled'),
	'created' => __('Created'),
	'modified' => __('Modified'),
];

$competitionLabels = [
	'id' => __('ID'),
	'name' => __('Name'),
	'title' => __('Title'),
	'country' => __('Country'),
	'club' => __('Club'),
	'competition_datetime' => __('Competition datetime'),
	'visible' => __('Visible'),
	'created' => __('Created'),
	'modified' => __('Modified'),
];

$config = [
	'rowDoubleClickAction' => 'modal',
	'entityFieldLabels' => [
		'user' => $userLabels,
		'competition' => $competitionLabels,
	],
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);

$users = $club->users ?? [];
$usersList = is_array($users) ? $users : iterator_to_array($users);
$usersCount = count($usersList);
$userCountCached = (int)($club->user_count ?? $usersCount);

$competitions = $club->competitions ?? [];
$competitionsList = is_array($competitions) ? $competitions : iterator_to_array($competitions);
$competitionsCount = count($competitionsList);
$competitionCountCached = (int)($club->competition_count ?? $competitionsCount);

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
	data-title="<?= h(__('Member details')) ?>"
>
	<thead>
		<tr>
			<th scope="col" class="string name"><?= __('Name') ?></th>
			<th scope="col" class="string email"><?= __('Email') ?></th>
			<th scope="col" class="string role"><?= __('Role') ?></th>
			<th scope="col" class="boolean active"><?= __('Active') ?></th>
			<th scope="col" class="boolean enabled"><?= __('Enabled') ?></th>
			<th scope="col" class="datetime created modified"><?= __('Created') ?><br><?= __('Modified') ?></th>
			<th scope="col" class="actions"><?= __('Actions') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($usersList as $user):
			$userId = (string)$user->id;
			$name = MembershipProfile::displayName($user);
			if ($name === '') {
				$name = (string)($user->email ?? '');
			}
			$roleKey = strtolower(trim((string)($user->role ?? '')));
			$roleLabel = $roleKey !== '' ? AppRoles::label($roleKey) : '—';
			?>
			<tr id="related-user-<?= h($userId) ?>" data-id="<?= h($userId) ?>" data-can-delete="0">
				<td class="string name">
					<a href="#"
						class="record-modal-link fw-bold"
						data-id="<?= h($userId) ?>"
						data-labels="user"
						data-title="<?= h(__('Member details')) ?>"
					><?= h($name) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>
				</td>
				<td class="string email"><?= h((string)$user->email) ?></td>
				<td class="string role"><?= h($roleLabel) ?></td>
				<td class="boolean active text-center">
					<?= !empty($user->active)
						? '<i class="fa fa-check text-success"></i>'
						: '<i class="fa fa-times text-danger"></i>' ?>
				</td>
				<td class="boolean enabled text-center">
					<?= (int)($user->enabled ?? 0) === 1
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
						['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'edit', $userId],
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
					<span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDeleteBlocked) ?>">
						<a role="button" href="#" class="btn btn-secondary disabled" tabindex="-1" aria-disabled="true">
							<i class="fa fa-trash"></i>
						</a>
					</span>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php
endif;
$usersTable = (string)ob_get_clean();

ob_start();
if ($competitionsCount > 0):
?>
<table
	class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table related-records-table"
	data-get-url="<?= h($competitionsGetUrl) ?>"
	data-edit-url="<?= h($competitionsEditUrl) ?>"
	data-view-url="<?= h($competitionsViewUrl) ?>"
	data-delete-url="<?= h($competitionsDeleteUrl) ?>"
	data-delete-form-prefix="competition"
	data-labels="competition"
	data-title="<?= h(__('Competition details')) ?>"
>
	<thead>
		<tr>
			<th scope="col" class="string name"><?= __('Name') ?></th>
			<th scope="col" class="datetime competition"><?= __('Competition datetime') ?></th>
			<th scope="col" class="boolean visible"><?= __('Visible') ?></th>
			<th scope="col" class="actions"><?= __('Actions') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($competitionsList as $competition):
			$competitionId = (string)$competition->id;
			?>
			<tr id="related-competition-<?= h($competitionId) ?>" data-id="<?= h($competitionId) ?>" data-can-delete="0">
				<td class="string name">
					<a href="#"
						class="record-modal-link fw-bold"
						data-id="<?= h($competitionId) ?>"
						data-labels="competition"
						data-title="<?= h(__('Competition details')) ?>"
					><?= h((string)$competition->name) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>
				</td>
				<td class="datetime competition">
					<?= $competition->competition_datetime
						? h(\App\Utility\LocaleDateParser::format($competition->competition_datetime, 'datetime_short'))
						: '—' ?>
				</td>
				<td class="boolean visible text-center">
					<?= !empty($competition->visible)
						? '<i class="fa fa-check text-success"></i>'
						: '<i class="fa fa-times text-danger"></i>' ?>
				</td>
				<td class="actions">
					<?= $this->Html->link(
						'<i class="fa fa-eye"></i>',
						['prefix' => 'Admin', 'controller' => 'Competitions', 'action' => 'view', $competitionId],
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
						['prefix' => 'Admin', 'controller' => 'Competitions', 'action' => 'edit', $competitionId],
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
					<span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDeleteBlocked) ?>">
						<a role="button" href="#" class="btn btn-secondary disabled" tabindex="-1" aria-disabled="true">
							<i class="fa fa-trash"></i>
						</a>
					</span>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php
endif;
$competitionsTable = (string)ob_get_clean();
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-eye"></i> <?= __('Club details') ?></h3>
					<?= __('View the selected record (read-only).') ?>
					<?php if ($countryLabel !== ''): ?>
						<div class="text-muted small"><?= h(__('Country: {0}', $countryLabel)) ?></div>
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
				<dl class="record-view-fields mb-0">
					<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h($club->id) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Name') ?></dt><dd><?= h($club->name) ?></dd></div>
					<?php if (trim((string)($club->short_name ?? '')) !== ''): ?>
						<div class="record-view-row"><dt><?= __('Short name') ?></dt><dd><?= h($club->short_name) ?></dd></div>
					<?php endif; ?>
					<div class="record-view-row"><dt><?= __('Country') ?></dt><dd><?= h($countryLabel) ?></dd></div>
					<div class="record-view-row"><dt><?= __('City') ?></dt><dd><?= h($club->city !== null ? (string)$club->city->name : '') ?: '—' ?></dd></div>
					<?php if (trim((string)($club->address ?? '')) !== ''): ?>
						<div class="record-view-row"><dt><?= __('Address') ?></dt><dd><?= h($club->address) ?></dd></div>
					<?php endif; ?>
					<?php if (trim((string)($club->email ?? '')) !== ''): ?>
						<div class="record-view-row"><dt><?= __('Email') ?></dt><dd><?= h($club->email) ?></dd></div>
					<?php endif; ?>
					<?php if (trim((string)($club->phone ?? '')) !== ''): ?>
						<div class="record-view-row"><dt><?= __('Phone') ?></dt><dd><?= h($club->phone) ?></dd></div>
					<?php endif; ?>
					<?php if (trim((string)($club->web ?? '')) !== ''): ?>
						<div class="record-view-row"><dt><?= __('Website') ?></dt><dd><a href="<?= h($club->web) ?>" target="_blank" rel="noopener"><?= h($club->web) ?></a></dd></div>
					<?php endif; ?>
					<?php if (trim((string)($club->facebook ?? '')) !== ''): ?>
						<div class="record-view-row"><dt><?= __('Facebook') ?></dt><dd><a href="<?= h($club->facebook) ?>" target="_blank" rel="noopener"><?= h($club->facebook) ?></a></dd></div>
					<?php endif; ?>
					<?php if (trim((string)($club->insta ?? '')) !== ''): ?>
						<div class="record-view-row"><dt><?= __('Instagram') ?></dt><dd><a href="<?= h($club->insta) ?>" target="_blank" rel="noopener"><?= h($club->insta) ?></a></dd></div>
					<?php endif; ?>
					<div class="record-view-row"><dt><?= __('Enabled') ?></dt><dd><?= $club->enabled ? __('Yes') : __('No') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Visible') ?></dt><dd><?= $club->visible ? __('Yes') : __('No') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Position') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($club->pos, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Members') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::formatCount($userCountCached, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Competitions') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::formatCount($competitionCountCached, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Created') ?></dt><dd><?= $club->created ? h(\App\Utility\LocaleDateParser::format($club->created, 'datetime_short')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Modified') ?></dt><dd><?= $club->modified ? h(\App\Utility\LocaleDateParser::format($club->modified, 'datetime_short')) : '—' ?></dd></div>
				</dl>
			</div>
			<div class="card-footer">
				<div class="record-view-footer-actions">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . __('Edit'),
						['action' => 'edit', $club->id],
						['escape' => false, 'class' => 'btn btn-primary']
					) ?>
				</div>
			</div>
		</div>

		<?= $this->element('admin/view_related_tabs', [
			'relatedTabs' => [
				[
					'id' => 'users',
					'title' => __('Members'),
					'count' => $usersCount,
					'table' => $usersTable,
				],
				[
					'id' => 'competitions',
					'title' => __('Competitions'),
					'count' => $competitionsCount,
					'table' => $competitionsTable,
				],
			],
		]) ?>
	</div>
</div>

<?= $this->element('admin/modal_linked_record_view') ?>
