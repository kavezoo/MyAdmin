<?php
/**
 * Dashboard navigation cards — title, description, button (where it goes).
 *
 * @var \App\View\AppView $this
 * @var list<array{
 *   title: string,
 *   text: string,
 *   url: array<string, mixed>|string,
 *   button?: string,
 *   btnClass?: string,
 *   icon?: string
 * }> $cards
 */
$cards = $cards ?? [];
if ($cards === []) {
	return;
}
?>
<div class="row g-3 dashboard-nav-cards">
	<?php foreach ($cards as $card): ?>
		<?php
		$title = (string)($card['title'] ?? '');
		$text = (string)($card['text'] ?? '');
		$url = $card['url'] ?? '#';
		$button = (string)($card['button'] ?? __('Open'));
		$btnClass = (string)($card['btnClass'] ?? 'btn-primary');
		$icon = (string)($card['icon'] ?? '');
		if ($title === '' || $url === '' || $url === []) {
			continue;
		}
		$href = is_array($url) ? $this->Url->build($url) : (string)$url;
		?>
		<div class="col-12 col-md-6 col-xl-4">
			<div class="card h-100 shadow border border-2">
				<div class="card-body d-flex flex-column">
					<h4 class="card-title">
						<?php if ($icon !== ''): ?>
							<i class="fa fa-fw <?= h($icon) ?> me-1" aria-hidden="true"></i>
						<?php endif; ?>
						<?= h($title) ?>
					</h4>
					<p class="card-text flex-grow-1"><?= h($text) ?></p>
					<a href="<?= h($href) ?>" class="btn <?= h($btnClass) ?> align-self-start mt-2">
						<?php if ($icon !== ''): ?>
							<span class="btn-label"><i class="fa <?= h($icon) ?>"></i></span>
						<?php endif; ?>
						<?= h($button) ?>
					</a>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</div>
