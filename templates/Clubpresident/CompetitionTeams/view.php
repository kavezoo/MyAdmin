<?php
/**
 * Clubpresident — sub-team view + related applicants tab (CRUD / actions).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CompetitionsClub $team
 * @var iterable<\App\Model\Entity\CompetitionsUser> $members
 * @var int $minimum
 * @var bool $meetsMinimum
 * @var bool $canDelete
 */
use App\Auth\MembershipProfile;
use App\Utility\CompetitionApplication;

$this->Html->css(['pages/index'], ['block' => true]);
$this->Html->script(['pages/index'], ['block' => 'scriptBottom']);

$canDelete = (bool)($canDelete ?? ((int)$team->user_count === 0));
$membersList = is_array($members) ? $members : iterator_to_array($members);
$membersCount = count($membersList);

$applicantsGetUrl = $this->Url->build(['prefix' => 'Clubpresident', 'controller' => 'CompetitionApplicants', 'action' => 'recordGet']);
$applicantsEditUrl = $this->Url->build(['prefix' => 'Clubpresident', 'controller' => 'CompetitionApplicants', 'action' => 'edit']);
$applicantsViewUrl = $this->Url->build(['prefix' => 'Clubpresident', 'controller' => 'CompetitionApplicants', 'action' => 'edit']);

$tooltipDetails = '<b>' . __('View details') . '</b><br>' . __('View the selected record details.');
$tooltipEdit = '<b>' . __('Edit') . '</b><br>' . __('Edit the selected record.');
$tooltipUnassign = '<b>' . __('Remove from team') . '</b><br>' . __('Move the member back to unassigned applicants.');
$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.');
$tooltipDeleteBlocked = '<b>' . __('Delete') . '</b><br>' . __('Cannot delete this sub-team because it already has assigned members.');

$config = [
	'rowDoubleClickAction' => 'modal',
	'deleteUrl' => $this->Url->build(['action' => 'delete']),
	'entityFieldLabels' => [
		'applicant' => [
			'id' => __('ID'),
			'member' => __('Member'),
			'email' => __('Email'),
			'status' => __('Status'),
			'lunch' => __('Lunch'),
			'modified' => __('Assigned'),
		],
	],
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);

ob_start();
if ($membersCount > 0):
?>
<table
	class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table related-records-table"
	data-get-url="<?= h($applicantsGetUrl) ?>"
	data-edit-url="<?= h($applicantsEditUrl) ?>"
	data-view-url="<?= h($applicantsViewUrl) ?>"
	data-labels="applicant"
	data-title="<?= h(__('Applicant details')) ?>"
