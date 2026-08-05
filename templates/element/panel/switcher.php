<?php
/**
 * Links to other role panels (step up / step down).
 *
 * @var \App\View\AppView $this
 * @var list<array{prefix: string, label: string, url: array<string, mixed>, direction: string}> $panelSwitcherLinks
 */
$panelSwitcherLinks = $panelSwitcherLinks ?? [];
if ($panelSwitcherLinks === []) {
	return;
}

$downStarted = false;
foreach ($panelSwitcherLinks as $link) {
	$isUp = ($link['direction'] ?? '') === 'up';
	if (!$isUp && !$downStarted) {
		$downStarted = true;
		echo '<li class="panel-switcher-divider" aria-hidden="true"></li>';
	}
	$icon = $isUp ? 'fa-arrow-circle-up' : 'fa-arrow-circle-down';
	?>
	<li class="submenu panel-switcher-item">
		<a href="<?= $this->Url->build($link['url']) ?>">
			<i class="fa fa-fw <?= h($icon) ?>"></i><span> <?= h($link['label']) ?></span>
		</a>
	</li>
	<?php
}
