<?php
/**
 * Club president — active members + club membership fee (one-click record).
 *
 * @var \App\View\AppView $this
 * @var iterable<\CakeDC\Users\Model\Entity\User> $members
 * @var string $clubName
 * @var int $clubId
 * @var int $clubCountryId
 * @var int $membershipYear
 */
use App\Auth\MembershipProfile;
use App\Utility\MembershipFee;

$this->Html->css(['pages/index', 'pages/membership_fee'], ['block' => true]);
$this->Html->script('pages/clubpresident_members', ['block' => 'scriptBottom']);
$this->assign('title', __('Active members'));

$clubName = (string)($clubName ?? '');
$membershipYear = (int)($membershipYear ?? MembershipFee::currentYear());
$clubCountryId = (int)($clubCountryId ?? 0);
$clubFeeLabel = MembershipFee::clubFeeLabel($clubCountryId);
$nationalFeeLabel = MembershipFee::nationalFeeLabel($clubCountryId);
?>
<div class="row">
	<div class="col-12 p-2">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-users"></i> <?= __('Active members') ?></h3>
					<?php if ($clubName !== ''): ?>
						<div class="text-muted"><?= h(__('Members of {0} — membership year {1}', $clubName, $membershipYear)) ?></div>
					<?php endif; ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<?= $this->element('admin/index_pagination') ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-2">
				<div class="table-responsive">
					<table class="table table-bordered table-hover table-striped mb-0 align-middle">
						<thead>
							<tr>
								<th><?= __('Name') ?></th>
								<th><?= __('Email') ?></th>
								<th class="text-center"><?= h($clubFeeLabel) ?> (<?= h($membershipYear) ?>)</th>
								<th class="text-center"><?= h($nationalFeeLabel) ?> (<?= h($membershipYear) ?>)</th>
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
								?>
								<tr>
									<td><?= h($name) ?></td>
									<td><?= h((string)$member->email) ?></td>
									<td>
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
											'mode' => 'table_action',
											'memberName' => $name,
											'formId' => $formId,
											'memberId' => $memberId,
										]) ?>
									</td>
									<td>
										<?= $this->element('users/membership_fee_status', [
											'label' => $nationalFeeLabel,
											'paid' => $nationalFeePaid,
											'membershipYear' => $membershipYear,
											'dateFormatted' => $nationalFeeDateFormatted,
											'mode' => 'table',
										]) ?>
									</td>
								</tr>
							<?php endforeach; ?>
							<?php if ($empty): ?>
								<tr>
									<td colspan="4" class="text-center text-muted py-4">
										<?= __('No active members.') ?>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
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
	recordCancel: <?= json_encode(__('Cancel'), JSON_UNESCAPED_UNICODE) ?>
};
</script>
