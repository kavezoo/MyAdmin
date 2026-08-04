<?php
/**
 * New panel sidebar.
 *
 * @var \App\View\AppView $this
 */
$controller = (string)$this->request->getParam('controller');
$isDashboard = $controller === 'Dashboard';
$home = ['prefix' => 'New', 'controller' => 'Dashboard', 'action' => 'index'];
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
			</ul>
			<div class="clearfix"></div>
		</div>
		<div class="clearfix"></div>
	</div>
</div>
