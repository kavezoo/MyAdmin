<?php
/**
 * Own activity detail (friendly).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\EventLog $eventLog
 */
use App\Utility\EventLogChanges;
use App\Utility\EventLogPresenter;
use CakeDC\Users\Utility\UsersUrl;

$module = (string)($eventLog->module ?? '');
$changes = EventLogChanges::fromRequestData($eventLog->request_data ?? null);
$summary = EventLogPresenter::activitySummary($eventLog);
$when = $eventLog->created
	? \App\Utility\LocaleDateParser::format($eventLog->created, 'datetime')
	: '—';
$backUrl = $this->Url->build(UsersUrl::actionUrl('eventLog'));
?>
<div class="row mt-3">
	<div class="col-12 col-lg-8 col-xxl-7 p-2">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3 class="mb-1"><i class="fa fa-history"></i> <?= __('Activity details') ?></h3>
					<div class="text-muted small"><?= h($when) ?></div>
				</div>
				<div class="float-right">
					<a role="button" href="<?= h($backUrl) ?>" class="btn btn-outline-secondary">
						<i class="fa fa-arrow-left"></i> <?= __('Back') ?>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<p class="lead mb-4"><?= h($summary) ?></p>
				<?php if ($changes !== []): ?>
					<h5 class="mb-3"><?= __('What changed') ?></h5>
					<?= $this->element('activity_log_changes', [
						'changes' => $changes,
						'module' => $module,
						'compact' => false,
					]) ?>
				<?php elseif (!empty($eventLog->description)): ?>
					<p class="text-muted mb-0"><?= h((string)$eventLog->description) ?></p>
				<?php else: ?>
					<p class="text-muted mb-0"><?= __('No further details for this entry.') ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
