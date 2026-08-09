<?php
/**
 * President — competition view + related tabs (qualifying sub-teams / applicants).
 *
 * Only sub-teams with at least `minimum_team_size` assigned members are listed.
 * Applicants tab: only members assigned to those qualifying sub-teams.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Competition $competition
 * @var int $minimum
 * @var list<\App\Model\Entity\CompetitionsClub> $qualifyingTeams
 * @var list<\App\Model\Entity\CompetitionsUser> $qualifyingApplicants
 */
use App\Auth\MembershipProfile;
use App\Utility\CompetitionApplication;

$this->Html->css(['pages/index', 'pages/competition_view'], ['block' => true]);
$this->Html->script(['pages/index'], ['block' => 'scriptBottom']);

$yes = '<i class="fa fa-check text-success"></i> ' . h(__('Yes'));
$no = '<i class="fa fa-times text-danger"></i> ' . h(__('No'));
$minimum = (int)($minimum ?? (int)($competition->minimum_team_size ?? 0));
$qualifyingTeams = $qualifyingTeams ?? [];
$qualifyingApplicants = $qualifyingApplicants ?? [];
$teamsCount = count($qualifyingTeams);
$applicantsCount = count($qualifyingApplicants);

$membersGetUrl = $this->Url->build(['prefix' => 'President', 'controller' => 'Members', 'action' => 'recordGet']);
$membersEditUrl = $this->Url->build(['prefix' => 'President', 'controller' => 'Members', 'action' => 'edit']);
$membersViewUrl = $this->Url->build(['prefix' => 'President', 'controller' => 'Members', 'action' => 'view']);
$membersDeleteUrl = $this->Url->build(['prefix' => 'President', 'controller' => 'Members', 'action' => 'delete']);
$clubsGetUrl = $this->Url->build(['prefix' => 'President', 'controller' => 'Clubs', 'action' => 'recordGet']);
$clubsEditUrl = $this->Url->build(['prefix' => 'President', 'controller' => 'Clubs', 'action' => 'edit']);
$clubsViewUrl = $this->Url->build(['prefix' => 'President', 'controller' => 'Clubs', 'action' => 'view']);
$clubsDeleteUrl = $this->Url->build(['prefix' => 'President', 'controller' => 'Clubs', 'action' => 'delete']);

$userLabels = [
	'id' => __('ID'),
	'first_name' => __('Name'),
	'email' => __('Email'),
	'phone' => __('Phone'),
	'role' => __('Role'),
	'club' => __('Club'),
];
$clubLabels = [
	'id' => __('ID'),
	'name' => __('Name'),
	'short_name' => __('Short name'),
];
$config = [
	'rowDoubleClickAction' => 'modal',
	'entityFieldLabels' => [
		'user' => $userLabels,
		'club' => $clubLabels,
	],
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);

$tooltipDetails = '<b>' . __('View details') . '</b><br>' . __('View the selected record details.');

ob_start();
if ($teamsCount > 0):
?>
<table class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table related-records-table">
	<thead>
		<tr>
			<th scope="col" class="string"><?= __('Team') ?></th>
			<th scope="col" class="string"><?= __('Club') ?></th>
			<th scope="col" class="number"><?= __('Members') ?></th>
			<th scope="col" class="datetime"><?= __('Applied') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($qualifyingTeams as $team):
			$teamName = (string)($team->subclub->name ?? ('#' . $team->id));
			$clubEntity = $team->club ?? null;
			$clubId = $clubEntity ? (string)$clubEntity->id : '';
			$clubName = (string)($clubEntity->name ?? '—');
			?>
			<tr id="related-team-<?= h((string)$team->id) ?>" data-id="<?= h((string)$team->id) ?>">
				<td class="string fw-bold"><?= h($teamName) ?></td>
				<td class="string">
					<?php if ($clubId !== ''): ?>
						<a href="#"
							class="record-modal-link"
							data-id="<?= h($clubId) ?>"
							data-get-url="<?= h($clubsGetUrl) ?>"
							data-edit-url="<?= h($clubsEditUrl) ?>"
							data-view-url="<?= h($clubsViewUrl) ?>"
							data-delete-url="<?= h($clubsDeleteUrl) ?>"
							data-delete-form-prefix="club"
							data-labels="club"
							data-title="<?= h(__('Club details')) ?>"
						><?= h($clubName) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>
					<?php else: ?>
						—
					<?php endif; ?>
				</td>
				<td class="number">
					<?= h(\App\Utility\LocaleNumberParser::format($team->user_count, decimals: 0)) ?>
					/ <?= h(\App\Utility\LocaleNumberParser::format($minimum, decimals: 0)) ?>
					<span class="badge text-bg-success ms-1"><?= __('Ready to compete') ?></span>
				</td>
				<td class="datetime">
					<?= $team->application_datetime
						? h(\App\Utility\LocaleDateParser::format($team->application_datetime, 'datetime_short'))
						: '—' ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php
