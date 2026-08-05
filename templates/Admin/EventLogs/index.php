<?php
/**
 * Event logs index (president+).
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\EventLog> $eventLogs
 * @var bool $canFilterCountries
 * @var int $filterCountryId
 * @var string $filterCountryLabel
 * @var array<int, string> $countryOptions
 * @var array<string, string> $actionOptions
 * @var array<string, string> $moduleOptions
 * @var string $actionFilter
 * @var string $moduleFilter
 * @var string $filterUserId
 * @var string $filterUserLabel
 * @var string $userSearchUrl
 */
use App\Utility\EventLogChanges;

$this->Html->css([
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
	'pages/index',
], ['block' => true]);
$this->Html->script('/plugins/select2-4.1.0/js/select2.full.min', ['block' => 'scriptBottom']);
$this->Html->script('pages/event_logs_index', ['block' => 'scriptBottom']);

$canFilterCountries = (bool)($canFilterCountries ?? false);
$filterCountryId = (int)($filterCountryId ?? 0);
$filterCountryLabel = (string)($filterCountryLabel ?? '');
$countryOptions = $countryOptions ?? [];
$actionOptions = $actionOptions ?? [];
$moduleOptions = $moduleOptions ?? [];
$actionFilter = (string)($actionFilter ?? '');
$moduleFilter = (string)($moduleFilter ?? '');
$filterUserId = (string)($filterUserId ?? '');
$filterUserLabel = (string)($filterUserLabel ?? '');
$userSearchUrl = (string)($userSearchUrl ?? '');

