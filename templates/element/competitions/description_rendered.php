<?php
/**
 * Render competition description with {{placeholders}} resolved + optional map slot.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Competition $competition
 */
use App\Utility\CompetitionTextRender;

$competition = $competition ?? null;
if ($competition === null) {
	return;
}

[$before, $hasMap, $after] = CompetitionTextRender::descriptionParts($competition);
$embedUrl = CompetitionTextRender::mapEmbedUrl($competition);
$venueLabel = CompetitionTextRender::vars($competition)['venue'] ?? '';
?>
<div class="competition-description-rendered">
	<?php if (trim($before) !== ''): ?>
		<?= $this->element('admin/html_content', ['html' => $before]) ?>
	<?php endif; ?>

	<?php if ($hasMap || $embedUrl !== ''): ?>
		<?= $this->element('competitions/map_frame', [
			'embedUrl' => $embedUrl,
			'venueLabel' => $venueLabel,
		]) ?>
	<?php endif; ?>

	<?php if ($hasMap && trim($after) !== ''): ?>
		<?= $this->element('admin/html_content', ['html' => $after]) ?>
	<?php elseif (!$hasMap && trim($before) === '' && $embedUrl === ''): ?>
		—
	<?php endif; ?>
</div>
