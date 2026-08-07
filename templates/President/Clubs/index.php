<?php
/**
 * President — clubs index (country-scoped) + national association fee.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Club> $clubs
 * @var array<int, \Cake\Datasource\EntityInterface> $clubPresidents
 * @var string $countryLabel
 * @var int $countryId
 * @var int $membershipYear
 */
use App\Auth\MembershipProfile;
use App\Utility\MembershipFee;

$this->Html->css(['pages/index', 'pages/membership_fee', 'pages/club_logo'], ['block' => true]);

/**
 * Row double-click: 'modal' | 'edit' | 'none'
 */
$rowDoubleClickAction = 'modal'; // 'modal' | 'edit' | 'none'

$numberDecimals = [
	'integer' => 0,
	'decimal' => 2,
];

/**
 * Optional index columns (true = show, false = hide).
 */
$showIdColumn = true;
$showPosColumn = true;
$showEnabledColumn = true;
$showVisibleColumn = true;
$showCountColumn = true;
$showCreatedColumn = true;
$showModifiedColumn = true;

$countryLabel = (string)($countryLabel ?? '');
$countryId = (int)($countryId ?? 0);
$membershipYear = (int)($membershipYear ?? MembershipFee::currentYear());
$clubEntityFeeLabel = MembershipFee::clubEntityFeeLabel($countryId);

$showTimestampColumn = $showCreatedColumn || $showModifiedColumn;
$indexColspan = 5; // name, city, club president, national fee, actions
if ($showIdColumn) {
	$indexColspan++;
}
if ($showPosColumn) {
	$indexColspan++;
}
if ($showEnabledColumn) {
	$indexColspan++;
}
if ($showVisibleColumn) {
	$indexColspan++;
}
if ($showCountColumn) {
	$indexColspan++;
}
if ($showTimestampColumn) {
	$indexColspan++;
}

$tooltipDetails = '<b>' . __('View details') . '</b><br>' . __('View the selected record details.');
$tooltipEdit = '<b>' . __('Edit') . '</b><br>' . __('Edit the selected record.');
$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.');
$tooltipDeleteBlocked = '<b>' . __('Delete') . '</b><br>' . __('Cannot delete this record because it has related child records.');

$rowDoubleClickHints = [
	'modal' => __('Double-click a row to view the record details.'),
	'edit' => __('Double-click a row to edit the record.'),
	'none' => '',
];
$rowDoubleClickHint = $rowDoubleClickHints[$rowDoubleClickAction] ?? $rowDoubleClickHints['modal'];

$userLabels = [
	'id' => __('ID'),
	'first_name' => __('Name'),
	'email' => __('Email'),
	'phone' => __('Phone'),
	'role' => __('Role'),
	'country' => __('Country'),
	'club' => __('Club'),
	'active' => __('Active'),
	'enabled' => __('Enabled'),
	MembershipProfile::FIELD_JOINED => __('Member since'),
	MembershipFee::FIELD_CLUB => MembershipFee::clubFeeLabel($countryId),
	MembershipFee::FIELD_NATIONAL => MembershipFee::nationalFeeLabel($countryId),
	'created' => __('Created'),
	'modified' => __('Modified'),
];

$config = [
	'rowDoubleClickAction' => $rowDoubleClickAction,
	'recordGetUrl' => $this->Url->build(['action' => 'recordGet']),
	'editUrl' => $this->Url->build(['action' => 'edit']),
	'viewUrl' => $this->Url->build(['action' => 'view']),
	'deleteUrl' => $this->Url->build(['action' => 'delete']),
	'recordFieldLabels' => [
		'id' => __('ID'),
		'name' => __('Name'),
		'short_name' => __('Short name'),
		'country' => __('Country'),
		'city' => __('City'),
		'address' => __('Address'),
		'email' => __('Email'),
		'phone' => __('Phone'),
		'web' => __('Website'),
		'facebook' => __('Facebook'),
		'insta' => __('Instagram'),
		'club_president' => __('Club president'),
		'enabled' => __('Enabled'),
		'visible' => __('Visible'),
		'pos' => __('Position'),
		'user_count' => __('Members'),
		MembershipFee::FIELD_CLUB_ENTITY => $clubEntityFeeLabel,
		'created' => __('Created'),
		'modified' => __('Modified'),
	],
	'entityFieldLabels' => [
		'user' => $userLabels,
	],
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);
$this->Html->script(['pages/index', 'pages/president_clubs'], ['block' => 'scriptBottom']);

$clubPresidents = $clubPresidents ?? [];

