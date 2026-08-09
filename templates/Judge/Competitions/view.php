<?php
/**
 * Judge competition view (tools to be extended).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Competition $competition
 */
$this->Html->css(['pages/index', 'pages/competition_view'], ['block' => true]);
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<h3 class="fw-bold mb-0"><i class="fa fa-gavel"></i> <?= h((string)$competition->name) ?></h3>
				<div class="text-muted"><?= __('Table judge workspace for this competition.') ?></div>
			</div>
			<div class="card-body">
				<dl class="row record-view-fields mb-0">
					<div class="record-view-row"><dt><?= __('Title') ?></dt><dd><?= h((string)$competition->title) ?></dd></div>
					<div class="record-view-row"><dt><?= __('When') ?></dt><dd><?= $competition->competition_datetime
						? h(\App\Utility\LocaleDateParser::format($competition->competition_datetime, 'datetime_short'))
						: '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Club') ?></dt><dd><?= h((string)($competition->club->name ?? '')) ?></dd></div>
				</dl>
				<p class="text-muted mt-3 mb-0"><?= __('Scoring and table tools will appear here.') ?></p>
			</div>
			<div class="card-footer">
				<a class="btn btn-outline-secondary" href="<?= h($this->Url->build([
					'prefix' => 'Judge',
					'controller' => 'Dashboard',
					'action' => 'index',
				])) ?>"><i class="fa fa-arrow-left"></i> <?= __('Back') ?></a>
			</div>
		</div>
	</div>
</div>
