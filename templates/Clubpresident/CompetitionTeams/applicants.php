<?php
/**
 * Clubpresident — assign applicants to teams.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CompetitionsClub $team
 * @var iterable<\App\Model\Entity\CompetitionsUser> $applicants
 * @var array<int, string> $teamOptions
 * @var int $minimum
 * @var bool $meetsMinimum
 */
use App\Utility\CompetitionApplication;

$this->Html->css(['pages/index', 'pages/form'], ['block' => true]);
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-users"></i> <?= __('Assign applicants') ?></h3>
					<?= h((string)($team->competition->name ?? '')) ?> — <?= h((string)($team->subclub->name ?? '')) ?>
					<div class="mt-1">
						<?= __('Members on this team:') ?>
						<strong><?= h(\App\Utility\LocaleNumberParser::format($team->user_count, decimals: 0)) ?></strong>
						/ <?= __('minimum') ?>
						<strong><?= h(\App\Utility\LocaleNumberParser::format($minimum, decimals: 0)) ?></strong>
						<?php if ($meetsMinimum): ?>
							<span class="badge text-bg-success ms-2"><?= __('Ready to compete') ?></span>
						<?php else: ?>
							<span class="badge text-bg-warning ms-2"><?= __('Below minimum — move members if needed') ?></span>
						<?php endif; ?>
					</div>
				</div>
				<div class="float-right d-flex gap-2">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-user-plus"></i></span>' . __('All applicants'),
						['prefix' => 'Clubpresident', 'controller' => 'CompetitionApplicants', 'action' => 'index', '#' => 'competition-' . $team->competition_id],
						['escape' => false, 'class' => 'btn btn-outline-primary']
					) ?>
					<?= $this->Html->link(
						'<i class="fa fa-eye"></i>',
						['action' => 'view', $team->id],
						['escape' => false, 'class' => 'btn btn-outline-secondary', 'title' => h(__('View details'))]
					) ?>
					<a href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary"><i class="fa fa-times"></i></a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-2">
				<table class="table table-bordered table-striped mb-0">
					<thead>
						<tr>
							<th><?= __('Member') ?></th>
							<th><?= __('Status') ?></th>
							<th><?= __('Current team') ?></th>
							<th><?= __('Assign to') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ($applicants->count() === 0): ?>
							<tr><td colspan="4" class="text-center text-muted py-4"><?= __('No club members have applied yet.') ?></td></tr>
						<?php else: ?>
							<?php foreach ($applicants as $app): ?>
								<?php
								$userEntity = $app->user ?? null;
								$name = $userEntity ? \App\Auth\MembershipProfile::displayName($userEntity) : '';
								if ($name === '') {
									$name = (string)($userEntity->email ?? $app->user_id);
								}
								?>
								<tr>
									<td><?= h($name) ?></td>
									<td><?= h(CompetitionApplication::statusLabel((string)$app->status)) ?></td>
									<td><?= h((string)($app->competitions_club->subclub->name ?? $app->competitions_club->name ?? '—')) ?></td>
									<td>
										<?= $this->Form->create(null, ['url' => ['action' => 'applicants', $team->id], 'class' => 'd-flex gap-2 align-items-center']) ?>
											<?= $this->Form->hidden('user_id', ['value' => $app->user_id]) ?>
											<?= $this->Form->control('competition_club_id', [
												'label' => false,
												'type' => 'select',
												'options' => $teamOptions,
												'value' => $app->competition_club_id ?: $team->id,
												'class' => 'form-select form-select-sm',
												'templates' => ['inputContainer' => '{{content}}'],
											]) ?>
											<button type="submit" class="btn btn-sm btn-primary"><?= __('Save') ?></button>
										<?= $this->Form->end() ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
