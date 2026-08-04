<?php
/**
 * Admin header profile dropdown (CakeDC Users logout / profile).
 *
 * @var \App\View\AppView $this
 */
use CakeDC\Users\Utility\UsersUrl;

$identity = $this->request->getAttribute('identity');
$displayName = 'admin';
if ($identity !== null) {
	$displayName = (string)(
		$identity->get('first_name')
		?: $identity->get('username')
		?: $identity->get('email')
		?: 'admin'
	);
}
?>
			<li class="list-inline-item dropdown notif">
				<a class="nav-link dropdown-toggle nav-user" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
					<img src="<?= $this->Url->image('avatars/admin.png') ?>" alt="<?= h(__('Profile picture')) ?>" class="avatar-rounded">
				</a>
				<div class="dropdown-menu dropdown-menu-right profile-dropdown border border-1 border-secondary">
					<div class="dropdown-item noti-title profile-dropdown-user">
						<h5 class="profile-dropdown-signed-in"><small><?= h(__('Logged in: {0}', $displayName)) ?></small></h5>
					</div>
					<?= $this->Html->link(
						'<i class="fa fa-fw fa-user"></i> ' . h(__('Profile')),
						UsersUrl::actionUrl('profile'),
						['escape' => false, 'class' => 'dropdown-item notify-item']
					) ?>
					<?= $this->Html->link(
						'<i class="fa fa-fw fa-key"></i> ' . h(__('Change password')),
						UsersUrl::actionUrl('changePassword'),
						['escape' => false, 'class' => 'dropdown-item notify-item']
					) ?>
					<?= $this->Html->link(
						'<i class="fa fa-fw fa-power-off"></i> ' . h(__('Log out')),
						UsersUrl::actionUrl('logout'),
						['escape' => false, 'class' => 'dropdown-item notify-item']
					) ?>
				</div>
			</li>
