<?php
/**
 * @var \App\View\AppView $this
 * @var string $memberName
 * @var string $clubName
 * @var string $loginUrl
 */
?>
<p><?= h(__('Hello{0},', $memberName !== '' ? ' ' . $memberName : '')) ?></p>
<p><?= h(__('Your membership profile details have been changed by an officer.')) ?></p>
<p><?= h(__('Club: {0}', $clubName)) ?></p>
<p>
	<a href="<?= h($loginUrl) ?>"><?= h(__('Sign in')) ?></a>
</p>
<p><?= h(__('If you did not expect this change, please contact your club or national leadership.')) ?></p>
