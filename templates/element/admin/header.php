<?php
/**
 * Panel top bar (Admin + role prefixes).
 *
 * @var \App\View\AppView $this
 */
use App\Auth\AppRoles;
use App\Auth\CurrentUser;
use App\Auth\MembershipProfile;

$panelHomeUrl = $this->get('panelHomeUrl') ?? [
	'prefix' => 'Admin',
	'controller' => 'Dashboard',
	'action' => 'index',
];
$panelBrand = (string)($this->get('panelBrand') ?? __('Admin'));

$sessionName = '';
$sessionRoleLabel = '';
$identity = $this->request->getAttribute('identity');
if ($identity !== null) {
	$sessionName = MembershipProfile::displayName($identity);
	if ($sessionName === '') {
		$sessionName = trim((string)(
			$identity->get('username')
			?: $identity->get('email')
			?: ''
		));
	}
	$role = CurrentUser::role($this->request);
	$sessionRoleLabel = AppRoles::label($role);
	if (CurrentUser::isSuperuser($this->request) && $role !== AppRoles::SUPERUSER) {
		$sessionRoleLabel .= ' · ' . __('Superuser');
	}
}
?>
<!-- top bar navigation -->
<div class="headerbar">

	<div class="headerbar-left">
		<a href="<?= $this->Url->build($panelHomeUrl) ?>" class="logo">
			<img alt="Logo" src="<?= $this->Url->image('logo.png') ?>" />
			<span><?= h($panelBrand) ?></span>
		</a>
	</div>

	<nav class="navbar-custom">

		<ul class="list-inline float-right mb-0">
			<?= $this->element('admin/header_search') ?>
			<?php
			/*
			 * Temporary: question / envelope / bell header dropdowns hidden until wired up.
			 * <?= $this->element('admin/header_help') ?>
			 * <?= $this->element('admin/header_messages') ?>
			 * <?= $this->element('admin/header_alerts') ?>
			 */
			?>
			<?= $this->element('admin/header_profile') ?>
		</ul>

		<ul class="list-inline menu-left mb-0">
			<li class="float-left header-session">
				<button type="button" class="button-menu-mobile open-left" aria-label="<?= h(__('Menu')) ?>">
					<i class="fa fa-fw fa-bars"></i>
				</button>
				<?php if ($sessionName !== '' || $sessionRoleLabel !== ''): ?>
					<span class="header-session-info" title="<?= h(trim($sessionName . ' — ' . $sessionRoleLabel, ' —')) ?>">
						<?php if ($sessionName !== ''): ?>
							<span class="header-session-name"><?= h($sessionName) ?></span>
						<?php endif; ?>
						<?php if ($sessionRoleLabel !== ''): ?>
							<span class="header-session-role"><?= h($sessionRoleLabel) ?></span>
						<?php endif; ?>
					</span>
				<?php endif; ?>
			</li>
		</ul>

	</nav>

</div>
<!-- End Navigation -->
