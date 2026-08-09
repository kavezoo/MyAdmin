<?php
/**
 * Check-in panel sidebar — home is the applicants list.
 *
 * @var \App\View\AppView $this
 */
use App\Utility\PanelNav;

$navItems = PanelNav::forPrefix('Checkin', $this->getRequest());
?>
<div class="left main-sidebar">
	<div class="sidebar-inner leftscroll">
		<div id="sidebar-menu">
			<ul>
				<?= $this->element('panel/sidebar_nav_items', ['items' => $navItems]) ?>
				<?= $this->element('panel/switcher') ?>
			</ul>
			<div class="clearfix"></div>
		</div>
		<div class="clearfix"></div>
	</div>
</div>
