<?php
/**
 * Friendly activity change list (user-facing).
 *
 * @var \App\View\AppView $this
 * @var array<string, array{from?: mixed, to?: mixed}|mixed> $changes
 * @var string $module
 * @var bool $compact
 */
use App\Utility\EventLogChanges;
use App\Utility\EventLogPresenter;
use App\Utility\EventLogger;

$changes = $changes ?? [];
$module = (string)($module ?? '');
$compact = (bool)($compact ?? false);
if ($changes === []) {
	return;
}
?>
<?php if ($compact): ?>
	<div class="activity-log-changes small text-muted mb-0">
		<?= h(EventLogPresenter::friendlyChangeSummary($changes, $module)) ?>
	</div>
<?php else: ?>
	<ul class="list-unstyled mb-0 activity-log-changes-list">
		<?php foreach ($changes as $field => $pair): ?>
			<?php
			$from = is_array($pair) ? ($pair['from'] ?? null) : null;
			$to = is_array($pair) ? ($pair['to'] ?? null) : $pair;
			$isSecret = EventLogger::isSecretField((string)$field);
			$label = EventLogPresenter::fieldLabel($module, (string)$field);
			?>
			<li class="mb-2">
				<strong><?= h($label) ?></strong>
				<?php if ($isSecret): ?>
					<span class="text-muted"> — <?= h(__('changed')) ?></span>
				<?php else: ?>
					<br>
					<span class="text-muted"><?= h(EventLogPresenter::formatFieldValue($module, (string)$field, $from)) ?></span>
					<i class="fa fa-long-arrow-right mx-1 text-muted" aria-hidden="true"></i>
					<span><?= h(EventLogPresenter::formatFieldValue($module, (string)$field, $to)) ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
