<?php
/**
 * President / vice president — country members + national membership fee.
 *
 * @var \App\View\AppView $this
 * @var iterable<\CakeDC\Users\Model\Entity\User> $members
 * @var string $countryLabel
 * @var int $countryId
 * @var int $membershipYear
 * @var bool $nationalPaidOnly
 */
use App\Auth\MembershipProfile;
use App\Utility\MembershipFee;

$this->Html->css(['pages/index', 'pages/membership_fee', 'pages/users_list_avatar'], ['block' => true]);

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
$showIdColumn = true;
$showClubColumn = true;
$showActiveColumn = true;
$showEnabledColumn = true;
$showPosColumn = false;
$showVisibleColumn = false;
$showCreatedColumn = true;
$showModifiedColumn = true;

$showTimestampColumn = $showCreatedColumn || $showModifiedColumn;
$indexColspan = 5; // name, email, club fee, national fee, actions
if ($showIdColumn) {
	$indexColspan++;
}
if ($showClubColumn) {
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

$countryLabel = (string)($countryLabel ?? '');
$membershipYear = (int)($membershipYear ?? MembershipFee::currentYear());
$countryId = (int)($countryId ?? 0);
$nationalPaidOnly = (bool)($nationalPaidOnly ?? false);
$nationalFeeLabel = MembershipFee::nationalFeeLabel($countryId);
$clubFeeLabel = MembershipFee::clubFeeLabel($countryId);
$nationalFilterQuery = $this->request->getQueryParams();
unset($nationalFilterQuery['national_paid_only']);
$nationalFilterQuery['page'] = '1';

$tooltipDetails = '<b>' . __('View details') . '</b><br>' . __('View the selected record details.');
$tooltipEdit = '<b>' . __('Edit') . '</b><br>' . __('Edit the selected record.');
$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.');
$tooltipDeleteBlocked = '<b>' . __('Delete') . '</b><br>' . __('Cannot delete this record because it has related child records.');

$rowDoubleClickHints = [
	'modal' => __('Double-click a row to view the record details.'),
	'edit' => __('Double-click a row to edit the record.'),
	'none' => '',
];
$rowDoubleClickHint = $rowDoubleClickHints[$rowDoubleClickAction] ?? $rowDoubleClickHints['modal'];

$memberRecordLabels = [
	'id' => __('ID'),
	'first_name' => __('Name'),
	'email' => __('Email'),
	'phone' => __('Phone'),
	'role' => __('Role'),
	'country' => __('Country'),
	'club' => __('Club'),
	'active' => __('Active'),
	'enabled' => __('Enabled'),
	MembershipFee::FIELD_CLUB => $clubFeeLabel,
	MembershipFee::FIELD_NATIONAL => $nationalFeeLabel,
	'created' => __('Created'),
	'modified' => __('Modified'),
];

$config = [
	'rowDoubleClickAction' => $rowDoubleClickAction,
	'recordGetUrl' => $this->Url->build(['action' => 'recordGet']),
	'categoryGetUrl' => $this->Url->build(['action' => 'clubRecordGet']),
	'editUrl' => $this->Url->build(['action' => 'view']),
	'viewUrl' => $this->Url->build(['action' => 'view']),
	'deleteUrl' => '',
	'recordFieldLabels' => $memberRecordLabels,
	'categoryFieldLabels' => [
		'id' => __('ID'),
		'name' => __('Name'),
		'country' => __('Country'),
		'enabled' => __('Enabled'),
		'visible' => __('Visible'),
		'pos' => __('Position'),
		'created' => __('Created'),
		'modified' => __('Modified'),
	],
	'entityFieldLabels' => [
		'club' => [
			'id' => __('ID'),
			'name' => __('Name'),
			'country' => __('Country'),
			'enabled' => __('Enabled'),
			'visible' => __('Visible'),
			'pos' => __('Position'),
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
$this->Html->script(['pages/index', 'pages/president_members'], ['block' => 'scriptBottom']);
$this->assign('title', __('Members'));
?>
<div class="row">
	<div class="col-12 p-2">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-users"></i> <?= __('Members') ?></h3>
					<?php if ($countryLabel !== ''): ?>
						<div class="text-muted"><?= h(__('Members in {0} — membership year {1}', $countryLabel, $membershipYear)) ?></div>
					<?php endif; ?>
					<?php if ($rowDoubleClickHint !== ''): ?>
						<div class="small text-muted"><?= h($rowDoubleClickHint) ?></div>
					<?php endif; ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<form method="get" action="<?= h($this->Url->build(['action' => 'index'])) ?>"
						class="members-national-paid-filter mb-0"
						id="members-national-paid-filter">
						<?php foreach ($nationalFilterQuery as $name => $value): ?>
							<?php if (!is_scalar($value)) {
								continue;
							} ?>
							<input type="hidden" name="<?= h((string)$name) ?>" value="<?= h((string)$value) ?>">
						<?php endforeach; ?>
						<input type="hidden" name="national_paid_only" value="0" id="members-national-paid-only-off"
							<?= $nationalPaidOnly ? 'disabled' : '' ?>>
						<div class="form-check form-switch mb-0">
							<input type="checkbox"
								class="form-check-input"
								id="members-national-paid-only"
								name="national_paid_only"
								value="1"
								<?= $nationalPaidOnly ? 'checked' : '' ?>
								onchange="document.getElementById('members-national-paid-only-off').disabled = this.checked; this.form.submit();">
							<label class="form-check-label text-nowrap" for="members-national-paid-only"><?= __('Only national fee paid') ?></label>
						</div>
					</form>
					<span class="index-header-sep" aria-hidden="true">|</span>
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
									<th scope="col" class="number id"><?= $this->Paginator->sort('id', '#') ?></th>
								<?php endif; ?>
								<?php if ($showClubColumn): ?>
									<th scope="col" class="string category-id"><?= $this->Paginator->sort('Clubs.name', __('Club')) ?></th>
								<?php endif; ?>
								<th scope="col" class="string name"><?= $this->Paginator->sort('first_name', __('Name')) ?></th>
								<th scope="col" class="string email"><?= $this->Paginator->sort('email', __('Email')) ?></th>
								<?php if ($showActiveColumn): ?>
									<th scope="col" class="boolean active"><?= $this->Paginator->sort('active', __('Active')) ?></th>
								<?php endif; ?>
								<?php if ($showEnabledColumn): ?>
									<th scope="col" class="boolean enabled"><?= $this->Paginator->sort('enabled', __('Enabled')) ?></th>
								<?php endif; ?>
								<?php if ($showPosColumn): ?>
									<th scope="col" class="number pos"><?= $this->Paginator->sort('pos', __('Position')) ?></th>
								<?php endif; ?>
								<?php if ($showVisibleColumn): ?>
									<th scope="col" class="boolean visible"><?= $this->Paginator->sort('visible', __('Visible')) ?></th>
								<?php endif; ?>
								<th scope="col" class="date club-fee text-center"><?= $this->Paginator->sort(MembershipFee::FIELD_CLUB, h($clubFeeLabel) . ' (' . h($membershipYear) . ')') ?></th>
								<th scope="col" class="date national-fee text-center"><?= $this->Paginator->sort(MembershipFee::FIELD_NATIONAL, h($nationalFeeLabel) . ' (' . h($membershipYear) . ')') ?></th>
								<?php if ($showTimestampColumn): ?>
									<th scope="col" class="datetime<?= $showCreatedColumn ? ' created' : '' ?><?= $showModifiedColumn ? ' modified' : '' ?>">
										<?php if ($showCreatedColumn): ?>
											<?= $this->Paginator->sort('created', __('Created')) ?>
										<?php endif; ?>
										<?php if ($showModifiedColumn): ?>
											<?= $this->Paginator->sort('modified', __('Modified')) ?>
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
								$formId = 'member-national-fee-form-' . $memberId;
								$clubLabel = '';
								$clubIdRow = 0;
								if (!empty($member->club) && !empty($member->club->name)) {
									$clubLabel = (string)$member->club->name;
									$clubIdRow = (int)($member->club_id ?? 0);
								}
								$clubFeeDate = $member->club_membership_fee_date ?? null;
								$nationalFeeDate = $member->national_membership_fee_date ?? null;
								$clubFeePaid = MembershipFee::isPaidForYear($clubFeeDate, $membershipYear);
								$nationalFeePaid = MembershipFee::isPaidForYear($nationalFeeDate, $membershipYear);
								$clubFeeDateFormatted = MembershipFee::paidDateFormatted($clubFeeDate, $membershipYear);
								$nationalFeeDateFormatted = MembershipFee::paidDateFormatted($nationalFeeDate, $membershipYear);
								?>
								<tr id="record-<?= h($memberId) ?>" data-id="<?= h($memberId) ?>" data-can-delete="0">
									<?php if ($showIdColumn): ?>
										<td class="number id"><?= h($memberId) ?></td>
									<?php endif; ?>
									<?php if ($showClubColumn): ?>
										<td class="string category-id">
											<?php if ($clubIdRow > 0 && $clubLabel !== ''): ?>
												<a href="#"
													class="category-link record-modal-link"
													data-id="<?= (int)$clubIdRow ?>"
													data-get-url="<?= h($this->Url->build(['action' => 'clubRecordGet'])) ?>"
													data-view-url=""
													data-edit-url=""
													data-delete-url=""
													data-labels="club"
													data-title="<?= h(__('Club details')) ?>"
												>
													<?= h($clubLabel) ?><span class="category-link-icon record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span>
												</a>
											<?php endif; ?>
										</td>
									<?php endif; ?>
									<td class="string name users-list-name-cell">
										<div class="d-flex align-items-center gap-2">
											<?= $this->element('users/list_avatar', [
												'user' => $member,
												'displayName' => $name,
												'size' => 40,
											]) ?>
											<span class="users-list-name-cell__label"><?= h($name) ?></span>
										</div>
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
										<td class="boolean enabled text-center">
											<?= (int)($member->enabled ?? 0) === 1
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
										<?= $this->element('users/membership_fee_status', [
											'label' => $clubFeeLabel,
											'paid' => $clubFeePaid,
											'membershipYear' => $membershipYear,
											'dateFormatted' => $clubFeeDateFormatted,
											'mode' => 'table',
										]) ?>
									</td>
									<td class="date national-fee text-center">
										<?= $this->Form->create(null, [
											'url' => ['action' => 'updateNationalFee', $memberId],
											'id' => $formId,
											'class' => 'membership-fee-hidden-form',
										]) ?>
										<?= $this->Form->end() ?>
										<?= $this->element('users/membership_fee_status', [
											'label' => $nationalFeeLabel,
											'paid' => $nationalFeePaid,
											'membershipYear' => $membershipYear,
											'dateFormatted' => $nationalFeeDateFormatted,
											'mode' => 'table_national_action',
											'memberName' => $name,
											'formId' => $formId,
											'memberId' => $memberId,
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
											['action' => 'view', $memberId],
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
				<div class="mt-2">
					<?= $this->element('admin/index_footer') ?>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
window.PresidentMembers = {
	recordTitle: <?= json_encode(__('Record national membership fee payment?'), JSON_UNESCAPED_UNICODE) ?>,
	recordText: <?= json_encode(__('Do you confirm that this member has paid the national membership fee for this year (e.g. MPE in Hungary)? The payment date will be set to today.'), JSON_UNESCAPED_UNICODE) ?>,
	recordTextNamed: <?= json_encode(__('Do you confirm that {0} has paid the national membership fee for this year? The payment date will be set to today.'), JSON_UNESCAPED_UNICODE) ?>,
	recordConfirm: <?= json_encode(__('Yes, record payment'), JSON_UNESCAPED_UNICODE) ?>,
	recordCancel: <?= json_encode(__('Cancel'), JSON_UNESCAPED_UNICODE) ?>
};
</script>
<?= $this->element('admin/modal_record_view') ?>
<?= $this->element('admin/modal_linked_record_view') ?>
