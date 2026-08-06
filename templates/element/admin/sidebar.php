<?php
/**
 * @var \App\View\AppView $this
 */
use App\Auth\EventLogAccess;
use App\Auth\LanguageAccess;
use App\Auth\SetupAccess;

$controller = (string)$this->request->getParam('controller');
$isLanguages = $controller === 'Languages';
$isCountries = $controller === 'Countries';
$isSetups = $controller === 'Setups';
$isEventLogs = $controller === 'EventLogs';
$isDashboard = $controller === 'Dashboard';
$showSetupsMenu = SetupAccess::canAccessModule($this->request);
$showLanguagesMenu = LanguageAccess::canAccessModule($this->request);
$showEventLogsMenu = EventLogAccess::canSearch($this->request);
?>
<!-- Left Sidebar -->
<div class="left main-sidebar">
	<div class="sidebar-inner leftscroll">
		<div id="sidebar-menu">
			<ul>
				<li class="submenu">
					<a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index']) ?>"<?= $isDashboard ? ' class="active"' : '' ?>>
						<i class="fa fa-fw fa-tachometer"></i><span> <?= __('Dashboard') ?> </span>
					</a>
				</li>

				<li class="submenu">
					<a href="#"<?= (($showSetupsMenu && $isSetups) || ($showLanguagesMenu && $isLanguages) || $isCountries || ($showEventLogsMenu && $isEventLogs)) ? ' class="active"' : '' ?>>
						<i class="fa fa-fw fa-cogs"></i> <span> <?= __('Settings') ?> </span> <span class="menu-arrow"></span>
					</a>
					<ul class="list-unstyled">
						<?php if ($showSetupsMenu): ?>
							<li<?= $isSetups ? ' class="active"' : '' ?>>
								<a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Setups', 'action' => 'index']) ?>"><?= __('Setups') ?></a>
							</li>
						<?php endif; ?>
						<?php if ($showLanguagesMenu): ?>
							<li<?= $isLanguages ? ' class="active"' : '' ?>>
								<a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Languages', 'action' => 'index']) ?>"><?= __('Languages') ?></a>
							</li>
						<?php endif; ?>
						<li<?= $isCountries ? ' class="active"' : '' ?>>
							<a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Countries', 'action' => 'index']) ?>"><?= __('Countries') ?></a>
						</li>
						<?php if ($showEventLogsMenu): ?>
							<li<?= $isEventLogs ? ' class="active"' : '' ?>>
								<a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'EventLogs', 'action' => 'index']) ?>"><?= __('Event logs') ?></a>
							</li>
						<?php endif; ?>
					</ul>
				</li>
				<?= $this->element('panel/switcher') ?>
			</ul>
			<div class="clearfix"></div>
		</div>
		<div class="clearfix"></div>
	</div>
</div>
<!-- End Sidebar -->
