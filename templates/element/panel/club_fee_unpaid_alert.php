<?php
/**
 * Dashboard warning: club membership fee unpaid for the current year.
 *
 * @var \App\View\AppView $this
 * @var bool $clubFeeUnpaid
 * @var int $membershipYear
 */
$clubFeeUnpaid = (bool)($clubFeeUnpaid ?? false);
$membershipYear = (int)($membershipYear ?? \App\Utility\MembershipFee::currentYear());
if (!$clubFeeUnpaid) {
	return;
}
?>
<div class="alert alert-warning mb-3" role="alert">
	<h5 class="alert-heading">
		<i class="fa fa-exclamation-triangle me-1" aria-hidden="true"></i>
		<?= __('Club membership fee unpaid') ?>
	</h5>
	<p class="mb-0">
		<?= __('You have not paid the club membership fee for {0} yet. Please pay your club and contact your club president.', $membershipYear) ?>
	</p>
</div>