endif;
$teamsTable = (string)ob_get_clean();

ob_start();
if ($applicantsCount > 0):
?>
<table
	class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table related-records-table"
	data-get-url="<?= h($membersGetUrl) ?>"
	data-edit-url="<?= h($membersEditUrl) ?>"
	data-view-url="<?= h($membersViewUrl) ?>"
	data-delete-url="<?= h($membersDeleteUrl) ?>"
	data-delete-form-prefix="member"
	data-labels="user"
	data-title="<?= h(__('Member details')) ?>"
>
	<thead>
		<tr>
			<th scope="col" class="string name"><?= __('Member') ?></th>
			<th scope="col" class="string email"><?= __('Email') ?></th>
			<th scope="col" class="string"><?= __('Club') ?></th>
			<th scope="col" class="string"><?= __('Team') ?></th>
			<th scope="col" class="string"><?= __('Status') ?></th>
			<th scope="col" class="actions"><?= __('Actions') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($qualifyingApplicants as $app):
			$userEntity = $app->user ?? null;
			$userId = $userEntity ? (string)$userEntity->id : '';
			$name = $userEntity ? MembershipProfile::displayName($userEntity) : '';
			if ($name === '') {
				$name = (string)($userEntity->email ?? $app->user_id);
			}
			$email = (string)($userEntity->email ?? '');
			$clubName = (string)($app->competitions_club->club->name ?? '—');
			$teamName = (string)($app->competitions_club->subclub->name ?? '—');
			?>
			<tr id="related-applicant-<?= h((string)$app->id) ?>" data-id="<?= h($userId) ?>" data-can-delete="0">
				<td class="string name">
					<?php if ($userId !== ''): ?>
						<a href="#"
							class="record-modal-link fw-bold"
							data-id="<?= h($userId) ?>"
							data-labels="user"
							data-title="<?= h(__('Member details')) ?>"
						><?= h($name) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>
					<?php else: ?>
						<?= h($name) ?>
					<?php endif; ?>
				</td>
				<td class="string email"><?= h($email !== '' ? $email : '—') ?></td>
				<td class="string"><?= h($clubName) ?></td>
				<td class="string"><?= h($teamName) ?></td>
				<td class="string"><?= h(CompetitionApplication::statusLabel((string)$app->status)) ?></td>
				<td class="actions">
					<?php if ($userId !== ''): ?>
						<?= $this->Html->link(
							'<i class="fa fa-eye"></i>',
							['prefix' => 'President', 'controller' => 'Members', 'action' => 'view', $userId],
							[
								'escape' => false,
								'class' => 'btn btn-sm btn-outline-primary',
								'data-bs-toggle' => 'tooltip',
								'data-bs-html' => 'true',
								'title' => $tooltipDetails,
							]
						) ?>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php
