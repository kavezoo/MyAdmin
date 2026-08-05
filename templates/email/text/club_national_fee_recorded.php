<?php
/**
 * @var \App\View\AppView $this
 * @var string $presidentName
 * @var string $clubName
 * @var int $membershipYear
 * @var string $associationName
 * @var string $paymentDateFormatted
 */
?>
<?= __('Hello{0},', $presidentName !== '' ? ' ' . $presidentName : '') ?>


<?= __(
	'We confirm that the annual membership fee payment for club {0} has been received by the leadership and has been booked.',
	$clubName !== '' ? $clubName : __('your club')
) ?>


<?= __(
	'The payment date is {0}. Your club\'s annual membership fee for {1} is settled, and membership is valid toward {2}.',
	$paymentDateFormatted,
	$membershipYear,
	$associationName
) ?>


<?= __('Thank you.') ?>
