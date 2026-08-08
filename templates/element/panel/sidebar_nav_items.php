<?php
/**
 * Sidebar links from PanelNav (same destinations as dashboard cards).
 *
 * @var \App\View\AppView $this
 * @var list<array<string, mixed>> $items
 */
use App\Utility\PanelNav;

$items = $items ?? [];
foreach ($items as $item) {
	$title = (string)($item['title'] ?? '');
	$url = $item['url'] ?? null;
	$icon = (string)($item['icon'] ?? 'fa-circle');
	if ($title === '' || $url === null || $url === '' || $url === []) {
		continue;
	}
	$href = is_array($url) ? $this->Url->build($url) : (string)$url;
	$active = PanelNav::isActive($item, $this->getRequest());
	?>
	<li class="submenu">
		<a href="<?= h($href) ?>"<?= $active ? ' class="active"' : '' ?>>
			<i class="fa fa-fw <?= h($icon) ?>"></i><span> <?= h($title) ?> </span>
		</a>
	</li>
	<?php
}
