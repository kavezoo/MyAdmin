<?php
/**
 * Club president panel sidebar.
 *
 * @var \App\View\AppView $this
 */
$controller = (string)$this->request->getParam('controller');
$isDashboard = $controller === 'Dashboard';
$isApplicants = $controller === 'Applicants';
$home = ['prefix' => 'Clubpresident', 'controller' => 'Dashboard', 'action' => 'index'];
$applicantsUrl = ['prefix' => 'Clubpresident', 'controller' => 'Applicants', 'action' => 'index'];
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
					<a href="<?= $this->Url->build($applicantsUrl) ?>"<?= $isApplicants ? ' class="active"' : '' ?>>
						<i class="fa fa-fw fa-user-plus"></i><span> <?= __('Applicants') ?> </span>
					</a>
				</li>
			</ul>
			<div class="clearfix"></div>
		</div>
		<div class="clearfix"></div>
	</div>
</div>
