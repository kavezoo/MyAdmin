<?php
/**
 * Admin — global users index (optional country filter).
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\User> $users
 * @var int $filterCountryId
 * @var string $filterCountryLabel
 * @var array<int, string> $countryOptions
 * @var array<string, true> $deletableUserIds
 */
use App\Auth\AppRoles;
use App\Auth\MembershipProfile;

$filterCountryId = (int)($filterCountryId ?? 0);
$filterCountryLabel = (string)($filterCountryLabel ?? '');
$countryOptions = $countryOptions ?? [];
$deletableUserIds = $deletableUserIds ?? [];

$this->Html->css(['pages/index', 'pages/users_list_avatar'], ['block' => true]);

$filterQuery = $this->request->getQueryParams();
unset($filterQuery['country_id']);
$filterQuery['page'] = '1';

$rowDoubleClickAction = 'modal';

$numberDecimals = [
	'integer' => 0,
	'decimal' => 2,
];

$showIdColumn = false; // UUID PK — too wide for the list
$showClubColumn = true;
$showRoleColumn = true;
$showActiveColumn = true;
$showEnabledColumn = true;

$indexColspan = 3; // country, name, email, actions
if ($showIdColumn) {
	$indexColspan++;
}
if ($showClubColumn) {
	$indexColspan++;
}
if ($showRoleColumn) {
	$indexColspan++;
}
if ($showActiveColumn) {
	$indexColspan++;
}
if ($showEnabledColumn) {
	$indexColspan++;
}

$clubGetUrl = $this->Url->build(['controller' => 'Clubs', 'action' => 'recordGet']);
$clubEditUrl = $this->Url->build(['controller' => 'Clubs', 'action' => 'edit']);
$clubViewUrl = $this->Url->build(['controller' => 'Clubs', 'action' => 'view']);
$clubDeleteUrl = $this->Url->build(['controller' => 'Clubs', 'action' => 'delete']);

$tooltipDetails = '<b>' . __('View details') . '</b><br>' . __('View the selected record details.');
$tooltipEdit = '<b>' . __('Edit') . '</b><br>' . __('Edit the selected record.');
$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.');
$tooltipDeleteBlocked = '<b>' . __('Delete') . '</b><br>' . __('Cannot delete this record because it has related child records.');

$rowDoubleClickHint = __('Double-click a row to view the record details.');

$userRecordLabels = [
	'id' => __('ID'),
	'first_name' => __('Name'),
	'email' => __('Email'),
	'phone' => __('Phone'),
	'role' => __('Role'),
	'country' => __('Country'),
	'club' => __('Club'),
	'active' => __('Active'),
	'enabled' => __('Enabled'),
	'membership_joined_date' => __('Member since'),
	'club_membership_fee_date' => __('Club membership fee'),
	'national_membership_fee_date' => __('National membership fee'),
	'created' => __('Created'),
	'modified' => __('Modified'),
];

$config = [
	'rowDoubleClickAction' => $rowDoubleClickAction,
	'recordGetUrl' => $this->Url->build(['action' => 'recordGet']),
	'categoryGetUrl' => $clubGetUrl,
	'editUrl' => $this->Url->build(['action' => 'edit']),
	'viewUrl' => $this->Url->build(['action' => 'view']),
	'deleteUrl' => $this->Url->build(['action' => 'delete']),
	'recordFieldLabels' => $userRecordLabels,
	'categoryFieldLabels' => [
		'id' => __('ID'),
		'name' => __('Name'),
		'short_name' => __('Short name'),
		'country' => __('Country'),
		'city' => __('City'),
		'enabled' => __('Enabled'),
		'visible' => __('Visible'),
		'user_count' => __('Members'),
		'created' => __('Created'),
		'modified' => __('Modified'),
	],
	'entityFieldLabels' => [
		'user' => $userRecordLabels,
		'club' => [
			'id' => __('ID'),
			'name' => __('Name'),
			'short_name' => __('Short name'),
			'country' => __('Country'),
			'city' => __('City'),
			'enabled' => __('Enabled'),
			'visible' => __('Visible'),
			'user_count' => __('Members'),
			'created' => __('Created'),
			'modified' => __('Modified'),
		],
	],
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);
$this->Html->script(['pages/index'], ['block' => 'scriptBottom']);

