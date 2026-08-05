<?php
/**
 * Own activity log (friendly list).
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\EventLog> $eventLogs
 * @var string $searchQ
 * @var bool $canSearchAll
 */
use App\Utility\EventLogChanges;
use App\Utility\EventLogPresenter;
use CakeDC\Users\Utility\UsersUrl;

$this->Html->css(['pages/activity_log'], ['block' => true]);
$searchQ = (string)($searchQ ?? '');
$canSearchAll = (bool)($canSearchAll ?? false);
$rows = collection($eventLogs)->toList();
?>
<div class="row">
	<div class="col-12 col-lg-10 col-xxl-9 p-2">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold mb-1"><i class="fa fa-history"></i> <?= __('My activity') ?></h3>
					<div class="text-muted"><?= __('When you signed in, signed out, or changed your data.') ?></div>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<?php if ($canSearchAll): ?>
						<a class="btn btn-outline-primary btn-sm" href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'EventLogs', 'action' => 'index']) ?>">
							<i class="fa fa-search"></i> <?= __('Browse all activity') ?>
						</a>
					<?php endif; ?>
					<form method="get" class="d-flex gap-2" action="<?= h($this->Url->build(UsersUrl::actionUrl('eventLog'))) ?>">
						<input type="search" name="q" value="<?= h($searchQ) ?>" class="form-control form-control-sm" placeholder="<?= h(__('Search activity…')) ?>" style="min-width:12rem">
						<button type="submit" class="btn btn-sm btn-outline-secondary"><?= __('Search') ?></button>
					</form>
					<?= $this->element('admin/index_pagination') ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-0">
				<?php if ($rows === []): ?>
					<div class="text-center text-muted py-5 px-3"><?= __('No activity recorded yet.') ?></div>
				<?php else: ?>
					<ul class="list-group list-group-flush activity-log-timeline">
						<?php foreach ($rows as $eventLog): ?>
							<?php
							$changes = EventLogChanges::fromRequestData($eventLog->request_data ?? null);
							$module = (string)($eventLog->module ?? '');
							$summary = EventLogPresenter::activitySummary($eventLog);
							$when = $eventLog->created
								? \App\Utility\LocaleDateParser::format($eventLog->created, 'datetime')
								: '—';
							?>
							<li class="list-group-item activity-log-item">
								<div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
									<div class="flex-grow-1">
										<div class="activity-log-when text-muted small mb-1">
											<i class="fa fa-clock-o me-1" aria-hidden="true"></i><?= h($when) ?>
										</div>
										<div class="activity-log-summary fw-semibold"><?= h($summary) ?></div>
										<?php if ($changes === [] && !empty($eventLog->description) && $summary !== (string)$eventLog->description): ?>
											<div class="small text-muted mt-1"><?= h((string)$eventLog->description) ?></div>
										<?php endif; ?>
									</div>
									<div>
										<?= $this->Html->link(
											__('Details'),
											array_merge(UsersUrl::actionUrl('eventLogView'), [(string)$eventLog->id]),
											['class' => 'btn btn-sm btn-outline-secondary']
										) ?>
									</div>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
			<?php if ($rows !== []): ?>
				<div class="card-footer">
					<?= $this->element('admin/index_counter') ?>
					<?= $this->element('admin/index_pagination') ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
