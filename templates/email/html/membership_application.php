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
<p><?= h(__('Hello{0},', $presidentName !== '' ? ' ' . $presidentName : '')) ?></p>
<p><?= h(__('A new user has completed their profile and applied for membership in {0}.', $clubName)) ?></p>
<ul>
	<li><?= h(__('Name')) ?>: <strong><?= h($applicantName) ?></strong></li>
	<li><?= h(__('Email')) ?>: <strong><?= h($applicantEmail) ?></strong></li>
	<li><?= h(__('Club')) ?>: <strong><?= h($clubName) ?></strong></li>
</ul>
<p>
	<a href="<?= h($listUrl) ?>"><?= h(__('Open members list')) ?></a>
</p>
<p><?= h(__('Please review the application and approve membership if appropriate.')) ?></p>
