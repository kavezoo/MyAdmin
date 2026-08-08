<?php
/**
 * Member panel sidebar.
 *
 * @var \App\View\AppView $this
 */
use App\Utility\PanelNav;

$controller = (string)$this->request->getParam('controller');
$isDashboard = $controller === 'Dashboard';
$home = ['prefix' => 'Member', 'controller' => 'Dashboard', 'action' => 'index'];
$navItems = PanelNav::forPrefix('Member', $this->getRequest());
?>
<div class="left main-sidebar">
	<div class="sidebar-inner leftscroll">
		<div id="sidebar-menu">
			<ul>
				<li class="submenu">
					<a href="<?= $this->Url->build($home) ?>"<?= $isDashboard ? ' class="active"' : '' ?>>
						<i class="fa fa-fw fa-tachometer"></i><span> <?= __('Dashboard') ?> </span>
					</a>
				</li>
				<?= $this->element('panel/sidebar_nav_items', ['items' => $navItems]) ?>
				<?= $this->element('panel/switcher') ?>
			</ul>
			<div class="clearfix"></div>
		</div>
		<div class="clearfix"></div>
	</div>
</div>
