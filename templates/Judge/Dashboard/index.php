<?php
/**
 * Judge dashboard.
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
				<h3 class="fw-bold mb-0"><i class="fa fa-gavel"></i> <?= __('Judge') ?></h3>
				<div class="text-muted"><?= __('Competitions where you are assigned as table judge.') ?></div>
			</div>
			<div class="card-body p-0">
				<table class="table table-bordered table-hover mb-0 index-data-table">
					<thead>
						<tr>
							<th class="string"><?= __('Competition') ?></th>
							<th class="datetime"><?= __('When') ?></th>
							<th class="actions"><?= __('Actions') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($competitions as $competition): ?>
							<tr>
								<td class="string"><?= h((string)$competition->name) ?></td>
								<td class="datetime"><?= $competition->competition_datetime
									? h(\App\Utility\LocaleDateParser::format($competition->competition_datetime, 'datetime_short'))
									: '—' ?></td>
								<td class="actions">
									<a class="btn btn-sm btn-outline-primary" href="<?= h($this->Url->build([
										'prefix' => 'Judge',
										'controller' => 'Applicants',
										'action' => 'index',
										$competition->id,
									])) ?>">
										<i class="fa fa-stopwatch"></i> <?= __('Results') ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
