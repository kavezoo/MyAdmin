<?php
/**
 * New panel sidebar.
 *
 * @var \App\View\AppView $this
 */
use CakeDC\Users\Utility\UsersUrl;

$controller = (string)$this->request->getParam('controller');
$isDashboard = $controller === 'Dashboard';
$isProfile = $controller === 'Users' && (string)$this->request->getParam('action') === 'profile';
$home = ['prefix' => 'New', 'controller' => 'Dashboard', 'action' => 'index'];
$profileUrl = UsersUrl::actionUrl('profile');
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
					<a href="<?= $this->Url->build($profileUrl) ?>"<?= $isProfile ? ' class="active"' : '' ?>>
						<i class="fa fa-fw fa-user"></i><span> <?= __('Profile') ?> </span>
					</a>
				</li>
			</ul>
			<div class="clearfix"></div>
		</div>
		<div class="clearfix"></div>
	</div>
</div>
