<?php
/**
 * Membership fee status — profile or club president members table.
 *
 * @var \App\View\AppView $this
 * @var string $label Fee type label
 * @var bool $paid Paid for membership year
 * @var int $membershipYear
 * @var string $dateFormatted Localized payment date (when paid for membership year)
 * @var string $lastPaymentDateFormatted Stored fee date (any year); empty if never paid
 * @var string $mode profile|table|table_action
 * @var string $memberName For SWAL (table_action)
 * @var string $formId Hidden POST form id (table_action)
 * @var string $memberId User id (table_action)
 */
$label = (string)($label ?? '');
$paid = (bool)($paid ?? false);
$membershipYear = (int)($membershipYear ?? \App\Utility\MembershipFee::currentYear());
$dateFormatted = (string)($dateFormatted ?? '');
$lastPaymentDateFormatted = (string)($lastPaymentDateFormatted ?? '');
$mode = (string)($mode ?? 'profile');
$memberName = (string)($memberName ?? '');
$formId = (string)($formId ?? '');
$memberId = (string)($memberId ?? '');

$lastPaymentHint = $lastPaymentDateFormatted !== ''
	? __('Last paid on {0}', $lastPaymentDateFormatted)
	: __('Has not paid yet');

if ($mode === 'profile'): ?>
	<div class="membership-fee-status membership-fee-status--profile <?= $paid ? 'membership-fee-status--paid' : 'membership-fee-status--unpaid' ?>">
		<div class="membership-fee-status__head">
			<span class="membership-fee-status__icon" aria-hidden="true">
				<?php if ($paid): ?>
					<i class="fa fa-check-circle"></i>
				<?php else: ?>
					<i class="fa fa-exclamation-circle"></i>
				<?php endif; ?>
			</span>
			<div class="membership-fee-status__titles">
				<div class="membership-fee-status__label"><?= h($label) ?></div>
				<div class="membership-fee-status__year"><?= h(__('Membership year {0}', $membershipYear)) ?></div>
			</div>
		</div>
		<?php if ($paid): ?>
			<div class="membership-fee-status__message membership-fee-status__message--paid">
				<?= h(__('Paid on {0}', $dateFormatted)) ?>
			</div>
		<?php else: ?>
			<div class="membership-fee-status__message membership-fee-status__message--unpaid">
				<strong><?= __('Membership fee not paid yet') ?></strong>
				<span class="d-block mt-1"><?= __('Please pay your {0} for {1} and contact your club president.', h($label), $membershipYear) ?></span>
			</div>
		<?php endif; ?>
	</div>
<?php elseif ($mode === 'table_national_action'): ?>
	<?php if ($paid): ?>
		<div class="membership-fee-cell membership-fee-cell--paid text-center">
			<span class="membership-fee-cell__check" title="<?= h(__('Paid on {0}', $dateFormatted)) ?>">
				<i class="fa fa-check-circle" aria-hidden="true"></i>
				<span class="visually-hidden"><?= h(__('Paid')) ?></span>
			</span>
			<?php if ($dateFormatted !== ''): ?>
				<div class="membership-fee-cell__date small text-muted"><?= h($dateFormatted) ?></div>
			<?php endif; ?>
		</div>
	<?php else: ?>
		<div class="membership-fee-cell membership-fee-cell--unpaid text-center">
			<button
				type="button"
				class="btn btn-sm btn-danger membership-fee-record-btn js-record-national-fee"
				data-form-id="<?= h($formId) ?>"
				data-member-name="<?= h($memberName) ?>"
				title="<?= h(__('Record national fee payment')) ?>"
			>
				<i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
				<span class="ms-1"><?= h(__('Outstanding')) ?></span>
			</button>
			<div class="membership-fee-cell__last small text-muted mt-1"><?= h($lastPaymentHint) ?></div>
		</div>
	<?php endif; ?>
<?php elseif ($mode === 'table_action'): ?>
	<?php if ($paid): ?>
		<div class="membership-fee-cell membership-fee-cell--paid text-center">
			<span class="membership-fee-cell__check" title="<?= h(__('Paid on {0}', $dateFormatted)) ?>">
				<i class="fa fa-check-circle" aria-hidden="true"></i>
				<span class="visually-hidden"><?= h(__('Paid')) ?></span>
			</span>
			<?php if ($dateFormatted !== ''): ?>
				<div class="membership-fee-cell__date small text-muted"><?= h($dateFormatted) ?></div>
			<?php endif; ?>
		</div>
	<?php else: ?>
		<div class="membership-fee-cell membership-fee-cell--unpaid text-center">
			<button
				type="button"
				class="btn btn-sm btn-danger membership-fee-record-btn js-record-club-fee"
				data-form-id="<?= h($formId) ?>"
				data-member-name="<?= h($memberName) ?>"
				title="<?= h(__('Record payment')) ?>"
			>
				<i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
				<span class="ms-1"><?= h(__('Outstanding')) ?></span>
			</button>
			<div class="membership-fee-cell__last small text-muted mt-1"><?= h($lastPaymentHint) ?></div>
		</div>
	<?php endif; ?>
<?php else: /* table — read-only national column */ ?>
	<div class="membership-fee-cell text-center <?= $paid ? 'membership-fee-cell--paid' : 'membership-fee-cell--unpaid' ?>">
		<?php if ($paid): ?>
			<span class="membership-fee-cell__check" title="<?= h(__('Paid on {0}', $dateFormatted)) ?>">
				<i class="fa fa-check-circle" aria-hidden="true"></i>
			</span>
			<?php if ($dateFormatted !== ''): ?>
				<div class="membership-fee-cell__date small text-muted"><?= h($dateFormatted) ?></div>
			<?php endif; ?>
		<?php else: ?>
			<span class="membership-fee-cell__warning text-danger" title="<?= h(__('Not paid for {0}', $membershipYear)) ?>">
				<i class="fa fa-times-circle" aria-hidden="true"></i>
				<span class="visually-hidden"><?= h(__('Not paid')) ?></span>
			</span>
			<div class="membership-fee-cell__last small text-muted mt-1"><?= h($lastPaymentHint) ?></div>
		<?php endif; ?>
	</div>
<?php endif; ?>
