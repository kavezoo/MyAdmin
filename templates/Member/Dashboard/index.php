<?php
/**
 * Member panel — Dashboard.
 *
 * @var \App\View\AppView $this
 */
use CakeDC\Users\Utility\UsersUrl;

$this->assign('title', __('Dashboard'));

$cards = [
	[
		'title' => __('Profile'),
		'text' => __('View your membership profile: personal data, club, and membership fee status. Edit is available from the profile page.'),
		'url' => UsersUrl::actionUrl('profile'),
		'button' => __('Go to Profile'),
		'btnClass' => 'btn-primary',
		'icon' => 'fa-user',
	],
];
?>
<div class="row">
	<div class="col-12 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<h3 class="fw-bold"><i class="fa fa-tachometer"></i> <?= __('Dashboard') ?></h3>
			</div>
			<div class="card-body">
				<?= $this->element('panel/club_fee_unpaid_alert') ?>
				<p class="mb-3"><?= __('Welcome to the Member panel. Choose where you want to go — each card explains the destination.') ?></p>
				<?= $this->element('panel/dashboard_nav_cards', ['cards' => $cards]) ?>
			</div>
		</div>
	</div>
</div>
