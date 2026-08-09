<?php
/**
 * Assign check-in / judge staff (separate Competition staff screens).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Competition $competition
 * @var list<\App\Model\Entity\CompetitionStaff> $competitionStaff
 * @var array<string, string> $staffRoles
 * @var array<string, mixed> $staffAddUrl
 * @var string|null $staffUserAjaxUrl
 * @var int|null $staffSearchCountryId
 * @var array<string, mixed>|string|false|null $staffBackUrl Back link in card-footer (false = hide)
 */
$competitionStaff = $competitionStaff ?? [];
$staffRoles = $staffRoles ?? [];
$staffAddUrl = $staffAddUrl ?? ['action' => 'staffAdd', $competition->id];
$staffUserAjaxUrl = $staffUserAjaxUrl ?? $this->Url->build(['action' => 'userOptions']);
$staffSearchCountryId = (int)($staffSearchCountryId ?? $competition->country_id ?? 0);
$staffBackUrl = $staffBackUrl ?? ['action' => 'index'];
?>
<div class="card mb-3 border border-2 shadow">
	<div class="card-header">
		<h4 class="mb-0"><i class="fa fa-id-badge"></i> <?= __('Competition staff') ?></h4>
		<div class="text-muted small"><?= __('Assign check-in desk and table judges. Access is valid only on the competition calendar day (full day, country timezone). Only assigned people may use check-in / judge for that competition.') ?></div>
	</div>
	<div class="card-body">
		<?= $this->Form->create(null, [
			'url' => $staffAddUrl,
			'class' => 'row g-2 align-items-end mb-3',
		]) ?>
			<div class="col-md-5">
				<label class="form-label" for="staff-user-id"><?= __('User') ?></label>
				<?= $this->Form->control('user_id', [
					'label' => false,
					'type' => 'select',
					'options' => [],
					'empty' => true,
					'class' => 'form-select js-staff-user-ajax',
					'id' => 'staff-user-id',
					'data-ajax-url' => $staffUserAjaxUrl,
					'data-country-id' => $staffSearchCountryId,
					'data-placeholder' => __('Type at least 2 characters to search by name…'),
				]) ?>
			</div>
			<div class="col-md-3">
				<label class="form-label" for="staff-role"><?= __('Role') ?></label>
				<?= $this->Form->control('staff_role', [
					'label' => false,
					'type' => 'select',
					'options' => $staffRoles,
					'empty' => __('Select…'),
					'class' => 'form-select',
					'id' => 'staff-role',
				]) ?>
			</div>
			<div class="col-md-2">
				<button type="submit" class="btn btn-primary"><?= __('Assign') ?></button>
			</div>
		<?= $this->Form->end() ?>

		<table class="table table-bordered table-sm mb-0 index-data-table">
			<thead>
				<tr>
					<th class="string"><?= __('User') ?></th>
					<th class="string col-label"><?= __('Staff role') ?></th>
					<th class="actions"><?= __('Actions') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($competitionStaff === []): ?>
					<tr><td colspan="3" class="text-muted"><?= __('No staff assigned yet.') ?></td></tr>
				<?php endif; ?>
				<?php foreach ($competitionStaff as $row):
					$user = $row->user;
					$label = $user
						? trim((string)$user->last_name . ' ' . (string)$user->first_name)
						: (string)$row->user_id;
					if ($label === '' && $user) {
						$label = (string)$user->email;
					}
					$staffRowId = (string)$row->id;
					$tooltipRemove = '<b>' . __('Remove') . '</b><br>' . __('Remove this staff assignment?');
					?>
					<tr data-id="<?= h($staffRowId) ?>" data-can-delete="1">
						<td class="string"><?= h($label) ?></td>
						<td class="string col-label"><?= h(\App\Utility\CompetitionStaff::roleLabel((string)$row->staff_role)) ?></td>
						<td class="actions">
							<button type="button"
								class="btn btn-sm btn-outline-danger btn-row-delete"
								data-id="<?= h($staffRowId) ?>"
								data-delete-form="#delete-form-<?= h($staffRowId) ?>"
								data-bs-toggle="tooltip"
								data-bs-html="true"
								title="<?= h($tooltipRemove) ?>"
								data-swal-title="<?= h(__('Remove')) ?>"
								data-swal-text="<?= h(__('Remove this staff assignment?')) ?>"
								data-swal-confirm="<?= h(__('Remove')) ?>">
								<?= h(__('Remove')) ?>
							</button>
							<?= $this->Form->create(null, [
								'url' => ['action' => 'staffDelete', $staffRowId],
								'id' => 'delete-form-' . $staffRowId,
								'class' => 'd-none js-row-delete-form',
							]) ?>
							<?= $this->Form->end() ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php if ($staffBackUrl !== false && $staffBackUrl !== null && $staffBackUrl !== ''): ?>
		<div class="card-footer">
			<div class="record-view-footer-actions">
				<?= $this->Html->link(
					'<span class="btn-label"><i class="fa fa-arrow-left"></i></span>' . __('Back'),
					$staffBackUrl,
					['escape' => false, 'class' => 'btn btn-outline-secondary']
				) ?>
			</div>
		</div>
	<?php endif; ?>
</div>
