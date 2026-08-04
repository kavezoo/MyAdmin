<?php
/**
 * @var \App\View\AppView $this
 * @var string $memberName
 * @var string $clubName
 * @var string $loginUrl
 */
?>
<p><?= h(__('Hello{0},', $memberName !== '' ? ' ' . $memberName : '')) ?></p>
<p><?= h(__('The club president has approved your membership in {0}.', $clubName)) ?></p>
<p><?= h(__('You are now a full member and can sign in to the site.')) ?></p>
<p>
	<a href="<?= h($loginUrl) ?>"><?= h(__('Sign in')) ?></a>
</p>
