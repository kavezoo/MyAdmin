<?php
/**
 * Links to other role panels — one collapsible submenu (all prefixes).
 *
 * @var \App\View\AppView $this
 * @var list<array{prefix: string, label: string, url: array<string, mixed>, direction: string}> $panelSwitcherLinks
 */
$panelSwitcherLinks = $panelSwitcherLinks ?? [];
if ($panelSwitcherLinks === []) {
	return;
}
?>
<li class="submenu panel-switcher">
	<a href="#">
		<i class="fa fa-fw fa-exchange"></i><span> <?= __('Role switch') ?> </span><span class="menu-arrow"></span>
	</a>
	<ul class="list-unstyled">
		<?php
		$prevDirection = null;
		foreach ($panelSwitcherLinks as $link):
			$direction = ($link['direction'] ?? '') === 'up' ? 'up' : 'down';
			if ($prevDirection === 'up' && $direction === 'down') {
				echo '<li class="panel-switcher-divider" aria-hidden="true"></li>';
			}
			$prevDirection = $direction;
			$icon = $direction === 'up' ? 'fa-arrow-circle-up' : 'fa-arrow-circle-down';
			?>
			<li class="panel-switcher-item">
				<a href="<?= $this->Url->build($link['url']) ?>">
					<i class="fa fa-fw <?= h($icon) ?>"></i><?= h($link['label']) ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</li>
