<?php
/**
 * Name (+ optional role) cell for user/member index tables.
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface|array|object $user
 * @var string $displayName
 * @var int $size Avatar size (px)
 * @var bool $showRole
 * @var bool $isCurrentUser Highlight logged-in user (“You”)
 */
use App\Auth\AppRoles;

$size = (int)($size ?? 40);
$showRole = (bool)($showRole ?? true);
$isCurrentUser = (bool)($isCurrentUser ?? false);
$displayName = (string)($displayName ?? '');
$roleKey = '';
if (is_object($user) && method_exists($user, 'get')) {
	$roleKey = strtolower(trim((string)$user->get('role')));
} elseif (is_object($user) && isset($user->role)) {
	$roleKey = strtolower(trim((string)$user->role));
} elseif (is_array($user)) {
	$roleKey = strtolower(trim((string)($user['role'] ?? '')));
}
$roleLabel = ($showRole && $roleKey !== '') ? AppRoles::label($roleKey) : '';
?>
<div class="d-flex align-items-center gap-2">
	<?= $this->element('users/list_avatar', [
		'user' => $user,
		'displayName' => $displayName,
		'size' => $size,
	]) ?>
	<div class="users-list-name-cell__text">
		<span class="users-list-name-cell__label">
			<?= h($displayName) ?>
			<?php if ($isCurrentUser): ?>
				<span class="badge text-bg-primary ms-1"><?= __('You') ?></span>
			<?php endif; ?>
		</span>
		<?php if ($roleLabel !== ''): ?>
			<span class="users-list-name-cell__role"><?= h($roleLabel) ?></span>
		<?php endif; ?>
	</div>
</div>
