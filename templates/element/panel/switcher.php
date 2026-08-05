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

$currentPrefix = strtolower((string)$this->request->getParam('prefix'));
$upLinks = [];
$downLinks = [];
foreach ($panelSwitcherLinks as $link) {
	if (($link['direction'] ?? '') === 'up') {
		$upLinks[] = $link;
	} else {
		$downLinks[] = $link;
	}
}
$downSectionTitle = $currentPrefix === 'admin'
	? __('Role panels')
	: __('Member area');
?>
<?php if ($upLinks !== []): ?>
	<li class="submenu panel-switcher-heading">
		<span class="panel-switcher-title text-muted small px-3 py-2 d-block">
			<i class="fa fa-fw fa-level-up" aria-hidden="true"></i> <?= __('Officer panels') ?>
		</span>
	</li>
	<?php foreach ($upLinks as $link): ?>
		<li class="submenu">
			<a href="<?= $this->Url->build($link['url']) ?>">
				<i class="fa fa-fw fa-arrow-circle-up"></i><span> <?= h($link['label']) ?></span>
			</a>
		</li>
	<?php endforeach; ?>
<?php endif; ?>
<?php if ($downLinks !== []): ?>
	<li class="submenu panel-switcher-heading">
		<span class="panel-switcher-title text-muted small px-3 py-2 d-block">
			<i class="fa fa-fw fa-level-down" aria-hidden="true"></i> <?= h($downSectionTitle) ?>
		</span>
	</li>
	<?php foreach ($downLinks as $link): ?>
		<li class="submenu">
			<a href="<?= $this->Url->build($link['url']) ?>">
				<i class="fa fa-fw fa-arrow-circle-down"></i><span> <?= h($link['label']) ?></span>
			</a>
		</li>
	<?php endforeach; ?>
<?php endif; ?>
