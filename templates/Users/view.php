<?php
/**
 * App Users — Profile view (read-only data sheet).
 *
 * @var \App\View\AppView $this
 * @var \CakeDC\Users\Model\Entity\User $user
 * @var bool $isCurrentUser
 * @var bool $canEditProfile
 * @var bool $needsProfileCompletion
 * @var string $countryLabel
 * @var int $membershipYear
 * @var string $clubFeeLabel
 * @var string $nationalFeeLabel
 * @var bool $clubFeePaid
 * @var bool $nationalFeePaid
 * @var string $clubFeeDateFormatted
 * @var string $nationalFeeDateFormatted
 */
use App\Auth\AppRoles;
use App\Auth\MembershipProfile;
use App\Utility\UserAvatar;
use CakeDC\Users\Utility\UsersUrl;

$this->assign('title', __('Profile'));
$this->Html->css([
	'pages/index',
	'pages/users_profile',
	'pages/membership_fee',
], ['block' => true]);

$isCurrentUser = (bool)($isCurrentUser ?? false);
$canEditProfile = (bool)($canEditProfile ?? false);
$needsProfileCompletion = (bool)($needsProfileCompletion ?? false);
$countryLabel = (string)($countryLabel ?? '');

$displayName = MembershipProfile::displayName($user);
if ($displayName === '') {
	$displayName = (string)($user->username ?? $user->email ?? '');
}
$roleKey = strtolower(trim((string)($user->role ?? '')));
$roleLabel = $roleKey !== '' ? AppRoles::labeled($roleKey) : '';
$isSuperuser = \App\Auth\CurrentUser::truthyFlag($user->get('is_superuser'));
$userIdStr = trim((string)($user->id ?? ''));
$avatarPath = $userIdStr !== '' ? UserAvatar::displayPath($userIdStr, (string)($user->avatar ?? '')) : '';
$avatarUrl = $avatarPath !== '' ? $this->Url->build(UserAvatar::publicUrlFor($userIdStr, (string)($user->avatar ?? ''))) : '';
$clubLabel = '';
if (!empty($user->club) && !empty($user->club->name)) {
	$clubLabel = (string)$user->club->name;
}
$dashboardUrl = $this->Url->build($this->get('panelHomeUrl') ?? [
	'prefix' => 'Admin',
	'controller' => 'Dashboard',
	'action' => 'index',
]);
$editUrl = $this->Url->build($this->get('breadcrumbEditUrl') ?? UsersUrl::actionUrl('edit'));
$socialAccounts = $user->social_accounts ?? [];

$membershipYear = (int)($membershipYear ?? \App\Utility\MembershipFee::currentYear());
$clubFeeLabel = (string)($clubFeeLabel ?? '');
$nationalFeeLabel = (string)($nationalFeeLabel ?? '');
$clubFeePaid = (bool)($clubFeePaid ?? false);
$nationalFeePaid = (bool)($nationalFeePaid ?? false);
$clubFeeDateFormatted = (string)($clubFeeDateFormatted ?? '');
$nationalFeeDateFormatted = (string)($nationalFeeDateFormatted ?? '');
?>
<div class="row mt-3">
	<div class="col-12 col-lg-10 col-xxl-9 p-2 pt-0">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-user"></i> <?= __('Profile') ?></h3>
					<?php if ($needsProfileCompletion): ?>
						<?= __('Complete your profile first to apply for membership.') ?>
					<?php else: ?>
						<?= __('View the selected record (read-only).') ?>
					<?php endif; ?>
				</div>
				<div class="float-right">
					<a role="button" href="<?= h($dashboardUrl) ?>" class="btn btn-outline-secondary" title="<?= h(__('Dashboard')) ?>">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>

			<div class="card-body">
				<?php if ($needsProfileCompletion): ?>
					<div class="alert alert-warning mb-4">
						<?= $this->Html->link(
							__('Complete your profile'),
							UsersUrl::actionUrl('completeProfile'),
							['class' => 'alert-link']
						) ?>
					</div>
				<?php endif; ?>

				<?php if ($avatarUrl !== '' || $roleLabel !== '' || $isSuperuser): ?>
					<div class="d-flex align-items-center gap-3 mb-4">
						<?php if ($avatarUrl !== ''): ?>
							<img src="<?= h($avatarUrl) ?>" alt="<?= h($displayName) ?>" class="rounded-circle users-profile-avatar-preview" width="72" height="72">
						<?php else: ?>
							<div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-secondary users-profile-avatar-placeholder-sm" aria-hidden="true">
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

				<div class="membership-fee-panel mb-4">
					<div class="membership-fee-panel__title"><?= __('Membership fees ({0})', $membershipYear) ?></div>
					<?= $this->element('users/membership_fee_status', [
						'label' => $clubFeeLabel,
						'paid' => $clubFeePaid,
						'membershipYear' => $membershipYear,
						'dateFormatted' => $clubFeeDateFormatted,
						'mode' => 'profile',
					]) ?>
					<?= $this->element('users/membership_fee_status', [
						'label' => $nationalFeeLabel,
						'paid' => $nationalFeePaid,
						'membershipYear' => $membershipYear,
						'dateFormatted' => $nationalFeeDateFormatted,
						'mode' => 'profile',
					]) ?>
				</div>

				<dl class="record-view-fields mb-0">
					<div class="record-view-row"><dt><?= __('Name') ?></dt><dd><?= h($displayName) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Email') ?></dt><dd><?= h((string)$user->email) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Username') ?></dt><dd><?= h((string)$user->username) ?></dd></div>
					<?php if ((string)($user->phone ?? '') !== ''): ?>
						<div class="record-view-row"><dt><?= __('Phone') ?></dt><dd><?= h((string)$user->phone) ?></dd></div>
					<?php endif; ?>
					<?php if ($roleLabel !== ''): ?>
						<div class="record-view-row"><dt><?= __('Role') ?></dt><dd><?= h($roleLabel) ?></dd></div>
					<?php endif; ?>
					<?php if ($isSuperuser): ?>
						<div class="record-view-row"><dt><?= __('Superuser') ?></dt><dd><?= h(__('Yes')) ?></dd></div>
					<?php endif; ?>
					<?php if ($countryLabel !== ''): ?>
						<div class="record-view-row"><dt><?= __('Country') ?></dt><dd><?= h($countryLabel) ?></dd></div>
					<?php endif; ?>
					<?php if ($clubLabel !== ''): ?>
						<div class="record-view-row"><dt><?= __('Club') ?></dt><dd><?= h($clubLabel) ?></dd></div>
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
						<?php if ($canEditProfile): ?>
							<?= $this->Html->link(
								'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . h(__('Edit')),
								$editUrl,
								['escape' => false, 'class' => 'btn btn-primary']
							) ?>
						<?php endif; ?>
						<?= $this->Html->link(
							'<span class="btn-label"><i class="fa fa-key"></i></span>' . h(__('Change password')),
							UsersUrl::actionUrl('changePassword'),
							['escape' => false, 'class' => $canEditProfile ? 'btn btn-outline-secondary' : 'btn btn-primary']
						) ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
