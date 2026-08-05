<?php
/**
 * Club view — main fields + related Users (members) tab.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Club $club
 * @var \Cake\Datasource\EntityInterface|null $president
 * @var int $countryId
 * @var string $countryLabel
 * @var int $membershipYear
 */
use App\Auth\AppRoles;
use App\Auth\MembershipProfile;
use App\Utility\MembershipFee;

$this->Html->css(['pages/index', 'pages/users_list_avatar', 'pages/membership_fee'], ['block' => true]);

$rowDoubleClickAction = 'modal';

$countryId = (int)($countryId ?? 0);
$membershipYear = (int)($membershipYear ?? MembershipFee::currentYear());
$clubEntityFeeLabel = MembershipFee::clubEntityFeeLabel($countryId);
$feeDate = $club->get(MembershipFee::FIELD_CLUB_ENTITY);
$feePaid = MembershipFee::isPaidForYear($feeDate, $membershipYear);
$feeDateFormatted = MembershipFee::paidDateFormatted($feeDate, $membershipYear);
$feeLastFormatted = MembershipFee::lastPaymentFormatted($feeDate);

$usersGetUrl = $this->Url->build(['action' => 'userRecordGet']);
$usersEditUrl = $this->Url->build(['prefix' => 'President', 'controller' => 'Members', 'action' => 'edit']);
$usersViewUrl = $this->Url->build(['prefix' => 'President', 'controller' => 'Members', 'action' => 'view']);
$usersDeleteUrl = $this->Url->build(['prefix' => 'President', 'controller' => 'Members', 'action' => 'delete']);

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
	MembershipProfile::FIELD_JOINED => __('Member since'),
	MembershipFee::FIELD_CLUB => MembershipFee::clubFeeLabel((int)($countryId ?? 0)),
	MembershipFee::FIELD_NATIONAL => MembershipFee::nationalFeeLabel((int)($countryId ?? 0)),
	'created' => __('Created'),
	'modified' => __('Modified'),
];

$config = [
	'rowDoubleClickAction' => $rowDoubleClickAction,
	'entityFieldLabels' => [
		'user' => $userLabels,
	],
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);
$this->Html->script(['pages/index'], ['block' => 'scriptBottom']);

$users = $club->users ?? [];
$usersList = is_array($users) ? $users : iterator_to_array($users);
$usersCount = count($usersList);
$userCountCached = (int)($club->user_count ?? $usersCount);
$countryLabel = (string)($countryLabel ?? '');

ob_start();
if ($usersCount > 0):
?>
<table
	class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table related-records-table"
	data-get-url="<?= h($usersGetUrl) ?>"
	data-edit-url="<?= h($usersEditUrl) ?>"
	data-view-url="<?= h($usersViewUrl) ?>"
	data-delete-url="<?= h($usersDeleteUrl) ?>"
	data-delete-form-prefix="member"
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
				<td class="string name users-list-name-cell">
					<a href="#"
						class="record-modal-link fw-bold"
						data-id="<?= h($userId) ?>"
						data-labels="user"
						data-title="<?= h(__('Member details')) ?>"
					><?= h($name) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>
					<?php if ($roleKey !== ''): ?>
						<div class="users-list-name-cell__role"><?= h($roleLabel) ?></div>
					<?php endif; ?>
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
						['prefix' => 'President', 'controller' => 'Members', 'action' => 'view', $userId],
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
						['prefix' => 'President', 'controller' => 'Members', 'action' => 'edit', $userId],
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
$usersTable = ob_get_clean();
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
				<div class="membership-fee-panel mb-4">
					<div class="membership-fee-panel__title"><?= __('Membership fees ({0})', $membershipYear) ?></div>
					<?= $this->element('users/membership_fee_status', [
						'label' => $clubEntityFeeLabel,
						'paid' => $feePaid,
						'membershipYear' => $membershipYear,
						'dateFormatted' => $feeDateFormatted,
						'lastPaymentDateFormatted' => $feeLastFormatted,
						'mode' => 'profile_club',
					]) ?>
				</div>

				<dl class="record-view-fields mb-0">
					<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h($club->id) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Name') ?></dt><dd><?= h($club->name) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Country') ?></dt><dd><?= h($countryLabel) ?></dd></div>
					<div class="record-view-row">
						<dt><?= __('Club president') ?></dt>
						<dd>
							<?php if ($president !== null):
								$presidentId = (string)$president->get('id');
								$presidentName = MembershipProfile::displayName($president);
								?>
								<a href="#"
									class="record-modal-link"
									data-id="<?= h($presidentId) ?>"
									data-get-url="<?= h($usersGetUrl) ?>"
									data-edit-url="<?= h($usersEditUrl) ?>"
									data-view-url="<?= h($usersViewUrl) ?>"
									data-delete-url="<?= h($usersDeleteUrl) ?>"
									data-delete-form-prefix="member"
									data-labels="user"
									data-title="<?= h(__('Club president')) ?>"
								><?= h($presidentName) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>
							<?php else: ?>
								—
							<?php endif; ?>
						</dd>
					</div>
					<div class="record-view-row"><dt><?= __('Enabled') ?></dt><dd><?= $club->enabled ? __('Yes') : __('No') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Visible') ?></dt><dd><?= $club->visible ? __('Yes') : __('No') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Position') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($club->pos, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Members') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::formatCount($userCountCached, decimals: 0)) ?></dd></div>
					<?php if ($usersCount > 0): ?>
						<div class="record-view-row">
							<dt><?= __('Member list') ?></dt>
							<dd class="record-related-list">
								<?php
								$memberLinks = [];
								foreach ($usersList as $user) {
									$userId = (string)$user->id;
									$label = MembershipProfile::displayName($user);
									if ($label === '') {
										$label = (string)($user->email ?? '');
									}
									$memberLinks[] = '<a href="#" class="record-modal-link"'
										. ' data-id="' . h($userId) . '"'
										. ' data-get-url="' . h($usersGetUrl) . '"'
										. ' data-edit-url="' . h($usersEditUrl) . '"'
										. ' data-view-url="' . h($usersViewUrl) . '"'
										. ' data-delete-url="' . h($usersDeleteUrl) . '"'
										. ' data-delete-form-prefix="member"'
										. ' data-labels="user"'
										. ' data-title="' . h(__('Member details')) . '"'
										. '>' . h($label)
										. '<span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>';
								}
								echo implode(', ', $memberLinks);
								?>
							</dd>
						</div>
					<?php endif; ?>
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
			],
		]) ?>
	</div>
</div>

<?= $this->element('admin/modal_linked_record_view') ?>
