<?php
/**
 * Admin user view — main fields + related competition applications tab.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var string $countryLabel
 * @var bool $canDelete
 */
use App\Auth\AppRoles;
use App\Auth\MembershipProfile;
use App\Utility\CompetitionApplication;
use App\Utility\MembershipFee;

$this->Html->css(['pages/index'], ['block' => true]);
$this->Html->script(['pages/index'], ['block' => 'scriptBottom']);

$countryLabel = (string)($countryLabel ?? \App\Utility\AdminCountry::label((int)$user->country_id));
$canDelete = (bool)($canDelete ?? false);
$roleKey = strtolower(trim((string)($user->role ?? '')));
$roleLabel = $roleKey !== '' ? AppRoles::label($roleKey) : '—';
$clubName = $user->club !== null ? (string)$user->club->name : '';
$clubId = (int)($user->club_id ?? 0);

$clubsGetUrl = $this->Url->build(['controller' => 'Clubs', 'action' => 'recordGet']);
$clubsEditUrl = $this->Url->build(['controller' => 'Clubs', 'action' => 'edit']);
$clubsViewUrl = $this->Url->build(['controller' => 'Clubs', 'action' => 'view']);
$clubsDeleteUrl = $this->Url->build(['controller' => 'Clubs', 'action' => 'delete']);

$applicationsGetUrl = $this->Url->build(['action' => 'applicationRecordGet']);

