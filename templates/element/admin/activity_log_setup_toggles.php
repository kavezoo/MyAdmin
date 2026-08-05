<?php
/**
 * Per-country activity log setup toggles (Admin working country).
 *
 * @var \App\View\AppView $this
 */
$workingCountryLabel = (string)($workingCountryLabel ?? '');
$activityLoggingEnabled = (bool)($activityLoggingEnabled ?? false);
$usersActivityLogVisible = (bool)($usersActivityLogVisible ?? false);
$canToggleActivityLogging = (bool)($canToggleActivityLogging ?? false);
$canToggleUsersActivityView = (bool)($canToggleUsersActivityView ?? false);
$redirectTarget = (string)($redirectTarget ?? $this->request->getRequestTarget());

if ($workingCountryLabel === '' || (!$canToggleActivityLogging && !$canToggleUsersActivityView)) {
	return;
}
?>
<div class="card mb-3 shadow-sm border border-2 border-info">
	<div class="card-header bg-info-subtle py-2">
		<h5 class="mb-0 fw-bold">
			<i class="fa fa-sliders"></i>
			<?= h(__('Settings for {0}', $workingCountryLabel)) ?>
		</h5>
		<div class="text-muted small"><?= __('Your working country — toggles apply only to this country.') ?></div>
	</div>
	<div class="card-body py-3">
		<div class="row g-3 align-items-center">
			<?php if ($canToggleActivityLogging): ?>
				<div class="col-12 col-lg-6">
					<div class="d-flex flex-wrap align-items-center gap-2">
						<span class="fw-semibold"><?= __('Activity logging') ?>:</span>
						<?php if ($activityLoggingEnabled): ?>
							<span class="badge text-bg-success"><?= __('On') ?></span>
						<?php else: ?>
							<span class="badge text-bg-secondary"><?= __('Off') ?></span>
						<?php endif; ?>
						<?= $this->Form->create(null, [
							'url' => ['action' => 'toggleActivityLogging'],
							'class' => 'd-inline',
						]) ?>
							<input type="hidden" name="_redirect" value="<?= h($redirectTarget) ?>">
							<button type="submit" class="btn btn-sm <?= $activityLoggingEnabled ? 'btn-outline-warning' : 'btn-outline-success' ?>">
								<i class="fa fa-<?= $activityLoggingEnabled ? 'pause' : 'play' ?>"></i>
								<?= $activityLoggingEnabled ? __('Turn off logging') : __('Turn on logging') ?>
							</button>
						<?= $this->Form->end() ?>
					</div>
					<div class="text-muted small mt-1"><?= __('When off, new login/logout and data changes are not recorded.') ?></div>
				</div>
			<?php endif; ?>

			<?php if ($canToggleUsersActivityView): ?>
				<div class="col-12 col-lg-6">
					<div class="d-flex flex-wrap align-items-center gap-2">
						<span class="fw-semibold"><?= __('Users see own activity') ?>:</span>
						<?php if ($usersActivityLogVisible): ?>
							<span class="badge text-bg-success"><?= __('On') ?></span>
						<?php else: ?>
							<span class="badge text-bg-secondary"><?= __('Off') ?></span>
						<?php endif; ?>
						<?= $this->Form->create(null, [
							'url' => ['action' => 'toggleUsersActivityView'],
							'class' => 'd-inline',
						]) ?>
							<input type="hidden" name="_redirect" value="<?= h($redirectTarget) ?>">
							<button type="submit" class="btn btn-sm <?= $usersActivityLogVisible ? 'btn-outline-warning' : 'btn-outline-success' ?>">
								<i class="fa fa-<?= $usersActivityLogVisible ? 'eye-slash' : 'eye' ?>"></i>
								<?= $usersActivityLogVisible ? __('Hide from users') : __('Show to users') ?>
							</button>
						<?= $this->Form->end() ?>
					</div>
					<div class="text-muted small mt-1"><?= __('Controls the “My activity” menu item for users in this country.') ?></div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
