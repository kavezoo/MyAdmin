<?php
/**
 * Check-in: membership type + fee status for the year (info only).
 *
 * @var \App\View\AppView $this
 * @var mixed $user
 * @var int|null $countryId
 * @var int|null $membershipYear
 */
use App\Utility\MembershipFee;

if ($user === null) {
	return;
}

$year = (int)($membershipYear ?? MembershipFee::currentYear());
$countryId = (int)($countryId ?? 0);
if ($countryId < 1 && is_object($user) && method_exists($user, 'get')) {
	$countryId = (int)($user->get('country_id') ?? 0);
}

$clubDate = is_object($user) && method_exists($user, 'get')
	? $user->get(MembershipFee::FIELD_CLUB)
	: ($user[MembershipFee::FIELD_CLUB] ?? null);
$nationalDate = is_object($user) && method_exists($user, 'get')
	? $user->get(MembershipFee::FIELD_NATIONAL)
	: ($user[MembershipFee::FIELD_NATIONAL] ?? null);

$clubPaid = MembershipFee::isPaidForYear($clubDate, $year);
$nationalPaid = MembershipFee::isPaidForYear($nationalDate, $year);
$clubWhen = MembershipFee::paidDateFormatted($clubDate, $year);
$nationalWhen = MembershipFee::paidDateFormatted($nationalDate, $year);
$clubLast = MembershipFee::lastPaymentFormatted($clubDate);
$nationalLast = MembershipFee::lastPaymentFormatted($nationalDate);

$clubLabel = MembershipFee::clubFeeLabel($countryId);
$nationalLabel = MembershipFee::nationalFeeLabel($countryId);
?>
<div class="checkin-member-info mt-2">
	<div class="d-flex flex-wrap gap-2 mb-2">
		<?php if ($nationalPaid): ?>
			<span class="badge text-bg-primary"><?= h(__('National member')) ?></span>
		<?php else: ?>
			<span class="badge text-bg-secondary"><?= h(__('Club member only')) ?></span>
		<?php endif; ?>
		<?php if ($clubPaid): ?>
			<span class="badge text-bg-success"><?= h(__('Club fee paid ({0})', (string)$year)) ?></span>
		<?php else: ?>
			<span class="badge text-bg-warning text-dark"><?= h(__('Club fee unpaid ({0})', (string)$year)) ?></span>
		<?php endif; ?>
		<?php if ($nationalPaid): ?>
			<span class="badge text-bg-success"><?= h(__('National fee paid ({0})', (string)$year)) ?></span>
		<?php else: ?>
			<span class="badge text-bg-warning text-dark"><?= h(__('National fee unpaid ({0})', (string)$year)) ?></span>
		<?php endif; ?>
	</div>
	<ul class="list-unstyled small text-muted mb-0 checkin-member-fee-dates">
		<li>
			<?= h($clubLabel) ?> (<?= h((string)$year) ?>):
			<?php if ($clubPaid && $clubWhen !== ''): ?>
				<span class="text-success"><?= h(__('Paid on {0}', $clubWhen)) ?></span>
			<?php elseif ($clubLast !== ''): ?>
				<span class="text-warning"><?= h(__('Not paid for {0}', (string)$year)) ?>
					(<?= h(__('last: {0}', $clubLast)) ?>)</span>
			<?php else: ?>
				<span class="text-warning"><?= h(__('Not paid for {0}', (string)$year)) ?></span>
			<?php endif; ?>
		</li>
		<li>
			<?= h($nationalLabel) ?> (<?= h((string)$year) ?>):
			<?php if ($nationalPaid && $nationalWhen !== ''): ?>
				<span class="text-success"><?= h(__('Paid on {0}', $nationalWhen)) ?></span>
			<?php elseif ($nationalLast !== ''): ?>
				<span class="text-warning"><?= h(__('Not paid for {0}', (string)$year)) ?>
					(<?= h(__('last: {0}', $nationalLast)) ?>)</span>
			<?php else: ?>
				<span class="text-warning"><?= h(__('Not paid for {0}', (string)$year)) ?></span>
			<?php endif; ?>
		</li>
	</ul>
</div>
