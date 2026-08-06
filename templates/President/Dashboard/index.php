<?php
/**
 * President panel — Dashboard.
 *
 * @var \App\View\AppView $this
 */
$this->assign('title', __('Dashboard'));

$cards = [
	[
		'title' => __('Members'),
		'text' => __('Open the list of members in your country: national membership fees, enable/disable accounts, and member details.'),
		'url' => ['prefix' => 'President', 'controller' => 'Members', 'action' => 'index'],
		'button' => __('Go to Members'),
		'btnClass' => 'btn-primary',
		'icon' => 'fa-users',
	],
	[
		'title' => __('Clubs'),
		'text' => __('Manage clubs in your country: create and edit clubs, assign club presidents, visibility and position.'),
		'url' => ['prefix' => 'President', 'controller' => 'Clubs', 'action' => 'index'],
		'button' => __('Go to Clubs'),
		'btnClass' => 'btn-primary',
		'icon' => 'fa-sitemap',
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
				<p class="mb-3"><?= __('Welcome to the President panel. Choose where you want to go — each card explains the destination.') ?></p>
				<?= $this->element('panel/dashboard_nav_cards', ['cards' => $cards]) ?>
			</div>
		</div>
	</div>
</div>
