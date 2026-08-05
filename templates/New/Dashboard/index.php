<?php
/**
 * New panel — Dashboard.
 *
 * @var \App\View\AppView $this
 * @var bool $pending
 */
use CakeDC\Users\Utility\UsersUrl;

$pending = (bool)($pending ?? false);
$this->assign('title', __('Dashboard'));

$cards = [];
if ($pending) {
	$cards[] = [
		'title' => __('Profile'),
		'text' => __('View the profile you submitted. Your club president has been notified and will review your application.'),
		'url' => UsersUrl::actionUrl('profile'),
		'button' => __('Go to Profile'),
		'btnClass' => 'btn-primary',
		'icon' => 'fa-user',
	];
} else {
	$cards[] = [
		'title' => __('Complete your profile'),
		'text' => __('Fill in the required profile fields and choose your club to submit your membership application.'),
		'url' => UsersUrl::actionUrl('completeProfile'),
		'button' => __('Go to Complete profile'),
		'btnClass' => 'btn-primary',
		'icon' => 'fa-id-card',
	];
	$cards[] = [
		'title' => __('Profile'),
		'text' => __('Open your profile page (view-only overview of your account).'),
		'url' => UsersUrl::actionUrl('profile'),
		'button' => __('Go to Profile'),
		'btnClass' => 'btn-outline-primary',
		'icon' => 'fa-user',
	];
}
?>
<div class="row">
	<div class="col-12 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<h3 class="fw-bold"><i class="fa fa-tachometer"></i> <?= __('Dashboard') ?></h3>
			</div>
			<div class="card-body">
				<?php if ($pending): ?>
					<div class="alert alert-info mb-3">
						<h5 class="alert-heading"><?= __('Application submitted') ?></h5>
						<p class="mb-0">
							<?= __('Your profile is complete. The club president has been notified and will review your membership application. You will receive an email when you are approved.') ?>
						</p>
					</div>
				<?php else: ?>
					<p class="mb-3"><?= __('Welcome. Please complete your profile to apply for membership. Choose a destination below.') ?></p>
				<?php endif; ?>
				<?= $this->element('panel/dashboard_nav_cards', ['cards' => $cards]) ?>
			</div>
		</div>
	</div>
</div>
