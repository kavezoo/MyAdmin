<?php
/**
 * @var \App\View\AppView $this
 */
use App\Auth\CountryAccess;
use App\Auth\EventLogAccess;
use App\Auth\LanguageAccess;
use App\Auth\SetupAccess;

$this->assign('title', __('Dashboard'));

$cards = [];

if (SetupAccess::canAccessModule($this->request)) {
	$cards[] = [
		'title' => __('Setups'),
		'text' => __('Country settings and EAV setup values for the working country.'),
		'url' => ['prefix' => 'Admin', 'controller' => 'Setups', 'action' => 'index'],
		'button' => __('Go to Setups'),
		'btnClass' => 'btn-outline-primary',
		'icon' => 'fa-cogs',
	];
}
if (LanguageAccess::canAccessModule($this->request)) {
	$cards[] = [
		'title' => __('Languages'),
		'text' => __('Manage UI languages for the login selector: code, name, endonym and visibility.'),
		'url' => ['prefix' => 'Admin', 'controller' => 'Languages', 'action' => 'index'],
		'button' => __('Go to Languages'),
		'btnClass' => 'btn-outline-primary',
		'icon' => 'fa-language',
	];
}
if (CountryAccess::canAccessModule($this->request)) {
	$cards[] = [
		'title' => __('Countries'),
		'text' => __('View and edit countries, visibility and related settings.'),
		'url' => ['prefix' => 'Admin', 'controller' => 'Countries', 'action' => 'index'],
		'button' => __('Go to Countries'),
		'btnClass' => 'btn-outline-primary',
		'icon' => 'fa-globe',
	];
}
if (EventLogAccess::canSearch($this->request)) {
	$cards[] = [
		'title' => __('Event logs'),
		'text' => __('Search the activity / event log for this country.'),
		'url' => ['prefix' => 'Admin', 'controller' => 'EventLogs', 'action' => 'index'],
		'button' => __('Go to Event logs'),
		'btnClass' => 'btn-outline-primary',
		'icon' => 'fa-list-alt',
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
				<p class="mb-3"><?= __('Welcome to MyAdmin. Choose a module below — each card describes where the button will take you.') ?></p>
				<?= $this->element('panel/dashboard_nav_cards', ['cards' => $cards]) ?>
			</div>
		</div>
	</div>
</div>
