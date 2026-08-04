<?php
/**
 * Own event log (all roles).
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\EventLog> $eventLogs
 * @var string $searchQ
 * @var bool $canSearchAll
 */
use App\Utility\EventLogChanges;
use CakeDC\Users\Utility\UsersUrl;

$this->Html->css(['pages/index'], ['block' => true]);
$searchQ = (string)($searchQ ?? '');
$canSearchAll = (bool)($canSearchAll ?? false);
$rows = collection($eventLogs)->toList();
?>
<div class="row">
	<div class="col-12 p-2">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-list-alt"></i> <?= __('My event log') ?></h3>
					<div class="text-muted"><?= __('Only your own activity is listed here.') ?></div>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<?php if ($canSearchAll): ?>
						<a class="btn btn-outline-primary btn-sm" href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'EventLogs', 'action' => 'index']) ?>">
							<i class="fa fa-search"></i> <?= __('Browse all event logs') ?>
						</a>
					<?php endif; ?>
					<form method="get" class="d-flex gap-2" action="<?= h($this->Url->build(UsersUrl::actionUrl('eventLog'))) ?>">
						<input type="search" name="q" value="<?= h($searchQ) ?>" class="form-control form-control-sm" placeholder="<?= h(__('Search…')) ?>" style="min-width:12rem">
						<button type="submit" class="btn btn-sm btn-outline-secondary"><?= __('Search') ?></button>
					</form>
					<?= $this->element('admin/index_pagination') ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-2">
				<table class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table">
					<thead>
						<tr>
							<th scope="col">#</th>
							<th scope="col"><?= __('Created') ?></th>
							<th scope="col"><?= __('Module') ?></th>
							<th scope="col"><?= __('Action') ?></th>
							<th scope="col"><?= __('Data changes') ?></th>
							<th scope="col"><?= __('Actions') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($rows as $eventLog): ?>
							<?php $changes = EventLogChanges::fromRequestData($eventLog->request_data ?? null); ?>
							<tr>
								<td><?= (int)$eventLog->id ?></td>
								<td><?= $eventLog->created ? h(\App\Utility\LocaleDateParser::format($eventLog->created, 'datetime_short')) : '—' ?></td>
								<td><code><?= h($eventLog->module) ?></code></td>
								<td><span class="badge text-bg-secondary"><?= h($eventLog->action) ?></span></td>
								<td>
									<?php if ($changes !== []): ?>
										<?= $this->element('admin/event_log_changes', ['changes' => $changes, 'compact' => true]) ?>
									<?php else: ?>
										<span class="text-muted"><?= h((string)($eventLog->description ?? '—')) ?></span>
									<?php endif; ?>
								</td>
								<td>
									<?= $this->Html->link(
										'<i class="fa fa-eye"></i>',
										array_merge(UsersUrl::actionUrl('eventLogView'), [(string)$eventLog->id]),
										['escape' => false, 'class' => 'btn btn-sm btn-outline-secondary']
									) ?>
								</td>
							</tr>
						<?php endforeach; ?>
						<?php if ($rows === []): ?>
							<tr>
								<td colspan="6" class="text-center text-muted py-4"><?= __('No event log records found.') ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<div class="card-footer">
				<?= $this->element('admin/index_counter') ?>
				<?= $this->element('admin/index_pagination') ?>
			</div>
		</div>
	</div>
</div>
