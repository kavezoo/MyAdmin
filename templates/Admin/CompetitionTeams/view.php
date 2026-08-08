<?php
/**
 * Admin — competition sub-team view.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CompetitionsClub $team
 * @var string $teamName
 * @var int $minimum
 * @var bool $meetsMinimum
 */
$yes = '<i class="fa fa-check text-success"></i> ' . h(__('Yes'));
$no = '<i class="fa fa-times text-danger"></i> ' . h(__('No'));
$competitionViewUrl = [
	'prefix' => 'Admin',
	'controller' => 'Competitions',
	'action' => 'view',
	$team->competition_id,
];
?>
<div class="row">
	<div class="col-12 col-xxl-10 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-users"></i> <?= __('Sub-team details') ?></h3>
					<?= h($teamName) ?>
				</div>
				<div class="float-right">
					<a role="button" href="<?= $this->Url->build($competitionViewUrl) ?>" class="btn btn-outline-secondary">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<dl class="row record-view-fields mb-0">
					<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h((string)$team->id) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Competition') ?></dt><dd><?= h((string)($team->competition->name ?? '')) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Team') ?></dt><dd><?= h($teamName) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Club') ?></dt><dd><?= h((string)($team->club->name ?? '')) ?></dd></div>
					<div class="record-view-row">
						<dt><?= __('Members') ?></dt>
						<dd>
							<?= h(\App\Utility\LocaleNumberParser::format($team->user_count, decimals: 0)) ?>
							/ <?= h(\App\Utility\LocaleNumberParser::format($minimum, decimals: 0)) ?>
							<?php if ($meetsMinimum): ?>
								<span class="badge text-bg-success ms-1"><?= __('Ready to compete') ?></span>
							<?php endif; ?>
						</dd>
					</div>
					<div class="record-view-row">
						<dt><?= __('Applied') ?></dt>
						<dd><?= $team->application_datetime
							? h(\App\Utility\LocaleDateParser::format($team->application_datetime, 'datetime_short'))
							: '—' ?></dd>
					</div>
					<div class="record-view-row"><dt><?= __('Visible') ?></dt><dd><?= !empty($team->visible) ? $yes : $no ?></dd></div>
					<div class="record-view-row"><dt><?= __('Position') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($team->pos, decimals: 0)) ?></dd></div>
				</dl>
			</div>
			<div class="card-footer">
				<div class="record-view-footer-actions">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . __('Edit'),
						['action' => 'edit', $team->id],
						['escape' => false, 'class' => 'btn btn-primary']
					) ?>
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-trophy"></i></span>' . __('Competition'),
						$competitionViewUrl,
						['escape' => false, 'class' => 'btn btn-outline-secondary ms-2']
					) ?>
				</div>
			</div>
		</div>
	</div>
</div>
