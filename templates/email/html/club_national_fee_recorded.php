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
<p><?= h(__('Hello{0},', $presidentName !== '' ? ' ' . $presidentName : '')) ?></p>
<p><?= h(__(
	'We confirm that the annual membership fee payment for club {0} has been received by the leadership and has been booked.',
	$clubName !== '' ? $clubName : __('your club')
)) ?></p>
<p><?= h(__(
	'The payment date is {0}. Your club\'s annual membership fee for {1} is settled, and membership is valid toward {2}.',
	$paymentDateFormatted,
	$membershipYear,
	$associationName
)) ?></p>
<p><?= h(__('Thank you.')) ?></p>
