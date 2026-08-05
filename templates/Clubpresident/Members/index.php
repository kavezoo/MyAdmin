<?php
/**
 * Club president — active members + club membership fee (one-click record).
 *
 * @var \App\View\AppView $this
 * @var iterable<\CakeDC\Users\Model\Entity\User> $members
 * @var iterable<\CakeDC\Users\Model\Entity\User> $applicants
 * @var string $clubName
 * @var int $clubId
 * @var int $clubCountryId
 * @var int $membershipYear
 */
use App\Auth\MembershipProfile;
use App\Utility\MembershipFee;

$this->Html->css([
	'pages/index',
	'pages/membership_fee',
	'pages/users_list_avatar',
	'pages/clubpresident_applicants',
], ['block' => true]);

/**
 * Row double-click: 'modal' | 'edit' | 'none'
 */
$rowDoubleClickAction = 'modal'; // 'modal' | 'edit' | 'none'

$numberDecimals = [
	'integer' => 0,
	'decimal' => 2,
];

/**
 * Optional index columns (true = show, false = hide).
 */
$showIdColumn = false;
$showActiveColumn = true;
$showEnabledColumn = true;
$showPosColumn = false;
$showVisibleColumn = false;
$showCreatedColumn = false;
$showModifiedColumn = false;

$showTimestampColumn = $showCreatedColumn || $showModifiedColumn;
$indexColspan = 5; // name, email, club fee, national fee, actions
if ($showIdColumn) {
	$indexColspan++;
}
if ($showActiveColumn) {
	$indexColspan++;
}
if ($showEnabledColumn) {
	$indexColspan++;
}
if ($showPosColumn) {
	$indexColspan++;
}
if ($showVisibleColumn) {
	$indexColspan++;
}
if ($showTimestampColumn) {
	$indexColspan++;
}

$clubName = (string)($clubName ?? '');
$membershipYear = (int)($membershipYear ?? MembershipFee::currentYear());
$clubCountryId = (int)($clubCountryId ?? 0);
$clubFeeLabel = MembershipFee::clubFeeLabel($clubCountryId);
$nationalFeeLabel = MembershipFee::nationalFeeLabel($clubCountryId);

$tooltipDetails = '<b>' . __('View details') . '</b><br>' . __('View the selected record details.');
$tooltipEdit = '<b>' . __('Edit') . '</b><br>' . __('Edit the selected record.');
$tooltipDeleteBlocked = '<b>' . __('Delete') . '</b><br>' . __('Cannot delete this record because it has related child records.');

$rowDoubleClickHints = [
	'modal' => __('Double-click a row to view the record details.'),
	'edit' => __('Double-click a row to edit the record.'),
	'none' => '',
];
$rowDoubleClickHint = $rowDoubleClickHints[$rowDoubleClickAction] ?? $rowDoubleClickHints['modal'];

$config = [
	'rowDoubleClickAction' => $rowDoubleClickAction,
	'recordGetUrl' => $this->Url->build(['action' => 'recordGet']),
	'editUrl' => $this->Url->build(['action' => 'edit']),
	'viewUrl' => $this->Url->build(['action' => 'view']),
	'deleteUrl' => '',
	'recordFieldLabels' => [
		'id' => __('ID'),
		'first_name' => __('Name'),
		'email' => __('Email'),
		'phone' => __('Phone'),
		'role' => __('Role'),
		'country' => __('Country'),
		'club' => __('Club'),
		'active' => __('Active'),
		'enabled' => __('Enabled'),
		\App\Auth\MembershipProfile::FIELD_JOINED => __('Member since'),
		MembershipFee::FIELD_CLUB => $clubFeeLabel,
		MembershipFee::FIELD_NATIONAL => $nationalFeeLabel,
		'created' => __('Created'),
		'modified' => __('Modified'),
	],
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);
$this->Html->script(['pages/index', 'pages/clubpresident_members', 'pages/clubpresident_applicants'], ['block' => 'scriptBottom']);
$this->assign('title', __('Members'));