$tooltipDetails = '<b>' . __('View details') . '</b><br>' . __('View the selected record details.');
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
			'enabled' => __('Enabled'),
			'visible' => __('Visible'),
			'user_count' => __('Members'),
			'created' => __('Created'),
			'modified' => __('Modified'),
		],
		'competition_application' => [
			'id' => __('ID'),
			'competition' => __('Competition'),
			'club' => __('Club'),
			'team' => __('Team'),
			'status' => __('Status'),
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

$applications = $user->competitions_users ?? [];
$applicationsList = is_array($applications) ? $applications : iterator_to_array($applications);
$applicationsCount = count($applicationsList);

ob_start();
if ($applicationsCount > 0):
?>
<table
	class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table related-records-table"
	data-get-url="<?= h($applicationsGetUrl) ?>"
	data-edit-url=""
	data-view-url=""
	data-delete-url=""
	data-delete-form-prefix="application"
	data-labels="competition_application"
	data-title="<?= h(__('Competition application')) ?>"
>
	<thead>
		<tr>
			<th scope="col" class="string competition"><?= __('Competition') ?></th>
			<th scope="col" class="string club"><?= __('Club') ?></th>
			<th scope="col" class="string team"><?= __('Team') ?></th>
			<th scope="col" class="string status"><?= __('Status') ?></th>
			<th scope="col" class="datetime created modified"><?= __('Created') ?><br><?= __('Modified') ?></th>
			<th scope="col" class="actions"><?= __('Actions') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($applicationsList as $application):
			$applicationId = (int)$application->id;
			$competitionName = (string)($application->competition->name ?? '—');
			$teamName = '—';
			$appClubName = '—';
			if ($application->competitions_club !== null) {
				if ($application->competitions_club->subclub !== null) {
					$teamName = (string)$application->competitions_club->subclub->name;
				}
				if ($application->competitions_club->club !== null) {
					$appClubName = (string)$application->competitions_club->club->name;
				}
			}
			?>
			<tr id="related-application-<?= $applicationId ?>" data-id="<?= $applicationId ?>" data-can-delete="0">
				<td class="string competition">
					<a href="#"
						class="record-modal-link fw-bold"
						data-id="<?= $applicationId ?>"
						data-labels="competition_application"
						data-title="<?= h(__('Competition application')) ?>"
					><?= h($competitionName) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>
				</td>
				<td class="string club"><?= h($appClubName) ?></td>
				<td class="string team"><?= h($teamName) ?></td>
				<td class="string status"><?= h(CompetitionApplication::statusLabel((string)$application->status)) ?></td>
				<td class="datetime created modified">
					<?= $application->created ? h(\App\Utility\LocaleDateParser::format($application->created, 'datetime_short')) : '' ?>
					<?php if (!empty($application->modified)): ?>
						<br><?= h(\App\Utility\LocaleDateParser::format($application->modified, 'datetime_short')) ?>
					<?php endif; ?>
				</td>
				<td class="actions">
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
$applicationsTable = (string)ob_get_clean();
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-eye"></i> <?= __('User details') ?></h3>
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
					<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h($user->id) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Name') ?></dt><dd><?= h(MembershipProfile::displayName($user)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Email') ?></dt><dd><?= h((string)$user->email) ?></dd></div>
					<?php if (trim((string)($user->phone ?? '')) !== ''): ?>
						<div class="record-view-row"><dt><?= __('Phone') ?></dt><dd><?= h($user->phone) ?></dd></div>
					<?php endif; ?>
					<div class="record-view-row"><dt><?= __('Role') ?></dt><dd><?= h($roleLabel) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Country') ?></dt><dd><?= h($countryLabel) ?></dd></div>
					<div class="record-view-row">
						<dt><?= __('Club') ?></dt>
						<dd>
							<?php if ($clubId > 0 && $clubName !== ''): ?>
								<a href="#"
									class="record-modal-link fw-bold"
									data-id="<?= $clubId ?>"
									data-get-url="<?= h($clubsGetUrl) ?>"
									data-edit-url="<?= h($clubsEditUrl) ?>"
									data-view-url="<?= h($clubsViewUrl) ?>"
									data-delete-url="<?= h($clubsDeleteUrl) ?>"
									data-labels="club"
									data-title="<?= h(__('Club details')) ?>"
								><?= h($clubName) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>
							<?php else: ?>
								—
							<?php endif; ?>
						</dd>
					</div>
					<div class="record-view-row"><dt><?= __('Active') ?></dt><dd><?= !empty($user->active) ? __('Yes') : __('No') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Enabled') ?></dt><dd><?= (int)($user->enabled ?? 0) === 1 ? __('Yes') : __('No') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Membership status') ?></dt><dd><?= h((string)($user->membership_status ?? '')) ?: '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Member since') ?></dt><dd><?= $user->membership_joined_date ? h(\App\Utility\LocaleDateParser::format($user->membership_joined_date, 'date')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= h(MembershipFee::clubFeeLabel((int)$user->country_id)) ?></dt><dd><?= $user->club_membership_fee_date ? h(\App\Utility\LocaleDateParser::format($user->club_membership_fee_date, 'date')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= h(MembershipFee::nationalFeeLabel((int)$user->country_id)) ?></dt><dd><?= $user->national_membership_fee_date ? h(\App\Utility\LocaleDateParser::format($user->national_membership_fee_date, 'date')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Created') ?></dt><dd><?= $user->created ? h(\App\Utility\LocaleDateParser::format($user->created, 'datetime_short')) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Modified') ?></dt><dd><?= $user->modified ? h(\App\Utility\LocaleDateParser::format($user->modified, 'datetime_short')) : '—' ?></dd></div>
				</dl>
			</div>
			<div class="card-footer">
				<div class="record-view-footer-actions">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . __('Edit'),
						['action' => 'edit', $user->id],
						['escape' => false, 'class' => 'btn btn-primary']
					) ?>
					<?php if ($canDelete): ?>
						<?= $this->Form->create(null, [
							'url' => ['action' => 'delete', $user->id],
							'id' => 'delete-form-' . $user->id,
							'class' => 'd-inline',
						]) ?>
						<button type="button"
							class="btn btn-outline-danger ms-2 btn-row-delete"
							data-id="<?= h((string)$user->id) ?>"
							data-swal-title="<?= h(__('Delete')) ?>"
							data-swal-text="<?= h(__('Are you sure you want to delete this record?')) ?>">
							<span class="btn-label"><i class="fa fa-trash"></i></span><?= __('Delete') ?>
						</button>
						<?= $this->Form->end() ?>
					<?php else: ?>
						<span class="d-inline-block ms-2" tabindex="0" data-bs-toggle="tooltip" data-bs-html="true" title="<?= h($tooltipDeleteBlocked) ?>">
							<button type="button" class="btn btn-secondary disabled" tabindex="-1" aria-disabled="true">
								<span class="btn-label"><i class="fa fa-trash"></i></span><?= __('Delete') ?>
							</button>
						</span>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<?= $this->element('admin/view_related_tabs', [
			'relatedTabs' => [
				[
					'id' => 'competition-applications',
					'title' => __('Competition applications'),
					'count' => $applicationsCount,
					'table' => $applicationsTable,
				],
			],
		]) ?>
	</div>
</div>

<?= $this->element('admin/modal_linked_record_view') ?>
