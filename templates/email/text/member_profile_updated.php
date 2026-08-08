<?php
/**
 * @var \App\View\AppView $this
 * @var string $memberName
 * @var string $clubName
 * @var string $loginUrl
 */
?>
<?= __('Hello{0},', $memberName !== '' ? ' ' . $memberName : '') ?>


<?= __('Your membership profile details have been changed by an officer.') ?>


<?= __('Club: {0}', $clubName) ?>


<?= __('Sign in') ?>: <?= $loginUrl ?>


<?= __('If you did not expect this change, please contact your club or national leadership.') ?>