$applicants = $applicants ?? [];
?>
<div class="row">
	<div class="col-12 p-2">
		<?= $this->element('clubpresident/applicant_cards', ['applicants' => $applicants]) ?>

		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-users"></i> <?= __('Members') ?></h3>
					<?php if ($clubName !== ''): ?>
						<div class="text-muted"><?= h(__('Members of {0} — membership year {1}', $clubName, $membershipYear)) ?></div>
					<?php endif; ?>
					<?php if ($rowDoubleClickHint !== ''): ?>
						<div class="small text-muted"><?= h($rowDoubleClickHint) ?></div>
					<?php endif; ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<?= $this->element('admin/index_pagination') ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-2">
				<div class="table-responsive">
					<table class="table table-bordered table-hover table-striped mb-0 align-middle index-data-table">
						<thead>
							<tr>
								<?php if ($showIdColumn): ?>
									<th scope="col" class="number id"><?= $this->Paginator->sort('Users.id', '#') ?></th>
								<?php endif; ?>
								<th scope="col" class="string name"><?= $this->Paginator->sort('Users.first_name', __('Name')) ?></th>
								<th scope="col" class="string email"><?= $this->Paginator->sort('Users.email', __('Email')) ?></th>
								<?php if ($showActiveColumn): ?>
									<th scope="col" class="boolean active"><?= $this->Paginator->sort('Users.active', __('Active')) ?></th>
								<?php endif; ?>
								<?php if ($showEnabledColumn): ?>
									<th scope="col" class="boolean enabled"><?= $this->Paginator->sort('Users.enabled', __('Enabled')) ?></th>
								<?php endif; ?>
								<?php if ($showPosColumn): ?>
									<th scope="col" class="number pos"><?= $this->Paginator->sort('Users.pos', __('Position')) ?></th>
								<?php endif; ?>
								<?php if ($showVisibleColumn): ?>
									<th scope="col" class="boolean visible"><?= $this->Paginator->sort('Users.visible', __('Visible')) ?></th>
								<?php endif; ?>
								<th scope="col" class="date club-fee text-center"><?= $this->Paginator->sort('Users.' . MembershipFee::FIELD_CLUB, h($clubFeeLabel) . ' (' . h($membershipYear) . ')') ?></th>
								<th scope="col" class="date national-fee text-center"><?= $this->Paginator->sort('Users.' . MembershipFee::FIELD_NATIONAL, h($nationalFeeLabel) . ' (' . h($membershipYear) . ')') ?></th>
								<?php if ($showTimestampColumn): ?>
									<th scope="col" class="datetime<?= $showCreatedColumn ? ' created' : '' ?><?= $showModifiedColumn ? ' modified' : '' ?>">
										<?php if ($showCreatedColumn): ?>
											<?= $this->Paginator->sort('Users.created', __('Created')) ?>
										<?php endif; ?>
										<?php if ($showModifiedColumn): ?>
											<?= $this->Paginator->sort('Users.modified', __('Modified')) ?>
										<?php endif; ?>
									</th>
								<?php endif; ?>
								<th scope="col" class="actions"><?= __('Actions') ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$empty = true;
							foreach ($members as $member):
								$empty = false;
								$name = MembershipProfile::displayName($member);
								if ($name === '') {
									$name = (string)($member->email ?? '');
								}
								$memberId = (string)$member->id;
								$formId = 'member-fee-form-' . $memberId;
								$clubFeeDate = $member->club_membership_fee_date ?? null;
								$nationalFeeDate = $member->national_membership_fee_date ?? null;
								$clubFeePaid = MembershipFee::isPaidForYear($clubFeeDate, $membershipYear);
								$nationalFeePaid = MembershipFee::isPaidForYear($nationalFeeDate, $membershipYear);
								$clubFeeDateFormatted = MembershipFee::paidDateFormatted($clubFeeDate, $membershipYear);
								$nationalFeeDateFormatted = MembershipFee::paidDateFormatted($nationalFeeDate, $membershipYear);
								$clubLastPaymentFormatted = MembershipFee::lastPaymentFormatted($clubFeeDate);
								$nationalLastPaymentFormatted = MembershipFee::lastPaymentFormatted($nationalFeeDate);
								$isEnabled = (int)($member->enabled ?? 0) === 1;
								?>
								<tr id="record-<?= h($memberId) ?>" data-id="<?= h($memberId) ?>" data-can-delete="0"<?= !$isEnabled ? ' class="table-secondary"' : '' ?>>
									<?php if ($showIdColumn): ?>
										<td class="number id"><?= h($memberId) ?></td>
									<?php endif; ?>
									<td class="string name users-list-name-cell">
										<?= $this->element('users/list_name_cell', [
											'user' => $member,
											'displayName' => $name,
											'size' => 40,
										]) ?>
									</td>
									<td class="string email"><?= h((string)$member->email) ?></td>
									<?php if ($showActiveColumn): ?>
										<td class="boolean active text-center">
											<?= (bool)$member->active
												? '<i class="fa fa-check text-success"></i>'
												: '<i class="fa fa-times text-danger"></i>' ?>
										</td>
									<?php endif; ?>
									<?php if ($showEnabledColumn): ?>
										<td class="boolean enabled text-center js-member-enabled-cell">
											<?= $isEnabled
												? '<i class="fa fa-check text-success"></i>'
												: '<i class="fa fa-times text-danger"></i>' ?>
										</td>
									<?php endif; ?>
									<?php if ($showPosColumn): ?>
										<td class="number pos text-end"></td>
									<?php endif; ?>
									<?php if ($showVisibleColumn): ?>
										<td class="boolean visible text-center"></td>
									<?php endif; ?>
									<td class="date club-fee text-center">
										<?= $this->Form->create(null, [
											'url' => ['action' => 'updateClubFee', $memberId],
											'id' => $formId,
											'class' => 'membership-fee-hidden-form',
										]) ?>
										<?= $this->Form->end() ?>
										<?= $this->element('users/membership_fee_status', [
											'label' => $clubFeeLabel,
											'paid' => $clubFeePaid,
											'membershipYear' => $membershipYear,
											'dateFormatted' => $clubFeeDateFormatted,
											'lastPaymentDateFormatted' => $clubLastPaymentFormatted,
											'mode' => 'table_action',
											'memberName' => $name,
											'formId' => $formId,
											'memberId' => $memberId,
										]) ?>
									</td>
									<td class="date national-fee text-center">
										<?= $this->element('users/membership_fee_status', [
											'label' => $nationalFeeLabel,
											'paid' => $nationalFeePaid,
											'membershipYear' => $membershipYear,
											'dateFormatted' => $nationalFeeDateFormatted,
											'lastPaymentDateFormatted' => $nationalLastPaymentFormatted,
											'mode' => 'table',
										]) ?>
									</td>
									<?php if ($showTimestampColumn): ?>
										<td class="datetime<?= $showCreatedColumn ? ' created' : '' ?><?= $showModifiedColumn ? ' modified' : '' ?>">
											<?php if ($showCreatedColumn): ?>
												<?= $member->created ? h(\App\Utility\LocaleDateParser::format($member->created, 'datetime_short')) : '' ?>
											<?php endif; ?>
											<?php if ($showCreatedColumn && $showModifiedColumn && $member->modified): ?>
												<br>
											<?php endif; ?>
											<?php if ($showModifiedColumn): ?>
												<?= $member->modified ? h(\App\Utility\LocaleDateParser::format($member->modified, 'datetime_short')) : '' ?>
											<?php endif; ?>
										</td>
									<?php endif; ?>
									<td class="actions">
										<?= $this->Html->link(
											'<i class="fa fa-eye"></i>',
											['action' => 'view', $memberId],
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
											['action' => 'edit', $memberId],
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
										<?php if ($isEnabled): ?>
											<button
												type="button"
												class="btn btn-outline-warning js-member-toggle-enabled"
												data-id="<?= h($memberId) ?>"
												data-enabled="1"
												data-name="<?= h($name) ?>"
												data-url="<?= h($this->Url->build(['action' => 'toggleEnabled', $memberId])) ?>"
												data-bs-toggle="tooltip"
												data-bs-placement="top"
												data-bs-html="true"
												title="<?= h('<b>' . __('Disable') . '</b><br>' . __('Disable this member account (cannot log in).')) ?>"
											>
												<i class="fa fa-ban"></i>
											</button>
										<?php else: ?>
											<button
												type="button"
												class="btn btn-outline-success js-member-toggle-enabled"
												data-id="<?= h($memberId) ?>"
												data-enabled="0"
												data-name="<?= h($name) ?>"
												data-url="<?= h($this->Url->build(['action' => 'toggleEnabled', $memberId])) ?>"
												data-bs-toggle="tooltip"
												data-bs-placement="top"
												data-bs-html="true"
												title="<?= h('<b>' . __('Enable') . '</b><br>' . __('Enable this member account (can log in again).')) ?>"
											>
												<i class="fa fa-check"></i>
											</button>
										<?php endif; ?>
										<span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDeleteBlocked) ?>">
											<a role="button" href="#" class="btn btn-secondary disabled" tabindex="-1" aria-disabled="true">
												<i class="fa fa-trash"></i>
											</a>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
							<?php if ($empty): ?>
								<tr>
									<td colspan="<?= (int)$indexColspan ?>" class="text-center text-muted py-4">
										<?= __('No members found.') ?>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
			<div class="card-footer">
				<?= $this->element('admin/index_footer') ?>
			</div>
		</div>
	</div>
