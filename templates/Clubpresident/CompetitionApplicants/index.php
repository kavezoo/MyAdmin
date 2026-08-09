<?php
/**
 * Clubpresident — assign applied members to sub-teams (competitions_clubs / subclubs).
 *
 * @var \App\View\AppView $this
 * @var array<string, array{competition: \App\Model\Entity\Competition|null, applicants: list<\App\Model\Entity\CompetitionsUser>}> $groups
 * @var array<string, array<string, string>> $teamOptionsByCompetition
 */
use App\Auth\MembershipProfile;
use App\Utility\CompetitionApplication;

$this->Html->css(['pages/index', 'pages/form'], ['block' => true]);
$groups = $groups ?? [];
$teamOptionsByCompetition = $teamOptionsByCompetition ?? [];
$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Delete this application — the member will not compete.');
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-user-plus"></i> <?= __('Competition applicants') ?></h3>
					<?= __('Members apply first. Assign a sub-team to register them; delete if they should not compete.') ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-users"></i></span>' . __('Sub-teams'),
						['prefix' => 'Clubpresident', 'controller' => 'CompetitionTeams', 'action' => 'index'],
						['escape' => false, 'class' => 'btn btn-outline-secondary']
					) ?>
					<?= $this->element('admin/index_pagination', ['leadingSep' => true]) ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?= $this->element('panel/competition_browse_country', [
					'browseCountryId' => $browseCountryId ?? 0,
					'browseCountryOptions' => $browseCountryOptions ?? [],
					'homeCountryId' => $homeCountryId ?? 0,
				]) ?>
				<?php if ($groups === []): ?>
					<p class="text-muted mb-0"><?= __('No club members have applied to an active competition in this country yet. Members apply from the Member panel → Competitions.') ?></p>
				<?php else: ?>
					<?php foreach ($groups as $competitionId => $group): ?>
						<?php
						$competition = $group['competition'];
						$applicants = $group['applicants'];
						$teamOptions = $teamOptionsByCompetition[$competitionId] ?? ['' => __('— Unassigned —')];
						$hasTeams = count($teamOptions) > 1;
						?>
						<div class="mb-4" id="competition-<?= h((string)$competitionId) ?>">
							<h4 class="mb-2">
								<i class="fa fa-trophy"></i>
								<?= h((string)($competition->name ?? __('Competition'))) ?>
							</h4>
							<?php if (!$hasTeams): ?>
								<div class="alert alert-warning py-2">
									<?= __('Create at least one sub-team for this competition before assigning members.') ?>
									<?= $this->Html->link(
										__('Sub-teams'),
										['prefix' => 'Clubpresident', 'controller' => 'CompetitionTeams', 'action' => 'add'],
										['class' => 'alert-link']
									) ?>
								</div>
							<?php endif; ?>
							<table class="table table-bordered table-striped table-hover mb-0">
								<thead>
									<tr>
										<th scope="col"><?= __('Member') ?></th>
										<th scope="col"><?= __('Status') ?></th>
										<th scope="col"><?= __('Current sub-team') ?></th>
										<th scope="col"><?= __('Assign to') ?></th>
										<th scope="col" class="text-end"><?= __('Actions') ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($applicants as $app): ?>
										<?php
										$userEntity = $app->user ?? null;
										$name = $userEntity ? MembershipProfile::displayName($userEntity) : '';
										if ($name === '') {
											$name = (string)($userEntity->email ?? $app->user_id);
										}
										$currentTeam = (string)($app->competitions_club->subclub->name ?? '—');
										$registered = CompetitionApplication::isRegistered((string)$app->status, $app->competition_club_id);
										?>
										<tr>
											<td><?= h($name) ?></td>
											<td>
												<span class="badge <?= $registered ? 'text-bg-success' : 'text-bg-warning' ?>">
													<?= h(CompetitionApplication::statusLabel((string)$app->status)) ?>
												</span>
											</td>
											<td><?= h($currentTeam) ?></td>
											<td>
												<?= $this->Form->create(null, [
													'url' => ['action' => 'assign'],
													'class' => 'd-flex flex-wrap gap-2 align-items-center',
												]) ?>
													<?= $this->Form->hidden('application_id', ['value' => $app->id]) ?>
													<?= $this->Form->control('competition_club_id', [
														'label' => false,
														'type' => 'select',
														'options' => $teamOptions,
														'value' => $app->competition_club_id ? (string)$app->competition_club_id : '',
														'class' => 'form-select form-select-sm',
														'disabled' => !$hasTeams && !$app->competition_club_id,
														'templates' => ['inputContainer' => '{{content}}'],
													]) ?>
													<button type="submit" class="btn btn-sm btn-primary"<?= !$hasTeams && !$app->competition_club_id ? ' disabled' : '' ?>>
														<?= __('Save') ?>
													</button>
												<?= $this->Form->end() ?>
											</td>
											<td class="text-end text-nowrap">
												<?= $this->Html->link(
													'<i class="fa fa-pencil"></i>',
													['action' => 'edit', $app->id],
													[
														'escape' => false,
														'class' => 'btn btn-sm btn-outline-primary',
														'title' => h(__('Edit application details')),
													]
												) ?>
												<?= $this->Form->create(null, [
													'url' => ['action' => 'delete', $app->id],
													'id' => 'delete-form-' . $app->id,
													'class' => 'd-inline',
												]) ?>
												<button type="button"
													class="btn btn-sm btn-outline-danger btn-row-delete"
													data-id="<?= (int)$app->id ?>"
													data-bs-toggle="tooltip"
													data-bs-html="true"
													title="<?= h($tooltipDelete) ?>"
													data-swal-title="<?= h(__('Delete')) ?>"
													data-swal-text="<?= h(__('Delete this application? The member will no longer be listed for this competition.')) ?>">
													<i class="fa fa-trash"></i>
												</button>
												<?= $this->Form->end() ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<div class="card-footer">
				<?= $this->element('admin/index_footer') ?>
			</div>
		</div>
	</div>
</div>
