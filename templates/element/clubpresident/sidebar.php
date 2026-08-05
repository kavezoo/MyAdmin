<?php
/**
 * Club president panel sidebar.
 *
 * @var \App\View\AppView $this
 */
$controller = (string)$this->request->getParam('controller');
$isDashboard = $controller === 'Dashboard';
$isMembers = $controller === 'Members' || $controller === 'Applicants';
$home = ['prefix' => 'Clubpresident', 'controller' => 'Dashboard', 'action' => 'index'];
$membersUrl = ['prefix' => 'Clubpresident', 'controller' => 'Members', 'action' => 'index'];
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
				<li class="submenu">
					<a href="<?= $this->Url->build($membersUrl) ?>"<?= $isMembers ? ' class="active"' : '' ?>>
						<i class="fa fa-fw fa-users"></i><span> <?= __('Members') ?> </span>
					</a>
				</li>
				<?= $this->element('panel/switcher') ?>
			</ul>
			<div class="clearfix"></div>
		</div>
		<div class="clearfix"></div>
	</div>
</div>
