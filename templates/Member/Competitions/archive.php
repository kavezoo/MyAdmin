<?php
/**
 * Member — competition results archive.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\CompetitionsUser> $rows
 */
use App\Utility\CompetitionApplication;

$this->Html->css(['pages/index'], ['block' => true]);
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-archive"></i> <?= __('Competition archive') ?></h3>
					<?= __('Past competitions you took part in') ?>
				</div>
				<div class="float-right">
					<?= $this->Html->link(__('Back'), ['action' => 'index'], ['class' => 'btn btn-outline-secondary']) ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-2">
				<table class="table table-bordered table-striped mb-0">
					<thead>
						<tr>
							<th><?= __('Competition') ?></th>
							<th><?= __('Team') ?></th>
							<th><?= __('Status') ?></th>
							<th><?= __('Rank') ?></th>
							<th><?= __('Score') ?></th>
							<th><?= __('Note') ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php if ($rows->count() === 0): ?>
							<tr><td colspan="7" class="text-center text-muted py-4"><?= __('No archived competitions yet.') ?></td></tr>
						<?php else: ?>
							<?php foreach ($rows as $row): ?>
								<tr>
									<td><?= h((string)($row->competition->name ?? '')) ?></td>
									<td><?= h((string)($row->competitions_club->subclub->name ?? $row->competitions_club->name ?? '—')) ?></td>
									<td><?= h(CompetitionApplication::statusLabel((string)$row->status)) ?></td>
									<td><?= $row->result_rank !== null ? h(\App\Utility\LocaleNumberParser::format($row->result_rank, decimals: 0)) : '—' ?></td>
									<td><?= h((string)($row->result_score ?? '—')) ?></td>
									<td><?= h((string)($row->result_note ?? '—')) ?></td>
									<td><?= $this->Html->link(__('View'), ['action' => 'view', $row->competition_id], ['class' => 'btn btn-sm btn-outline-secondary']) ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