endif;
$applicantsTable = (string)ob_get_clean();
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-trophy"></i> <?= __('Competition details') ?></h3>
					<?= h((string)$competition->name) ?>
				</div>
				<div class="float-right">
					<a role="button" href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<div class="competition-view-layout">
					<?php /* Left (desktop) / top (mobile): meta */ ?>
					<div class="c-meta">
						<dl class="row record-view-fields mb-0">
							<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h((string)$competition->id) ?></dd></div>
							<div class="record-view-row"><dt><?= __('Name') ?></dt><dd><?= h(\App\Utility\CompetitionTextRender::field($competition, 'name')) ?></dd></div>
							<div class="record-view-row"><dt><?= __('Title') ?></dt><dd><?= h(\App\Utility\CompetitionTextRender::field($competition, 'title')) ?></dd></div>
							<div class="record-view-row"><dt><?= __('Subtitle') ?></dt><dd><?= h((string)$competition->subtitle) ?></dd></div>
						</dl>
							<?= $this->element('competitions/staff_under_title', [
								'competitionStaffGroups' => $competitionStaffGroups ?? null,
								'competitionId' => (string)$competition->id,
								'staffModal' => [
									'getUrl' => $membersGetUrl,
									'editUrl' => $membersEditUrl,
									'viewUrl' => $membersViewUrl,
									'deleteUrl' => $membersDeleteUrl,
									'labels' => 'user',
									'title' => __('Member details'),
								],
							]) ?>
						<dl class="row record-view-fields mb-0">
							<div class="record-view-row"><dt><?= __('Club') ?></dt><dd><?= h((string)($competition->club->name ?? '')) ?></dd></div>
							<?php
							$venueVars = \App\Utility\CompetitionTextRender::vars($competition);
							$venueName = trim((string)($venueVars['venue_name'] ?? ''));
							if ($venueName !== ''):
							?>
							<div class="record-view-row"><dt><?= __('Venue name') ?></dt><dd><?= h($venueName) ?></dd></div>
							<?php endif; ?>
							<?php if (($venueVars['venue'] ?? '') !== ''): ?>
							<div class="record-view-row"><dt><?= __('Venue') ?></dt><dd><?= h($venueVars['venue']) ?></dd></div>
							<?php endif; ?>
							<div class="record-view-row"><dt><?= __('National') ?></dt><dd><?= !empty($competition->national_competition) ? $yes : $no ?></dd></div>
							<div class="record-view-row"><dt><?= __('Application from') ?></dt><dd><?= $competition->first_date_of_application ? h(\App\Utility\LocaleDateParser::format($competition->first_date_of_application, 'date')) : '—' ?></dd></div>
							<div class="record-view-row"><dt><?= __('Application deadline') ?></dt><dd><?= $competition->application_deadline ? h(\App\Utility\LocaleDateParser::format($competition->application_deadline, 'date')) : '—' ?></dd></div>
							<div class="record-view-row"><dt><?= __('Competition datetime') ?></dt><dd><?= $competition->competition_datetime ? h(\App\Utility\LocaleDateParser::format($competition->competition_datetime, 'datetime_short')) : '—' ?></dd></div>
							<div class="record-view-row"><dt><?= __('Start') ?></dt><dd><?= $competition->start_datetime ? h(\App\Utility\LocaleDateParser::format($competition->start_datetime, 'datetime_short')) : '—' ?></dd></div>
							<div class="record-view-row"><dt><?= __('End') ?></dt><dd><?= $competition->end_datetime ? h(\App\Utility\LocaleDateParser::format($competition->end_datetime, 'datetime_short')) : '—' ?></dd></div>
							<div class="record-view-row"><dt><?= __('Min. team size') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($minimum, decimals: 0)) ?></dd></div>
							<div class="record-view-row"><dt><?= __('Pipe type') ?></dt><dd><?= h(\App\Utility\CompetitionTextRender::field($competition, 'pipe_type') ?: '—') ?></dd></div>
							<div class="record-view-row"><dt><?= __('Pipe parameters') ?></dt><dd><?= h(\App\Utility\CompetitionTextRender::field($competition, 'pipe_parameters') ?: '—') ?></dd></div>
							<div class="record-view-row"><dt><?= __('Tobacco type') ?></dt><dd><?= h(\App\Utility\CompetitionTextRender::field($competition, 'tobacco_type') ?: '—') ?></dd></div>
							<div class="record-view-row"><dt><?= __('Tobacco weight') ?></dt><dd><?php
								$tw = \App\Utility\CompetitionTextRender::vars($competition)['tobacco_weight'] ?? '';
								echo h($tw !== '' ? $tw : '—');
							?></dd></div>
							<div class="record-view-row">
								<dt><?= __('Competing sub-teams') ?></dt>
								<dd>
									<?= h(\App\Utility\LocaleNumberParser::format($teamsCount, decimals: 0)) ?>
									<span class="text-muted small ms-1"><?= __('(minimum roster reached)') ?></span>
								</dd>
							</div>
							<div class="record-view-row">
								<dt><?= __('Competing applicants') ?></dt>
								<dd>
									<?= h(\App\Utility\LocaleNumberParser::format($applicantsCount, decimals: 0)) ?>
									<span class="text-muted small ms-1"><?= __('(on qualifying sub-teams)') ?></span>
								</dd>
							</div>
							<div class="record-view-row"><dt><?= __('Visible') ?></dt><dd><?= !empty($competition->visible) ? $yes : $no ?></dd></div>
							<div class="record-view-row"><dt><?= __('Position') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($competition->pos, decimals: 0)) ?></dd></div>
						</dl>
					</div>

					<?php /* Right (desktop) / after meta (mobile): description */ ?>
					<div class="c-desc">
						<h5 class="mb-2"><?= __('Description') ?></h5>
						<div class="competition-description">
							<?= $this->element('competitions/description_rendered', [
								'competition' => $competition,
							]) ?>
						</div>
					</div>
				</div>
			</div>
			<div class="card-footer">
				<div class="record-view-footer-actions">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . __('Edit'),
						['action' => 'edit', $competition->id],
						['escape' => false, 'class' => 'btn btn-primary']
					) ?>
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-cash-register"></i></span>' . __('Cash desk'),
						['controller' => 'CompetitionCash', 'action' => 'view', $competition->id],
						['escape' => false, 'class' => 'btn btn-outline-success']
					) ?>
				</div>
			</div>
		</div>

		<?= $this->element('admin/view_related_tabs', [
			'relatedTabs' => [
				[
					'id' => 'sub-teams',
					'title' => __('Sub-teams'),
					'count' => $teamsCount,
					'table' => $teamsTable,
				],
				[
					'id' => 'applicants',
					'title' => __('Applicants'),
					'count' => $applicantsCount,
					'table' => $applicantsTable,
				],
			],
		]) ?>
	</div>
</div>

<?= $this->element('admin/modal_linked_record_view') ?>
