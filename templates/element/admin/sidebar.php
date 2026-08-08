<?php
/**
 * @var \App\View\AppView $this
 */
use App\Utility\PanelNav;

$controller = (string)$this->request->getParam('controller');
$isDashboard = $controller === 'Dashboard';
$navItems = PanelNav::forPrefix('Admin', $this->getRequest());
$mainItems = PanelNav::itemsInGroup($navItems, PanelNav::NAV_GROUP_MAIN);
$settingsItems = PanelNav::itemsInGroup($navItems, PanelNav::NAV_GROUP_SETTINGS);
$settingsActive = false;
foreach ($settingsItems as $item) {
	if (PanelNav::isActive($item, $this->getRequest())) {
		$settingsActive = true;
		break;
	}
}
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

				<?= $this->element('panel/sidebar_nav_items', ['items' => $mainItems]) ?>

				<?php if ($settingsItems !== []): ?>
					<li class="submenu">
						<a href="#"<?= $settingsActive ? ' class="active"' : '' ?>>
							<i class="fa fa-fw fa-cogs"></i> <span> <?= __('Settings') ?> </span> <span class="menu-arrow"></span>
						</a>
						<ul class="list-unstyled">
							<?php foreach ($settingsItems as $item):
								$title = (string)($item['title'] ?? '');
								$url = $item['url'] ?? null;
								if ($title === '' || $url === null || $url === [] || $url === '') {
									continue;
								}
								$href = is_array($url) ? $this->Url->build($url) : (string)$url;
								$active = PanelNav::isActive($item, $this->getRequest());
								?>
								<li<?= $active ? ' class="active"' : '' ?>>
									<a href="<?= h($href) ?>"><?= h($title) ?></a>
								</li>
							<?php endforeach; ?>
						</ul>
					</li>
				<?php endif; ?>
				<?= $this->element('panel/switcher') ?>
			</ul>
			<div class="clearfix"></div>
		</div>
		<div class="clearfix"></div>
	</div>
</div>
<!-- End Sidebar -->
