<?php
/**
 * Render event log field diffs (from → to).
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
	<div class="event-log-changes mb-0">
		<?= h(EventLogPresenter::friendlyChangeSummary($changes, $module, 400)) ?>
	</div>
<?php else: ?>
	<div class="table-responsive">
		<table class="table table-sm table-bordered mb-0 align-middle">
			<thead>
				<tr>
					<th><?= __('Field') ?></th>
					<th><?= __('From') ?></th>
					<th><?= __('To') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($changes as $field => $pair): ?>
					<?php
					$from = is_array($pair) ? ($pair['from'] ?? null) : null;
					$to = is_array($pair) ? ($pair['to'] ?? null) : $pair;
					$isSecret = EventLogger::isSecretField((string)$field);
					$label = EventLogPresenter::fieldLabel($module, (string)$field);
					?>
					<tr<?= $isSecret ? ' class="table-warning"' : '' ?>>
						<td>
							<?= h($label) ?>
							<span class="text-muted small"><code><?= h((string)$field) ?></code></span>
							<?php if ($isSecret): ?>
								<span class="badge text-bg-warning"><?= h(__('Secret')) ?></span>
							<?php endif; ?>
						</td>
						<td><?= h(EventLogPresenter::formatFieldValue($module, (string)$field, $from)) ?></td>
						<td><strong><?= h(EventLogPresenter::formatFieldValue($module, (string)$field, $to)) ?></strong></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
