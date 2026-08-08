<?php
/**
 * New panel sidebar.
 *
 * @var \App\View\AppView $this
 */
use App\Utility\PanelNav;

$controller = (string)$this->request->getParam('controller');
$isDashboard = $controller === 'Dashboard';
$home = ['prefix' => 'New', 'controller' => 'Dashboard', 'action' => 'index'];
$navItems = PanelNav::forPrefix('New', $this->getRequest());
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
			</ul>
			<div class="clearfix"></div>
		</div>
		<div class="clearfix"></div>
	</div>
</div>
