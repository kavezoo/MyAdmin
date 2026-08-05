<?php
/**
 * User row avatar — photo or placeholder (member / applicant lists).
 *
 * @var \App\View\AppView $this
 * @var \CakeDC\Users\Model\Entity\User|null $user
 * @var string $userId
 * @var string|null $avatar Stored avatar path
 * @var string $displayName Alt text
 * @var int $size Pixel size (width/height)
 */
use App\Utility\UserAvatar;

$user = $user ?? null;
$userId = trim((string)($userId ?? ''));
if ($user !== null && $userId === '') {
	$userId = trim((string)($user->id ?? ''));
}
$avatarStored = $avatar ?? ($user !== null ? (string)($user->get('avatar') ?? '') : '');
$displayName = trim((string)($displayName ?? ''));
if ($displayName === '' && $user !== null) {
	$displayName = trim((string)($user->get('first_name') ?? ''));
}
$size = (int)($size ?? 40);
if ($size < 24) {
	$size = 24;
}

$avatarUrl = '';
if ($userId !== '') {
	$publicUrl = UserAvatar::publicUrlFor($userId, $avatarStored);
	if ($publicUrl !== '') {
		$avatarUrl = $this->Url->build($publicUrl);
	}
}
?>
<div class="users-list-avatar" style="--users-list-avatar-size: <?= (int)$size ?>px;">
	<?php if ($avatarUrl !== ''): ?>
		<img
			src="<?= h($avatarUrl) ?>"
			alt="<?= h($displayName !== '' ? $displayName : __('Profile picture')) ?>"
			class="users-list-avatar__img rounded-circle"
			width="<?= (int)$size ?>"
			height="<?= (int)$size ?>"
			loading="lazy"
		>
	<?php else: ?>
		<div class="users-list-avatar__placeholder rounded-circle d-flex align-items-center justify-content-center text-secondary" aria-hidden="true">
			<i class="fa fa-user"></i>
		</div>
	<?php endif; ?>
</div>