</div>
<script>
window.ClubpresidentMembers = {
	recordTitle: <?= json_encode(__('Record membership fee payment?'), JSON_UNESCAPED_UNICODE) ?>,
	recordText: <?= json_encode(__('Do you confirm that this member has paid the club membership fee for this year? The payment date will be set to today.'), JSON_UNESCAPED_UNICODE) ?>,
	recordTextNamed: <?= json_encode(__('Do you confirm that {0} has paid the club membership fee for this year? The payment date will be set to today.'), JSON_UNESCAPED_UNICODE) ?>,
	recordConfirm: <?= json_encode(__('Yes, record payment'), JSON_UNESCAPED_UNICODE) ?>,
	recordCancel: <?= json_encode(__('Cancel'), JSON_UNESCAPED_UNICODE) ?>,
	disableTitle: <?= json_encode(__('Disable member?'), JSON_UNESCAPED_UNICODE) ?>,
	disableText: <?= json_encode(__('Do you really want to disable {0}? The member will not be able to log in.'), JSON_UNESCAPED_UNICODE) ?>,
	disableConfirm: <?= json_encode(__('Yes, disable'), JSON_UNESCAPED_UNICODE) ?>,
	disableLabel: <?= json_encode(__('Disable'), JSON_UNESCAPED_UNICODE) ?>,
	enableTitle: <?= json_encode(__('Enable member?'), JSON_UNESCAPED_UNICODE) ?>,
	enableText: <?= json_encode(__('Do you really want to enable {0}? The member will be able to log in again.'), JSON_UNESCAPED_UNICODE) ?>,
	enableConfirm: <?= json_encode(__('Yes, enable'), JSON_UNESCAPED_UNICODE) ?>,
	enableLabel: <?= json_encode(__('Enable'), JSON_UNESCAPED_UNICODE) ?>,
	toggleError: <?= json_encode(__('Could not update the member account.'), JSON_UNESCAPED_UNICODE) ?>
};
window.ClubpresidentApplicants = {
	approveTitle: <?= json_encode(__('Approve membership?'), JSON_UNESCAPED_UNICODE) ?>,
	approveText: <?= json_encode(__('Do you really want to approve this applicant as a full member?'), JSON_UNESCAPED_UNICODE) ?>,
	approveConfirm: <?= json_encode(__('Yes, approve'), JSON_UNESCAPED_UNICODE) ?>,
	rejectTitle: <?= json_encode(__('Reject application?'), JSON_UNESCAPED_UNICODE) ?>,
	rejectText: <?= json_encode(__('Do you really want to reject this application? The user will be disabled and cannot log in.'), JSON_UNESCAPED_UNICODE) ?>,
	rejectConfirm: <?= json_encode(__('Yes, reject'), JSON_UNESCAPED_UNICODE) ?>
};
</script>
<?= $this->element('admin/modal_record_view') ?>
