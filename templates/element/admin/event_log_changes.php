<?php
/**
 * Render event log field diffs (from → to).
 *
 * @var \App\View\AppView $this
 * @var array<string, array{from?: mixed, to?: mixed}|mixed> $changes
 * @var bool $compact
 */
use App\Utility\EventLogChanges;
use App\Utility\EventLogger;

$changes = $changes ?? [];
$compact = (bool)($compact ?? false);
if ($changes === []) {
	return;
}
?>
<?php if ($compact): ?>
	<ul class="list-unstyled mb-0 small event-log-changes">
		<?php foreach ($changes as $field => $pair): ?>
			<?php
			$from = is_array($pair) ? ($pair['from'] ?? null) : null;
			$to = is_array($pair) ? ($pair['to'] ?? null) : $pair;
			$isSecret = EventLogger::isSecretField((string)$field);
			?>
			<li<?= $isSecret ? ' class="text-warning-emphasis"' : '' ?>>
				<code><?= h((string)$field) ?></code>:
				<span class="text-muted"><?= h(EventLogChanges::formatValue($from)) ?></span>
				→
				<strong><?= h(EventLogChanges::formatValue($to)) ?></strong>
			</li>
		<?php endforeach; ?>
	</ul>
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
					?>
					<tr<?= $isSecret ? ' class="table-warning"' : '' ?>>
						<td>
							<code><?= h((string)$field) ?></code>
							<?php if ($isSecret): ?>
								<span class="badge text-bg-warning"><?= h(__('Secret')) ?></span>
							<?php endif; ?>
						</td>
						<td><code><?= h(EventLogChanges::formatValue($from)) ?></code></td>
						<td><code><?= h(EventLogChanges::formatValue($to)) ?></code></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
