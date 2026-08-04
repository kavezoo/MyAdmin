<?php
/**
 * App Users — Profile (Admin layout, view-style read-only card).
 *
 * @var \App\View\AppView $this
 * @var \CakeDC\Users\Model\Entity\User $user
 * @var bool $isCurrentUser
 * @var string $countryLabel
 */
use App\Auth\AppRoles;
use CakeDC\Users\Utility\UsersUrl;

$this->assign('title', __('Profile'));
$this->Html->css(['pages/index'], ['block' => true]);

$isCurrentUser = (bool)($isCurrentUser ?? false);
$countryLabel = (string)($countryLabel ?? '');
$displayName = trim((string)($user->first_name ?? '') . ' ' . (string)($user->last_name ?? ''));
if ($displayName === '') {
	$displayName = (string)($user->username ?? $user->email ?? '');
}
$roleKey = strtolower(trim((string)($user->role ?? '')));
$roleLabel = $roleKey !== '' ? AppRoles::labeled($roleKey) : '';
// CakeDC flag only — not Users.role. Strict truthy (0 / "0" / false → no badge).
$isSuperuser = \App\Auth\CurrentUser::truthyFlag($user->get('is_superuser'));
$userAvatar = trim((string)($user->avatar ?? ''));
$dashboardUrl = $this->Url->build($this->get('panelHomeUrl') ?? [
	'prefix' => 'Admin',
	'controller' => 'Dashboard',
	'action' => 'index',
]);
$socialAccounts = $user->social_accounts ?? [];
?>
<div class="row mt-3">
	<div class="col-12 col-xxl-11 p-2 pt-0">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-user"></i> <?= __('Profile') ?></h3>
					<?= __('View the selected record (read-only).') ?>
				</div>
				<div class="float-right">
					<a role="button" href="<?= h($dashboardUrl) ?>" class="btn btn-outline-secondary" title="<?= h(__('Dashboard')) ?>">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?php if ($userAvatar !== '' || $roleLabel !== '' || $isSuperuser): ?>
					<div class="d-flex align-items-center gap-3 mb-4">
						<?php if ($userAvatar !== ''): ?>
							<?= $this->Html->image($userAvatar, [
								'alt' => $displayName,
								'class' => 'rounded-circle',
								'width' => 72,
								'height' => 72,
								'style' => 'object-fit: cover;',
							]) ?>
						<?php else: ?>
							<div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-secondary" style="width:72px;height:72px;" aria-hidden="true">
								<i class="fa fa-user fa-2x"></i>
							</div>
						<?php endif; ?>
						<div>
							<div class="fw-bold fs-5"><?= h($displayName) ?></div>
							<?php if ($roleLabel !== ''): ?>
								<div class="text-muted"><?= h($roleLabel) ?></div>
							<?php endif; ?>
							<?php if ($isSuperuser): ?>
								<div class="mt-1"><span class="badge text-bg-warning"><?= h(__('Superuser')) ?></span></div>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>

				<dl class="record-view-fields mb-0">
					<div class="record-view-row"><dt><?= __('Name') ?></dt><dd><?= h($displayName) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Email') ?></dt><dd><?= h((string)$user->email) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Username') ?></dt><dd><?= h((string)$user->username) ?></dd></div>
					<?php if ($roleLabel !== ''): ?>
						<div class="record-view-row"><dt><?= __('Role') ?></dt><dd><?= h($roleLabel) ?></dd></div>
					<?php endif; ?>
					<?php if ($isSuperuser): ?>
						<div class="record-view-row"><dt><?= __('Superuser') ?></dt><dd><?= h(__('Yes')) ?></dd></div>
					<?php endif; ?>
					<?php if ($countryLabel !== ''): ?>
						<div class="record-view-row"><dt><?= __('Country') ?></dt><dd><?= h($countryLabel) ?></dd></div>
					<?php endif; ?>
					<?php if (!empty($socialAccounts)): ?>
						<div class="record-view-row">
							<dt><?= __('Social accounts') ?></dt>
							<dd>
								<?php
								$parts = [];
								foreach ($socialAccounts as $socialAccount) {
									$label = (string)$socialAccount->provider;
									if (!empty($socialAccount->username)) {
										$label .= ' — ' . (string)$socialAccount->username;
									}
									$parts[] = h($label);
								}
								echo implode(', ', $parts);
								?>
							</dd>
						</div>
					<?php endif; ?>
				</dl>
			</div>
			<?php if ($isCurrentUser): ?>
				<div class="card-footer">
					<div class="record-view-footer-actions">
						<?= $this->Html->link(
							'<span class="btn-label"><i class="fa fa-key"></i></span>' . h(__('Change password')),
							UsersUrl::actionUrl('changePassword'),
							['escape' => false, 'class' => 'btn btn-primary']
						) ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
