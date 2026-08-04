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

$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.');
$tooltipDeleteBlocked = '<b>' . __('Delete') . '</b><br>' . __('Cannot delete this record because it has related child records.');

$canDeleteSetup = (bool)$this->get('canDeleteSetup', false);

$config = [
	'rowDoubleClickAction' => $rowDoubleClickAction,
	'entityFieldLabels' => [
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
$typeLabels = SetupValue::typeOptions();

ob_start();
if ($usersCount > 0):
?>
<table class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table">
	<thead>
		<tr>
			<th scope="col" class="string email"><?= __('Email') ?></th>
			<th scope="col" class="string name"><?= __('Name') ?></th>
			<th scope="col" class="string role"><?= __('Role') ?></th>
			<th scope="col" class="boolean active"><?= __('Active') ?></th>
			<th scope="col" class="datetime created modified"><?= __('Created') ?><br><?= __('Modified') ?></th>
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
			?>
			<tr id="related-user-<?= h((string)$user->id) ?>" data-id="<?= h((string)$user->id) ?>">
				<td class="string email"><?= h((string)$user->email) ?></td>
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
					<div class="record-view-row"><dt><?= __('Primary locale') ?></dt><dd><code><?= h($country->locale) ?></code></dd></div>
					<div class="record-view-row"><dt><?= __('Continent') ?></dt><dd><?= h($country->continent->name ?? '') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Visible') ?></dt><dd><?= $country->visible ? __('Yes') : __('No') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Position') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($country->pos, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Users') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::formatCount($country->user_count, decimals: 0)) ?></dd></div>
					<?php if ($usersCount > 0): ?>
						<div class="record-view-row">
							<dt><?= __('User list') ?></dt>
							<dd class="record-related-list">
								<?php
								$userParts = [];
								foreach ($users as $user) {
									$label = trim((string)($user->first_name ?? '') . ' ' . (string)($user->last_name ?? ''));
									if ($label === '') {
										$label = (string)($user->email ?? $user->username ?? '');
									} else {
										$label .= ' (' . (string)$user->email . ')';
									}
									$userParts[] = h($label);
								}
								echo implode(', ', $userParts);
								?>
							</dd>
						</div>
					<?php endif; ?>
					<?php if ($setupsCount > 0): ?>
						<div class="record-view-row">
							<dt><?= __('Setup list') ?></dt>
							<dd class="record-related-list">
								<?php
								$setupLinks = [];
								foreach ($setups as $setup) {
									$setupLinks[] = '<a href="#" class="record-modal-link"'
										. ' data-id="' . (int)$setup->id . '"'
										. ' data-get-url="' . h($setupsGetUrl) . '"'
										. ' data-edit-url="' . h($setupsEditUrl) . '"'
										. ' data-view-url="' . h($setupsViewUrl) . '"'
										. ' data-delete-url="' . h($setupsDeleteUrl) . '"'
										. ' data-delete-form-prefix="setup"'
										. ' data-labels="setup"'
										. ' data-title="' . h(__('Setup details')) . '"'
										. '>' . h($setup->name)
										. '<span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>';
								}
								echo implode(', ', $setupLinks);
								?>
							</dd>
						</div>
					<?php endif; ?>
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
			],
		]) ?>
	</div>
</div>

<?= $this->element('admin/modal_linked_record_view') ?>