$baseQuery = $this->request->getQueryParams();
unset($baseQuery['page']);
$redirectTarget = $this->request->getRequestTarget();
?>
<div class="row">
	<div class="col-12 p-2">
		<?= $this->element('admin/activity_log_setup_toggles', compact('redirectTarget')) ?>
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-list-alt"></i> <?= __('Event logs') ?></h3>
					<?php if ($filterCountryLabel !== ''): ?>
						<div class="text-muted"><?= h(__('Showing events for {0}', $filterCountryLabel)) ?></div>
					<?php endif; ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<?= $this->element('admin/table_search') ?>
					<?= $this->element('admin/index_pagination') ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-2">
				<form method="get" class="row g-2 align-items-end mb-3 px-1" action="<?= h($this->Url->build(['action' => 'index'])) ?>" id="event-logs-filter-form">
					<?php foreach ($baseQuery as $qk => $qv): ?>
						<?php if (in_array($qk, ['country_id', 'action_filter', 'module_filter', 'user_id', 'page'], true)) {
							continue;
						} ?>
						<?php if (is_scalar($qv)): ?>
							<input type="hidden" name="<?= h((string)$qk) ?>" value="<?= h((string)$qv) ?>">
						<?php endif; ?>
					<?php endforeach; ?>

					<?php if ($canFilterCountries && $countryOptions !== []): ?>
						<div class="col-12 col-md-4 col-xl-3">
							<label class="form-label mb-1" for="event-log-country"><?= __('Country') ?></label>
							<select name="country_id" id="event-log-country" class="form-select js-example-basic-single">
								<?php foreach ($countryOptions as $cid => $clabel): ?>
									<option value="<?= (int)$cid ?>"<?= (int)$cid === $filterCountryId ? ' selected' : '' ?>><?= h($clabel) ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>

					<div class="col-12 col-md-6 col-xl-4">
						<label class="form-label mb-1" for="event-log-user"><?= __('User') ?></label>
						<select name="user_id" id="event-log-user" class="form-select" data-placeholder="<?= h(__('All users')) ?>" data-ajax-url="<?= h($userSearchUrl) ?>" data-country-id="<?= (int)$filterCountryId ?>">
							<?php if ($filterUserId !== ''): ?>
								<option value="<?= h($filterUserId) ?>" selected><?= h($filterUserLabel !== '' ? $filterUserLabel : $filterUserId) ?></option>
							<?php endif; ?>
						</select>
					</div>

					<div class="col-6 col-md-3 col-xl-2">
						<label class="form-label mb-1" for="event-log-module"><?= __('Module') ?></label>
						<select name="module_filter" id="event-log-module" class="form-select">
							<option value=""><?= __('All modules') ?></option>
							<?php foreach ($moduleOptions as $mod): ?>
								<option value="<?= h($mod) ?>"<?= $moduleFilter === $mod ? ' selected' : '' ?>><?= h($mod) ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="col-6 col-md-3 col-xl-2">
						<label class="form-label mb-1" for="event-log-action"><?= __('Action') ?></label>
						<select name="action_filter" id="event-log-action" class="form-select">
							<option value=""><?= __('All actions') ?></option>
							<?php foreach ($actionOptions as $act): ?>
								<option value="<?= h($act) ?>"<?= $actionFilter === $act ? ' selected' : '' ?>><?= h($act) ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="col-auto">
						<button type="submit" class="btn btn-outline-primary"><?= __('Filter') ?></button>
					</div>
				</form>

				<table class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table">
					<thead>
						<tr>
							<th scope="col" class="number id"><?= $this->Paginator->sort('id', '#') ?></th>
							<th scope="col" class="datetime created"><?= $this->Paginator->sort('created', __('Created')) ?></th>
							<th scope="col" class="string user"><?= __('User') ?></th>
							<th scope="col" class="string module"><?= $this->Paginator->sort('module', __('Module')) ?></th>
							<th scope="col" class="string action"><?= $this->Paginator->sort('action', __('Action')) ?></th>
							<th scope="col" class="string changes"><?= __('Data changes') ?></th>
							<th scope="col" class="string ip"><?= $this->Paginator->sort('ip', __('IP')) ?></th>
							<th scope="col" class="actions"><?= __('Actions') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$rows = collection($eventLogs)->toList();
						foreach ($rows as $eventLog):
							$userLabel = '';
							if (!empty($eventLog->user)) {
								$userLabel = trim((string)($eventLog->user->email ?? ''));
								if ($userLabel === '') {
									$userLabel = (string)($eventLog->user->username ?? $eventLog->user_id);
								}
							} elseif (!empty($eventLog->user_id)) {
								$userLabel = (string)$eventLog->user_id;
							} else {
								$userLabel = '—';
							}
							$changes = EventLogChanges::fromRequestData($eventLog->request_data ?? null);
							?>
							<tr id="record-<?= (int)$eventLog->id ?>" data-id="<?= (int)$eventLog->id ?>">
								<td class="number id"><?= (int)$eventLog->id ?></td>
								<td class="datetime created">
									<?= $eventLog->created
										? h(\App\Utility\LocaleDateParser::format($eventLog->created, 'datetime_short'))
										: '—' ?>
								</td>
								<td class="string user">
									<?= h($userLabel) ?>
									<?php if (!empty($eventLog->actor_role)): ?>
										<br><span class="text-muted small"><?= h($eventLog->actor_role) ?></span>
									<?php endif; ?>
								</td>
								<td class="string module"><code><?= h($eventLog->module) ?></code></td>
								<td class="string action"><span class="badge text-bg-secondary"><?= h($eventLog->action) ?></span></td>
								<td class="string changes">
									<?php if ($changes !== []): ?>
										<?= $this->element('admin/event_log_changes', [
											'changes' => $changes,
											'module' => (string)($eventLog->module ?? ''),
											'compact' => true,
										]) ?>
									<?php else: ?>
										<span class="text-muted"><?= h((string)($eventLog->description ?? '—')) ?></span>
									<?php endif; ?>
								</td>
								<td class="string ip"><code><?= h((string)($eventLog->ip ?? '')) ?></code></td>
								<td class="actions">
									<?= $this->Html->link(
										'<i class="fa fa-eye"></i>',
										['action' => 'view', $eventLog->id],
										[
											'escape' => false,
											'class' => 'btn btn-sm btn-outline-secondary',
											'title' => __('View'),
										]
									) ?>
								</td>
							</tr>
						<?php endforeach; ?>
						<?php if ($rows === []): ?>
							<tr>
								<td colspan="8" class="text-center text-muted py-4"><?= __('No event log records found.') ?></td>
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
<script>
window.EventLogsIndex = {
	noResults: <?= json_encode(__('No results found.')) ?>,
	searching: <?= json_encode(__('Search...')) ?>,
	inputTooShort: <?= json_encode(__('Please enter {0} or more characters', 2)) ?>
};
</script>
