<?php
/**
 * President panel sidebar.
 *
 * @var \App\View\AppView $this
 */
$controller = (string)$this->request->getParam('controller');
$isDashboard = $controller === 'Dashboard';
$home = ['prefix' => 'President', 'controller' => 'Dashboard', 'action' => 'index'];
$showEventLogs = \App\Auth\EventLogAccess::canSearch($this->request);
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
				<?php if ($showEventLogs): ?>
					<li class="submenu">
						<a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'EventLogs', 'action' => 'index']) ?>"<?= $controller === 'EventLogs' ? ' class="active"' : '' ?>>
							<i class="fa fa-fw fa-list-alt"></i><span> <?= __('Event logs') ?> </span>
						</a>
					</li>
				<?php endif; ?>
			</ul>
			<div class="clearfix"></div>
		</div>
		<div class="clearfix"></div>
	</div>
</div>