$indexSearch = (string)($this->get('indexSearch') ?? '');
$qKey = \App\Utility\AdminSearch::queryParam();
$tooltipSearch = h('<b>' . __('Start search') . '</b><br>' . __('Search in the text fields of this list.'));
$tooltipClear = h('<b>' . __('Clear search') . '</b><br>' . __('Clear the saved search and return to the last visited record.'));
$hasSearch = $indexSearch !== '';
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-users"></i> <?= __('Users') ?></h3>
					<?= h($rowDoubleClickHint) ?>
					<?php if ($filterCountryLabel !== ''): ?>
						<div class="text-muted"><?= h(__('Showing users for {0}', $filterCountryLabel)) ?></div>
					<?php endif; ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-plus"></i></span>' . __('New user'),
						['action' => 'add'],
						['escape' => false, 'class' => 'btn btn-sm btn-success']
					) ?>
					<?= $this->element('admin/index_country_scope') ?>
					<span class="index-header-sep" aria-hidden="true">|</span>
					<form method="get" class="table-search" role="search">
						<input type="hidden" name="country_id" value="<?= (int)$filterCountryId ?>">
						<input type="search"
							class="form-control form-control-sm table-search-input"
							id="table-search-input"
							name="<?= h($qKey) ?>"
							value="<?= h($indexSearch) ?>"
							placeholder="<?= h(__('Search...')) ?>"
							autocomplete="off">
						<button type="submit"
							class="btn btn-sm btn-outline-secondary table-search-btn"
							data-bs-toggle="tooltip"
							data-bs-placement="bottom"
							data-bs-html="true"
							title="<?= $tooltipSearch ?>">
							<i class="fa fa-search" aria-hidden="true"></i>
							<span class="visually-hidden"><?= h(__('Start search')) ?></span>
						</button>
						<?php if ($hasSearch): ?>
							<a href="<?= h($this->Url->build(['?' => ['clear_search' => '1', 'country_id' => (string)$filterCountryId]])) ?>"
								class="btn btn-sm btn-outline-secondary table-search-btn table-search-clear"
								role="button"
								data-bs-toggle="tooltip"
								data-bs-placement="bottom"
								data-bs-html="true"
								title="<?= $tooltipClear ?>">
								<i class="fa fa-times" aria-hidden="true"></i>
								<span class="visually-hidden"><?= h(__('Clear search')) ?></span>
							</a>
						<?php endif; ?>
					</form>
					<?= $this->element('admin/index_pagination') ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-2">
				<div class="table-responsive">
					<table class="table table-bordered table-hover table-striped mb-0 align-middle index-data-table">
						<thead>
							<tr>
								<?php if ($showIdColumn): ?>
									<th scope="col" class="number id"><?= $this->Paginator->sort('Users.id', '#') ?></th>
								<?php endif; ?>
								<th scope="col" class="string country"><?= $this->Paginator->sort('Countries.name', __('Country')) ?></th>
								<?php if ($showClubColumn): ?>
									<th scope="col" class="string category-id"><?= $this->Paginator->sort('Clubs.name', __('Club')) ?></th>
								<?php endif; ?>
								<th scope="col" class="string name"><?= $this->Paginator->sort('Users.first_name', __('Name')) ?></th>
								<th scope="col" class="string email"><?= $this->Paginator->sort('Users.email', __('Email')) ?></th>
								<?php if ($showRoleColumn): ?>
									<th scope="col" class="string role"><?= $this->Paginator->sort('Users.role', __('Role')) ?></th>
								<?php endif; ?>
								<?php if ($showActiveColumn): ?>
									<th scope="col" class="boolean active"><?= $this->Paginator->sort('Users.active', __('Active')) ?></th>
								<?php endif; ?>
								<?php if ($showEnabledColumn): ?>
									<th scope="col" class="boolean enabled"><?= $this->Paginator->sort('Users.enabled', __('Enabled')) ?></th>
								<?php endif; ?>
								<th scope="col" class="actions"><?= __('Actions') ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($users as $user):
								$userId = (string)$user->id;
								$displayName = MembershipProfile::displayName($user);
								if ($displayName === '') {
									$displayName = (string)($user->email ?? '');
								}
								$roleKey = strtolower(trim((string)($user->role ?? '')));
								$roleLabel = $roleKey !== '' ? AppRoles::label($roleKey) : '—';
								$clubLabel = '';
								$clubIdRow = (int)($user->club_id ?? 0);
								if ($user->club !== null && trim((string)$user->club->name) !== '') {
									$clubLabel = (string)$user->club->name;
								}
								$canDeleteRow = !empty($deletableUserIds[$userId]);
								$isLastVisited = isset($lastVisitedId) && (string)$lastVisitedId === $userId;
								?>
								<tr id="record-<?= h($userId) ?>"
									data-id="<?= h($userId) ?>"
									data-can-delete="<?= $canDeleteRow ? '1' : '0' ?>"<?= $isLastVisited ? ' class="last-visited"' : '' ?>>
									<?php if ($showIdColumn): ?>
										<td class="number id"><?= h($userId) ?></td>
									<?php endif; ?>
									<td class="string country"><?= h(\App\Utility\AdminCountry::label((int)$user->country_id)) ?></td>
									<?php if ($showClubColumn): ?>
										<td class="string category-id">
											<?php if ($clubIdRow > 0 && $clubLabel !== ''): ?>
												<a href="#"
													class="category-link record-modal-link"
													data-id="<?= (int)$clubIdRow ?>"
													data-get-url="<?= h($clubGetUrl) ?>"
													data-edit-url="<?= h($clubEditUrl) ?>"
													data-view-url="<?= h($clubViewUrl) ?>"
													data-delete-url="<?= h($clubDeleteUrl) ?>"
													data-labels="club"
													data-title="<?= h(__('Club details')) ?>"
												><?= h($clubLabel) ?><span class="category-link-icon record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>
											<?php else: ?>
												—
											<?php endif; ?>
										</td>
									<?php endif; ?>
									<td class="string name">
										<?= $this->element('users/list_name_cell', [
											'user' => $user,
											'displayName' => $displayName,
											'showRole' => false,
										]) ?>
									</td>
									<td class="string email"><?= h((string)$user->email) ?></td>
									<?php if ($showRoleColumn): ?>
										<td class="string role"><?= h($roleLabel) ?></td>
									<?php endif; ?>
									<?php if ($showActiveColumn): ?>
										<td class="boolean active text-center">
											<?= !empty($user->active)
												? '<i class="fa fa-check text-success"></i>'
												: '<i class="fa fa-times text-danger"></i>' ?>
										</td>
									<?php endif; ?>
									<?php if ($showEnabledColumn): ?>
										<td class="boolean enabled text-center">
											<?= (int)($user->enabled ?? 0) === 1
												? '<i class="fa fa-check text-success"></i>'
												: '<i class="fa fa-times text-danger"></i>' ?>
										</td>
									<?php endif; ?>
									<td class="actions">
										<?= $this->Html->link(
											'<i class="fa fa-eye"></i>',
											['action' => 'view', $userId],
											[
												'escape' => false,
												'role' => 'button',
												'class' => 'btn btn-outline-info',
												'data-bs-toggle' => 'tooltip',
												'data-bs-placement' => 'top',
												'data-bs-html' => 'true',
												'title' => $tooltipDetails,
											]
										) ?>
										<?= $this->Html->link(
											'<i class="fa fa-pencil"></i>',
											['action' => 'edit', $userId],
											[
												'escape' => false,
												'role' => 'button',
												'class' => 'btn btn-outline-primary',
												'data-bs-toggle' => 'tooltip',
												'data-bs-placement' => 'top',
												'data-bs-html' => 'true',
												'title' => $tooltipEdit,
											]
										) ?>
										<?php if ($canDeleteRow): ?>
											<a role="button" href="#" class="btn btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDelete) ?>" data-id="<?= h($userId) ?>">
												<i class="fa fa-trash"></i>
											</a>
											<?= $this->Form->create(null, [
												'url' => ['action' => 'delete', $userId],
												'id' => 'delete-form-' . $userId,
												'class' => 'd-none js-row-delete-form',
											]) ?>
											<?= $this->Form->end() ?>
										<?php else: ?>
											<span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDeleteBlocked) ?>">
												<a role="button" href="#" class="btn btn-secondary disabled" tabindex="-1" aria-disabled="true">
													<i class="fa fa-trash"></i>
												</a>
											</span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
							<?php if ($users->count() === 0): ?>
								<tr>
									<td colspan="<?= (int)$indexColspan ?>" class="text-center text-muted py-4"><?= __('No records found.') ?></td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
			<div class="card-footer">
				<?= $this->element('admin/index_footer') ?>
			</div>
		</div>
	</div>
</div>
<?= $this->element('admin/modal_record_view') ?>
<?= $this->element('admin/modal_linked_record_view') ?>
