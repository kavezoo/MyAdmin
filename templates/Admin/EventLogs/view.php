<?php
/**
 * Event log detail.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\EventLog $eventLog
 */
use App\Utility\EventLogChanges;

$userLabel = '—';
if (!empty($eventLog->user)) {
	$userLabel = trim((string)($eventLog->user->email ?? ''));
	if ($userLabel === '') {
		$userLabel = (string)($eventLog->user->username ?? $eventLog->user_id);
	}
} elseif (!empty($eventLog->user_id)) {
	$userLabel = (string)$eventLog->user_id;
}

$countryLabel = '—';
if (!empty($eventLog->country)) {
	$countryLabel = trim((string)$eventLog->country->name) . ' (' . (string)$eventLog->country->iso2 . ')';
} elseif (!empty($eventLog->country_id)) {
	$countryLabel = '#' . (int)$eventLog->country_id;
}

$decoded = EventLogChanges::decode($eventLog->request_data ?? null);
$changes = EventLogChanges::fromRequestData($eventLog->request_data ?? null);
$deleted = [];
if (!empty($decoded['deleted']) && is_array($decoded['deleted'])) {
	$deleted = $decoded['deleted'];
}

$requestPretty = '';
if ($decoded !== []) {
	$rest = $decoded;
	unset($rest['changes'], $rest['deleted'], $rest['changed']);
	if ($rest !== []) {
		$requestPretty = (string)json_encode($rest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
}
?>
<div class="row">
	<div class="col-12 col-xxl-10 p-2 pt-3">
		<?= $this->element('admin/activity_log_setup_toggles', [
			'redirectTarget' => $this->request->getRequestTarget(),
		]) ?>
		<?php if ($changes !== []): ?>
			<div class="card mb-3 shadow border border-2 border-primary">
				<div class="card-header bg-primary-subtle">
					<h4 class="mb-0"><i class="fa fa-exchange"></i> <?= __('Data changes') ?></h4>
					<div class="text-muted small"><?= __('Which fields changed and to what values.') ?></div>
				</div>
				<div class="card-body">
					<?= $this->element('admin/event_log_changes', [
						'changes' => $changes,
						'module' => (string)($eventLog->module ?? ''),
						'compact' => false,
					]) ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-list-alt"></i> <?= __('Event log') ?> #<?= (int)$eventLog->id ?></h3>
				</div>
				<div class="float-right">
					<a role="button" href="<?= $this->Url->build($indexListUrl ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<dl class="row mb-0">
					<dt class="col-sm-3"><?= __('Created') ?></dt>
					<dd class="col-sm-9"><?= $eventLog->created ? h(\App\Utility\LocaleDateParser::format($eventLog->created, 'datetime')) : '—' ?></dd>

					<dt class="col-sm-3"><?= __('Country') ?></dt>
					<dd class="col-sm-9"><?= h($countryLabel) ?></dd>

					<dt class="col-sm-3"><?= __('User') ?></dt>
					<dd class="col-sm-9"><?= h($userLabel) ?><?= !empty($eventLog->actor_role) ? ' <span class="text-muted">(' . h($eventLog->actor_role) . ')</span>' : '' ?></dd>

					<dt class="col-sm-3"><?= __('Module') ?></dt>
					<dd class="col-sm-9"><code><?= h($eventLog->module) ?></code></dd>

					<dt class="col-sm-3"><?= __('Action') ?></dt>
					<dd class="col-sm-9"><span class="badge text-bg-secondary"><?= h($eventLog->action) ?></span></dd>

					<dt class="col-sm-3"><?= __('Entity') ?></dt>
					<dd class="col-sm-9">
						<?= h((string)($eventLog->entity ?? '—')) ?>
						<?php if (!empty($eventLog->entity_id)): ?>
							#<?= h((string)$eventLog->entity_id) ?>
						<?php endif; ?>
					</dd>

					<dt class="col-sm-3"><?= __('Description') ?></dt>
					<dd class="col-sm-9"><?= h((string)($eventLog->description ?? '—')) ?></dd>

					<?php if ($deleted !== []): ?>
						<dt class="col-sm-3"><?= __('Deleted snapshot') ?></dt>
						<dd class="col-sm-9">
							<pre class="bg-light border rounded p-2 small mb-0" style="max-height:16rem;overflow:auto"><?= h((string)json_encode($deleted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
						</dd>
					<?php endif; ?>

					<dt class="col-sm-3"><?= __('URL') ?></dt>
					<dd class="col-sm-9"><code><?= h((string)($eventLog->url ?? '—')) ?></code></dd>

					<dt class="col-sm-3"><?= __('HTTP method') ?></dt>
					<dd class="col-sm-9"><?= h((string)($eventLog->http_method ?? '—')) ?></dd>

					<dt class="col-sm-3"><?= __('IP') ?></dt>
					<dd class="col-sm-9"><code><?= h((string)($eventLog->ip ?? '—')) ?></code></dd>

					<dt class="col-sm-3"><?= __('User agent') ?></dt>
					<dd class="col-sm-9 small"><?= h((string)($eventLog->user_agent ?? '—')) ?></dd>

					<?php if ($requestPretty !== ''): ?>
						<dt class="col-sm-3"><?= __('Request data') ?></dt>
						<dd class="col-sm-9">
							<pre class="bg-light border rounded p-2 small mb-0" style="max-height:24rem;overflow:auto"><?= h($requestPretty) ?></pre>
						</dd>
					<?php endif; ?>
				</dl>
			</div>
		</div>
	</div>
</div>