>
	<thead>
		<tr>
			<th scope="col" class="string name"><?= __('Member') ?></th>
			<th scope="col" class="string email"><?= __('Email') ?></th>
			<th scope="col" class="string status"><?= __('Status') ?></th>
			<th scope="col" class="datetime modified"><?= __('Assigned') ?></th>
			<th scope="col" class="actions"><?= __('Actions') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($membersList as $app):
			$userEntity = $app->user ?? null;
			$name = $userEntity ? MembershipProfile::displayName($userEntity) : '';
			if ($name === '') {
				$name = (string)($userEntity->email ?? $app->user_id);
			}
			$email = (string)($userEntity->email ?? '');
			?>
			<tr id="related-applicant-<?= (int)$app->id ?>" data-id="<?= (int)$app->id ?>" data-can-delete="0">
				<td class="string name">
					<a href="#"
						class="record-modal-link fw-bold"
						data-id="<?= (int)$app->id ?>"
						data-labels="applicant"
						data-title="<?= h(__('Applicant details')) ?>"
					><?= h($name) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>
				</td>
				<td class="string email"><?= h($email !== '' ? $email : '—') ?></td>
				<td class="string status"><?= h(CompetitionApplication::statusLabel((string)$app->status)) ?></td>
				<td class="datetime modified">
					<?= $app->modified ? h(\App\Utility\LocaleDateParser::format($app->modified, 'datetime_short')) : '—' ?>
				</td>
				<td class="actions">
					<?= $this->Html->link(
						'<i class="fa fa-pencil"></i>',
						['prefix' => 'Clubpresident', 'controller' => 'CompetitionApplicants', 'action' => 'edit', $app->id],
						[
							'escape' => false,
							'class' => 'btn btn-sm btn-outline-primary',
							'title' => $tooltipEdit,
							'data-bs-toggle' => 'tooltip',
							'data-bs-html' => 'true',
						]
					) ?>
					<?= $this->Form->create(null, [
						'url' => ['action' => 'unassign', $team->id],
						'class' => 'd-inline',
					]) ?>
						<?= $this->Form->hidden('user_id', ['value' => $app->user_id]) ?>
						<button type="submit"
							class="btn btn-sm btn-outline-warning"
							data-bs-toggle="tooltip"
							data-bs-html="true"
							title="<?= h($tooltipUnassign) ?>">
							<i class="fa fa-user-times"></i>
						</button>
					<?= $this->Form->end() ?>
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
					<h3><i class="fa fa-eye"></i> <?= __('Sub-team details') ?></h3>
					<?= __('View the selected record (read-only).') ?>
				</div>
				<div class="float-right">
					<a role="button" href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<dl class="row record-view-fields mb-0">
					<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h((string)$team->id) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Competition') ?></dt><dd><?= h((string)($team->competition->name ?? '—')) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Team') ?></dt><dd><?= h((string)($team->subclub->name ?? '—')) ?></dd></div>
					<div class="record-view-row">
						<dt><?= __('Members') ?></dt>
						<dd>
							<?= h(\App\Utility\LocaleNumberParser::format($team->user_count, decimals: 0)) ?>
							/ <?= __('minimum') ?>
							<?= h(\App\Utility\LocaleNumberParser::format($minimum, decimals: 0)) ?>
							<?php if ($meetsMinimum): ?>
								<span class="badge text-bg-success ms-2"><?= __('Ready to compete') ?></span>
							<?php else: ?>
								<span class="badge text-bg-warning ms-2"><?= __('Below minimum') ?></span>
							<?php endif; ?>
						</dd>
					</div>
					<div class="record-view-row">
						<dt><?= __('Applied') ?></dt>
						<dd><?= $team->application_datetime ? h(\App\Utility\LocaleDateParser::format($team->application_datetime, 'datetime_short')) : '—' ?></dd>
					</div>
					<div class="record-view-row">
						<dt><?= __('Visible') ?></dt>
						<dd><?= !empty($team->visible) ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>' ?></dd>
					</div>
					<div class="record-view-row">
						<dt><?= __('Position') ?></dt>
						<dd><?= h(\App\Utility\LocaleNumberParser::format($team->pos, decimals: 0)) ?></dd>
					</div>
					<div class="record-view-row">
						<dt><?= __('Created') ?></dt>
						<dd><?= $team->created ? h(\App\Utility\LocaleDateParser::format($team->created, 'datetime_short')) : '—' ?></dd>
					</div>
					<div class="record-view-row">
						<dt><?= __('Modified') ?></dt>
						<dd><?= $team->modified ? h(\App\Utility\LocaleDateParser::format($team->modified, 'datetime_short')) : '—' ?></dd>
					</div>
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
						'<span class="btn-label"><i class="fa fa-user-plus"></i></span>' . __('Assign applicants'),
						['action' => 'applicants', $team->id],
						['escape' => false, 'class' => 'btn btn-outline-primary']
					) ?>
					<?php if ($canDelete): ?>
						<?= $this->Form->create(null, ['url' => ['action' => 'delete', $team->id], 'id' => 'delete-form-' . (int)$team->id, 'class' => 'd-inline']) ?>
						<a role="button" href="#" class="btn btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-html="true" title="<?= h($tooltipDelete) ?>" data-id="<?= (int)$team->id ?>">
							<span class="btn-label"><i class="fa fa-trash"></i></span><?= __('Delete') ?>
						</a>
						<?= $this->Form->end() ?>
					<?php else: ?>
						<span class="d-inline-block" tabindex="-1" data-bs-toggle="tooltip" data-bs-html="true" title="<?= h($tooltipDeleteBlocked) ?>">
							<a role="button" href="#" class="btn btn-secondary disabled" tabindex="-1" aria-disabled="true">
								<span class="btn-label"><i class="fa fa-trash"></i></span><?= __('Delete') ?>
							</a>
						</span>
					<?php endif; ?>
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-list"></i></span>' . __('Back to list'),
						$this->get('indexListUrl') ?? ['action' => 'index'],
						['escape' => false, 'class' => 'btn btn-outline-secondary']
					) ?>
				</div>
			</div>
		</div>

		<?= $this->element('admin/view_related_tabs', [
			'relatedTabs' => [
				[
					'id' => 'applicants',
					'title' => __('Team members'),
					'count' => $membersCount,
					'table' => $applicantsTable,
					'toolbar' => $this->Html->link(
						'<span class="btn-label"><i class="fa fa-user-plus"></i></span>' . __('Assign applicants'),
						['action' => 'applicants', $team->id],
						['escape' => false, 'class' => 'btn btn-primary btn-sm']
					),
				],
			],
		]) ?>
	</div>
</div>

<?= $this->element('admin/modal_linked_record_view') ?>