$membersGetUrl = $this->Url->build(['action' => 'userRecordGet']);
$membersEditUrl = $this->Url->build(['prefix' => 'President', 'controller' => 'Members', 'action' => 'edit']);
$membersViewUrl = $this->Url->build(['prefix' => 'President', 'controller' => 'Members', 'action' => 'view']);
$membersDeleteUrl = $this->Url->build(['prefix' => 'President', 'controller' => 'Members', 'action' => 'delete']);
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-sitemap"></i> <?= __('Clubs') ?></h3>
					<?php if ($countryLabel !== ''): ?>
						<div class="text-muted"><?= h(__('Clubs in {0} — membership year {1}', $countryLabel, $membershipYear)) ?></div>
					<?php endif; ?>
					<?php if ($rowDoubleClickHint !== ''): ?>
						<?= h($rowDoubleClickHint) ?>
					<?php endif; ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2">
					<?= $this->element('admin/table_search') ?>
					<?= $this->element('admin/index_pagination') ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-2">
				<table class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table">
					<thead>
						<tr>
							<?php if ($showIdColumn): ?>
								<th scope="col" class="number id"><?= $this->Paginator->sort('id', '#') ?></th>
							<?php endif; ?>
							<th scope="col" class="string name"><?= $this->Paginator->sort('name', __('Name')) ?></th>
							<th scope="col" class="string city"><?= __('City') ?></th>
							<th scope="col" class="string club-president"><?= __('Club president') ?></th>
							<th scope="col" class="date national-fee text-center"><?= $this->Paginator->sort(MembershipFee::FIELD_CLUB_ENTITY, h($clubEntityFeeLabel) . ' (' . h($membershipYear) . ')') ?></th>
							<?php if ($showPosColumn): ?>
								<th scope="col" class="number pos"><?= $this->Paginator->sort('pos', __('Position')) ?></th>
							<?php endif; ?>
							<?php if ($showEnabledColumn): ?>
								<th scope="col" class="boolean enabled"><?= $this->Paginator->sort('enabled', __('Enabled')) ?></th>
							<?php endif; ?>
							<?php if ($showVisibleColumn): ?>
								<th scope="col" class="boolean visible"><?= $this->Paginator->sort('visible', __('Visible')) ?></th>
							<?php endif; ?>
							<?php if ($showCountColumn): ?>
								<th scope="col" class="number count"><?= $this->Paginator->sort('user_count', __('Members')) ?></th>
							<?php endif; ?>
							<?php if ($showTimestampColumn): ?>
								<th scope="col" class="datetime<?= $showCreatedColumn ? ' created' : '' ?><?= $showModifiedColumn ? ' modified' : '' ?>">
									<?php if ($showCreatedColumn): ?>
										<?= $this->Paginator->sort('created', __('Created')) ?>
									<?php endif; ?>
									<?php if ($showModifiedColumn): ?>
										<?= $this->Paginator->sort('modified', __('Modified')) ?>
									<?php endif; ?>
								</th>
							<?php endif; ?>
							<th scope="col" class="actions"><?= __('Actions') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($clubs as $club): ?>
							<?php
							$userCount = (int)($club->user_count ?? 0);
							$canDeleteRow = $userCount === 0;
							$isLastVisited = isset($lastVisitedId) && (int)$lastVisitedId === (int)$club->id;
							$president = $clubPresidents[(int)$club->id] ?? null;
							$feeDate = $club->get(MembershipFee::FIELD_CLUB_ENTITY);
							$feePaid = MembershipFee::isPaidForYear($feeDate, $membershipYear);
							$feeDateFormatted = MembershipFee::paidDateFormatted($feeDate, $membershipYear);
							$feeLastFormatted = MembershipFee::lastPaymentFormatted($feeDate);
							$formId = 'club-national-fee-form-' . (int)$club->id;
							?>
							<tr id="record-<?= (int)$club->id ?>" data-id="<?= (int)$club->id ?>" data-can-delete="<?= $canDeleteRow ? '1' : '0' ?>"<?= $isLastVisited ? ' class="last-visited"' : '' ?>>
								<?php if ($showIdColumn): ?>
									<td class="number id"><?= h($club->id) ?></td>
								<?php endif; ?>
								<td class="string name">
									<div class="club-index-name-with-logo">
										<?php
										$logoStored = $club->get('logo');
										$clubLogoUrl = \App\Utility\ClubLogo::publicUrlFor(
											(int)$club->id,
											is_string($logoStored) ? $logoStored : null
										);
										?>
										<?php if ($clubLogoUrl !== ''): ?>
											<img src="<?= h($clubLogoUrl) ?>" alt="" class="club-logo-preview--sm" width="36" height="36">
										<?php else: ?>
											<span class="club-logo-placeholder--sm d-inline-flex align-items-center justify-content-center text-secondary" aria-hidden="true">
												<i class="fa fa-shield"></i>
											</span>
										<?php endif; ?>
										<span><?= h($club->name) ?></span>
									</div>
								</td>
								<td class="string city">
									<?php
									$cityName = '';
									if (!empty($club->city)) {
										$zip = trim((string)($club->city->zip ?? ''));
										$cityName = trim((string)$club->city->name);
										if ($zip !== '') {
											$cityName .= ' (' . $zip . ')';
										}
									}
									echo $cityName !== '' ? h($cityName) : '—';
									?>
								</td>
								<td class="string club-president">
									<?php if ($president !== null): ?>
										<a href="#"
											class="record-modal-link"
											data-id="<?= h((string)$president->get('id')) ?>"
											data-get-url="<?= h($membersGetUrl) ?>"
											data-edit-url="<?= h($membersEditUrl) ?>"
											data-view-url="<?= h($membersViewUrl) ?>"
											data-delete-url="<?= h($membersDeleteUrl) ?>"
											data-delete-form-prefix="member"
											data-labels="user"
											data-title="<?= h(__('Club president')) ?>"
										><?= h(MembershipProfile::displayName($president)) ?><span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span></a>
									<?php endif; ?>
								</td>
								<td class="date national-fee text-center">
									<?= $this->Form->create(null, [
										'url' => ['action' => 'updateNationalFee', $club->id],
										'id' => $formId,
										'class' => 'membership-fee-hidden-form',
									]) ?>
									<?= $this->Form->end() ?>
									<?= $this->element('users/membership_fee_status', [
										'label' => $clubEntityFeeLabel,
										'paid' => $feePaid,
										'membershipYear' => $membershipYear,
										'dateFormatted' => $feeDateFormatted,
										'lastPaymentDateFormatted' => $feeLastFormatted,
										'mode' => 'table_club_national_action',
										'memberName' => (string)$club->name,
										'formId' => $formId,
									]) ?>
								</td>
								<?php if ($showPosColumn): ?>
									<td class="number pos text-end"><?= h(\App\Utility\LocaleNumberParser::format($club->pos, decimals: $numberDecimals['integer'])) ?></td>
								<?php endif; ?>
								<?php if ($showEnabledColumn): ?>
									<td class="boolean enabled">
										<?= $club->enabled
											? '<i class="fa fa-check text-success"></i>'
											: '<i class="fa fa-times text-danger"></i>' ?>
									</td>
								<?php endif; ?>
								<?php if ($showVisibleColumn): ?>
									<td class="boolean visible">
										<?= $club->visible
											? '<i class="fa fa-check text-success"></i>'
											: '<i class="fa fa-times text-danger"></i>' ?>
									</td>
								<?php endif; ?>
								<?php if ($showCountColumn): ?>
									<td class="number count text-end"><?= h(\App\Utility\LocaleNumberParser::formatCount($userCount, decimals: $numberDecimals['integer'])) ?></td>
								<?php endif; ?>
								<?php if ($showTimestampColumn): ?>
									<td class="datetime<?= $showCreatedColumn ? ' created' : '' ?><?= $showModifiedColumn ? ' modified' : '' ?>">
										<?php if ($showCreatedColumn): ?>
											<?= $club->created ? h(\App\Utility\LocaleDateParser::format($club->created, 'datetime_short')) : '' ?>
										<?php endif; ?>
										<?php if ($showCreatedColumn && $showModifiedColumn && $club->modified): ?>
											<br>
										<?php endif; ?>
										<?php if ($showModifiedColumn): ?>
											<?= $club->modified ? h(\App\Utility\LocaleDateParser::format($club->modified, 'datetime_short')) : '' ?>
										<?php endif; ?>
									</td>
								<?php endif; ?>
								<td class="actions">
									<?= $this->Html->link(
										'<i class="fa fa-eye"></i>',
										['action' => 'view', $club->id],
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
										['action' => 'edit', $club->id],
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
										<a role="button" href="#" class="btn btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDelete) ?>" data-id="<?= (int)$club->id ?>">
											<i class="fa fa-trash"></i>
										</a>
										<?= $this->Form->create(null, [
											'url' => ['action' => 'delete', $club->id],
											'id' => 'delete-form-' . $club->id,
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
						<?php if ($clubs->count() === 0): ?>
							<tr><td colspan="<?= (int)$indexColspan ?>" class="text-center text-muted py-4"><?= __('No records found.') ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<div class="card-footer">
				<?= $this->element('admin/index_footer') ?>
			</div>
		</div>
	</div>
</div>
<script>
window.PresidentClubs = {
	recordTitle: <?= json_encode(__('Record club annual membership fee?'), JSON_UNESCAPED_UNICODE) ?>,
	recordText: <?= json_encode(__('Do you confirm that this club has paid the national pipe association membership fee for this year? The payment date will be set to today.'), JSON_UNESCAPED_UNICODE) ?>,
	recordTextNamed: <?= json_encode(__('Do you confirm that {0} has paid the national pipe association membership fee for this year? The payment date will be set to today.'), JSON_UNESCAPED_UNICODE) ?>,
	recordConfirm: <?= json_encode(__('Yes, record payment'), JSON_UNESCAPED_UNICODE) ?>,
	recordCancel: <?= json_encode(__('Cancel'), JSON_UNESCAPED_UNICODE) ?>
};
</script>
<?= $this->element('admin/modal_record_view') ?>
<?= $this->element('admin/modal_linked_record_view') ?>
