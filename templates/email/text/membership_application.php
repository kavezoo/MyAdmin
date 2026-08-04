<?php
/**
 * @var \App\View\AppView $this
 * @var string $applicantName
 * @var string $applicantEmail
 * @var string $clubName
 * @var string $listUrl
 * @var string $presidentName
 */
?>
<?= __('Hello{0},', $presidentName !== '' ? ' ' . $presidentName : '') ?>


<?= __('A new user has completed their profile and applied for membership in {0}.', $clubName) ?>


<?= __('Name') ?>: <?= $applicantName ?>

<?= __('Email') ?>: <?= $applicantEmail ?>

<?= __('Club') ?>: <?= $clubName ?>


<?= __('Open applicants list') ?>: <?= $listUrl ?>


<?= __('Please review the application and approve membership if appropriate.') ?>
