<?php
/**
 * Check-in dashboard.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Competition> $competitions
 */
$this->Html->css(['pages/index'], ['block' => true]);
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<h3 class="fw-bold mb-0"><i class="fa fa-ticket"></i> <?= __('Check-in') ?></h3>
				<div class="text-muted"><?= __('Competitions where you collect entry fees and hand out racing pipes.') ?></div>
			</div>
			<div class="card-body p-0">
				<?php
				$competitionList = is_array($competitions) ? $competitions : iterator_to_array($competitions);
				?>
				<?php if ($competitionList === []): ?>
					<p class="p-3 mb-0 text-muted"><?= __('No assigned competitions.') ?></p>
				<?php else: ?>
					<table class="table table-bordered table-hover mb-0 index-data-table">
						<thead>
							<tr>
								<th class="string"><?= __('Competition') ?></th>
								<th class="datetime"><?= __('When') ?></th>
								<th class="actions"><?= __('Actions') ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($competitionList as $competition): ?>
								<tr>
									<td class="string"><?= h((string)$competition->name) ?></td>
									<td class="datetime"><?= $competition->competition_datetime
										? h(\App\Utility\LocaleDateParser::format($competition->competition_datetime, 'datetime_short'))
										: '—' ?></td>
									<td class="actions">
										<a class="btn btn-sm btn-outline-primary" href="<?= h($this->Url->build([
											'prefix' => 'Checkin',
											'controller' => 'Applicants',
											'action' => 'index',
											$competition->id,
										])) ?>">
											<i class="fa fa-list"></i> <?= __('Applicants') ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
