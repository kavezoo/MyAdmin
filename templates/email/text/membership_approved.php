<?php
/**
 * @var \App\View\AppView $this
 * @var string $memberName
 * @var string $clubName
 * @var string $loginUrl
 */
?>
<?= __('Hello{0},', $memberName !== '' ? ' ' . $memberName : '') ?>


<?= __('The club president has approved your membership in {0}.', $clubName) ?>


<?= __('You are now a full member and can sign in to the site.') ?>


<?= __('Sign in') ?>: <?= $loginUrl ?>
