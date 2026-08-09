<?php
/**
 * Competition venue map — compact frame + expand / Esc.
 *
 * @var \App\View\AppView $this
 * @var string $embedUrl
 * @var string $venueLabel
 */
$embedUrl = trim((string)($embedUrl ?? ''));
$venueLabel = trim((string)($venueLabel ?? ''));
if ($embedUrl === '') {
	if ($venueLabel !== '') {
		echo '<div class="competition-venue-only border rounded p-2 bg-white">' . h($venueLabel) . '</div>';
	}

	return;
}

$this->Html->css(['pages/competition_view'], ['block' => true]);
$this->Html->script(['pages/competition_map'], ['block' => 'scriptBottom']);
?>
<div class="competition-map-widget" data-competition-map>
	<?php if ($venueLabel !== ''): ?>
		<div class="competition-map-venue mb-2"><?= h($venueLabel) ?></div>
	<?php endif; ?>
	<div class="competition-map-frame border rounded overflow-hidden">
		<iframe
			class="competition-map-iframe"
			src="<?= h($embedUrl) ?>"
			title="<?= h(__('Venue map')) ?>"
			loading="lazy"
			referrerpolicy="no-referrer-when-downgrade"
			allowfullscreen
		></iframe>
	</div>
	<div class="competition-map-actions mt-2">
		<button type="button" class="btn btn-sm btn-outline-secondary js-competition-map-expand">
			<i class="fa fa-expand" aria-hidden="true"></i> <?= h(__('Enlarge map')) ?>
		</button>
	</div>
</div>

<div class="competition-map-lightbox" data-competition-map-lightbox hidden>
	<div class="competition-map-lightbox-inner">
		<div class="competition-map-lightbox-toolbar">
			<span class="competition-map-lightbox-title"><?= h($venueLabel !== '' ? $venueLabel : __('Venue map')) ?></span>
			<button type="button" class="btn btn-sm btn-light js-competition-map-close" aria-label="<?= h(__('Close')) ?>">
				<i class="fa fa-times" aria-hidden="true"></i> <?= h(__('Close')) ?>
			</button>
		</div>
		<iframe
			class="competition-map-lightbox-iframe"
			src="<?= h($embedUrl) ?>"
			title="<?= h(__('Venue map')) ?>"
			loading="lazy"
			referrerpolicy="no-referrer-when-downgrade"
			allowfullscreen
		></iframe>
	</div>
</div>
