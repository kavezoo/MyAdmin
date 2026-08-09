<?php
/**
 * Clickable {{placeholder}} chips for Summernote (competition text templates).
 *
 * @var \App\View\AppView $this
 * @var list<array{token?: string, label?: string, help?: string}|string> $placeholderHelp
 */
$placeholderHelp = $placeholderHelp ?? [];
if ($placeholderHelp === []) {
	return;
}
?>
<div class="competition-placeholders" id="competitionPlaceholders">
	<div class="competition-placeholders__title"><?= __('Available fields') ?></div>
	<div class="competition-placeholders__hint text-muted">
		<?= __('Click a field to insert it as bold text at the cursor. On display, values use the visitor language (dates, times, numbers).') ?>
	</div>
	<div class="competition-placeholders__list" role="list">
		<?php foreach ($placeholderHelp as $item): ?>
			<?php
			if (is_string($item)) {
				$token = $item;
				$label = trim($item, '{}');
				$help = '';
			} else {
				$token = (string)($item['token'] ?? '');
				$label = (string)($item['label'] ?? '');
				$help = (string)($item['help'] ?? '');
			}
			if ($token === '') {
				continue;
			}
			if ($label === '') {
				$label = trim($token, '{}');
			}
			$title = $help !== '' ? $token . ' — ' . $help : $token;
			?>
			<button
				type="button"
				class="competition-placeholder-chip"
				role="listitem"
				data-placeholder="<?= h($token) ?>"
				title="<?= h($title) ?>"
				aria-label="<?= h($title) ?>"
			>
				<code class="competition-placeholder-chip__token"><?= h($token) ?></code>
				<span class="competition-placeholder-chip__label"><?= h($label) ?></span>
				<?php if ($help !== ''): ?>
					<span class="competition-placeholder-chip__help"><?= h($help) ?></span>
				<?php endif; ?>
			</button>
		<?php endforeach; ?>
	</div>
</div>
