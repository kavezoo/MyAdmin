<?php
/**
 * President — pick a competition for cash desk overview.
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\Paging\PaginatedResultSet<\App\Model\Entity\Competition> $competitions
 */
$this->Html->css(['pages/index'], ['block' => true]);
$lastVisitedId = $this->get('lastVisitedId');
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold mb-0"><i class="fa fa-cash-register"></i> <?= __('Cash desk') ?></h3>
					<div class="text-muted small"><?= __('See how much each check-in collector should have in the till for a competition.') ?></div>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<?= $this->element('admin/table_search') ?>
					<?= $this->element('admin/index_pagination', ['leadingSep' => true]) ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-0">
				<table class="table table-bordered table-hover mb-0 index-data-table">
					<thead>
						<tr>
							<th class="string"><?= $this->Paginator->sort('name', __('Competition')) ?></th>
							<th class="datetime"><?= $this->Paginator->sort('competition_datetime', __('Competition datetime')) ?></th>
							<th class="string"><?= $this->Paginator->sort('Clubs.name', __('Club')) ?></th>
							<th class="actions"><?= __('Actions') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ($competitions->count() === 0): ?>
							<tr><td colspan="4" class="text-muted text-center py-4"><?= __('No competitions yet.') ?></td></tr>
						<?php else: ?>
							<?php foreach ($competitions as $competition):
								$club = $competition->club ?? null;
								$isLastVisited = isset($lastVisitedId) && (string)$lastVisitedId === (string)$competition->id;
								?>
								<tr<?= $isLastVisited ? ' class="last-visited"' : '' ?>>
									<td class="string"><?= h((string)$competition->name) ?></td>
									<td class="datetime"><?= h(\App\Utility\LocaleDateParser::format($competition->competition_datetime, 'datetime_short')) ?></td>
									<td class="string"><?= h($club ? (string)$club->name : '—') ?></td>
									<td class="actions">
										<a class="btn btn-sm btn-outline-primary" href="<?= h($this->Url->build([
											'action' => 'view',
											$competition->id,
										])) ?>"><?= __('Open cash desk') ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<div class="card-footer">
				<?= $this->element('admin/index_footer') ?>
			</div>
		</div>
	</div>
</div>
