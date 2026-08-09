<?php
/**
 * Member — competition details + apply / edit / view application.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Competition $competition
 * @var \App\Model\Entity\CompetitionsUser|null $application
 * @var bool $canApply
 * @var bool $pastDeadline
 * @var bool $ended
 * @var bool $canEditApplication
 * @var bool $canWithdraw
 * @var bool $clubFeePaid
 */
use App\Utility\CompetitionApplication;

$this->Html->css(['pages/index', 'pages/form', 'pages/competition_view'], ['block' => true]);
$this->Html->script(['/plugins/inputmask/jquery.inputmask.min', 'pages/form'], ['block' => 'scriptBottom']);
$canEditApplication = (bool)($canEditApplication ?? false);
$canWithdraw = (bool)($canWithdraw ?? false);
$clubFeePaid = !empty($clubFeePaid);
$hasApplication = CompetitionApplication::hasApplication($application ?? null);
// Withdraw only when a live application row exists (never without / after delete).
$canWithdraw = $canWithdraw && $hasApplication;
$canEditApplication = $canEditApplication && $hasApplication;
if (!$hasApplication) {
	$application = null;
}
$config = [
	'numberFormat' => \App\Utility\LocaleNumberParser::jsConfig(),
	'applyConfirm' => [
		'title' => __('Confirm application'),
		'text' => __('Do you really want to apply to this competition?'),
		'confirm' => __('Yes, apply'),
		'cancel' => __('Cancel'),
	],
	'saveConfirm' => [
		'title' => __('Save changes'),
		'text' => __('Do you want to save the changes to your application details?'),
		'confirm' => __('Yes, save'),
		'cancel' => __('Cancel'),
	],
	'withdrawConfirm' => [
		'title' => __('Withdraw application'),
		'text' => __('Withdraw your application? This permanently deletes your application record.'),
		'confirm' => __('Yes, withdraw'),
		'cancel' => __('Cancel'),
	],
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);
$this->Html->scriptBlock(<<<'JS'
(function ($) {
	$(function () {
		if (typeof window.MyAdmin === 'undefined' || typeof window.MyAdmin.swal !== 'function') {
			return;
		}
		var applyCfg = (window.MyAdmin.config && window.MyAdmin.config.applyConfirm) || null;
		var saveCfg = (window.MyAdmin.config && window.MyAdmin.config.saveConfirm) || null;
		var withdrawCfg = (window.MyAdmin.config && window.MyAdmin.config.withdrawConfirm) || {};

		function bindConfirmSubmit($form, cfg, defaults) {
			if (!$form.length || !cfg) {
				return;
			}
			var confirmed = false;
			$form.on('submit', function (e) {
				if (confirmed) {
					return;
				}
				e.preventDefault();
				window.MyAdmin.swal({
					icon: 'question',
					title: cfg.title || defaults.title,
					text: cfg.text || defaults.text,
					showCancelButton: true,
					focusCancel: true,
					confirmButtonText: cfg.confirm || defaults.confirm,
					cancelButtonText: cfg.cancel || defaults.cancel,
					confirmButtonColor: '#198754',
					cancelButtonColor: '#6c757d',
					reverseButtons: true
				}).then(function (result) {
					if (!result.isConfirmed) {
						return;
					}
					confirmed = true;
					if (typeof window.MyAdmin.allowFormLeave === 'function') {
						window.MyAdmin.allowFormLeave();
					}
					$form.trigger('submit');
				});
			});
		}

		bindConfirmSubmit($('#form-horizontal.js-competition-apply'), applyCfg, {
			title: 'Confirm application',
			text: 'Do you really want to apply to this competition?',
			confirm: 'Yes, apply',
			cancel: 'Cancel'
		});
		bindConfirmSubmit($('#form-horizontal.js-competition-update'), saveCfg, {
			title: 'Save changes',
			text: 'Do you want to save the changes to your application details?',
			confirm: 'Yes, save',
			cancel: 'Cancel'
		});

		// Same as competitions list: delete application row → redirect to list (controller).
		$(document).on('click', '.js-withdraw-application', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var $btn = $(this);
			var formId = $btn.attr('form');
			var $wForm = formId ? $('#' + formId) : $btn.closest('form');
			if (!$wForm.length || $wForm.is('#form-horizontal')) {
				return;
			}
			window.MyAdmin.swal({
				icon: 'warning',
				title: withdrawCfg.title || 'Withdraw application',
				text: withdrawCfg.text || 'Withdraw your application?',
				showCancelButton: true,
				focusCancel: true,
				confirmButtonText: withdrawCfg.confirm || 'Yes, withdraw',
				cancelButtonText: withdrawCfg.cancel || 'Cancel',
				confirmButtonColor: '#dc3545',
				cancelButtonColor: '#6c757d',
				reverseButtons: true
			}).then(function (result) {
				if (!result.isConfirmed) {
					return;
				}
				if (typeof window.MyAdmin.allowFormLeave === 'function') {
					window.MyAdmin.allowFormLeave();
				}
				$wForm.trigger('submit');
			});
		});
	});
})(jQuery);
JS
, ['block' => 'scriptBottom']);
?>
<div class="row">
	<div class="col-12 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-trophy"></i> <?= h((string)$competition->name) ?></h3>
					<?= h((string)$competition->title) ?>
					<?php if (trim((string)($competition->subtitle ?? '')) !== ''): ?>
						<div class="text-muted small"><?= h((string)$competition->subtitle) ?></div>
					<?php endif; ?>
					<?= $this->element('competitions/staff_under_title', [
						'competitionStaffGroups' => $competitionStaffGroups ?? null,
						'competitionId' => (string)$competition->id,
					]) ?>
				</div>
				<div class="float-right">
					<a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary"><i class="fa fa-times"></i></a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?php if ($pastDeadline && !$application): ?>
					<div class="alert alert-secondary"><?= __('The application deadline has passed. You can only view this competition.') ?></div>
				<?php endif; ?>
				<?php if ($application && $pastDeadline): ?>
					<div class="alert alert-secondary"><?= __('The application deadline has passed. Your details are read-only — ask your club president if something must change.') ?></div>
				<?php endif; ?>
				<?php if ($ended): ?>
					<div class="alert alert-secondary"><?= __('This competition has ended.') ?></div>
				<?php endif; ?>

				<?php if ($canWithdraw): ?>
					<?= $this->Form->create(null, [
						'url' => ['action' => 'withdraw', $competition->id],
						'id' => 'form-withdraw-application',
						'class' => 'd-none',
					]) ?>
					<?= $this->Form->end() ?>
				<?php endif; ?>

				<div class="competition-view-layout">
					<?php /* Left (desktop) / top (mobile): meta */ ?>
					<div class="c-meta">
						<dl class="row record-view-fields mb-0">
							<div class="record-view-row"><dt><?= __('Club') ?></dt><dd><?= h((string)($competition->club->name ?? '')) ?></dd></div>
							<div class="record-view-row"><dt><?= __('Application from') ?></dt><dd><?= $competition->first_date_of_application ? h(\App\Utility\LocaleDateParser::format($competition->first_date_of_application, 'date')) : '—' ?></dd></div>
							<div class="record-view-row"><dt><?= __('Application deadline') ?></dt><dd><?= $competition->application_deadline ? h(\App\Utility\LocaleDateParser::format($competition->application_deadline, 'date')) : '—' ?></dd></div>
							<div class="record-view-row"><dt><?= __('Competition datetime') ?></dt><dd><?= $competition->competition_datetime ? h(\App\Utility\LocaleDateParser::format($competition->competition_datetime, 'datetime_short')) : '—' ?></dd></div>
							<div class="record-view-row"><dt><?= __('Min. team size') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($competition->minimum_team_size, decimals: 0)) ?></dd></div>
						</dl>

						<?php if ($application): ?>
							<?php
							$registered = CompetitionApplication::isRegistered(
								(string)$application->status,
								$application->competition_club_id
							);
							?>
							<div class="alert <?= $registered ? 'alert-success' : 'alert-warning' ?> mt-3 mb-0">
								<?= __('Your application:') ?>
								<strong><?= h(CompetitionApplication::statusLabel((string)$application->status)) ?></strong>
								<?php if (!empty($application->competitions_club)): ?>
									— <?= h((string)($application->competitions_club->subclub->name ?? $application->competitions_club->name ?? '')) ?>
								<?php endif; ?>
								<?php if (!$registered): ?>
									<div class="small mt-1"><?= __('You are not registered yet — the club president must assign you to a sub-team.') ?></div>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>

					<?php /* Right (desktop) / middle (mobile): description — may be taller than left */ ?>
					<div class="c-desc">
						<h5 class="mb-2"><?= __('Description') ?></h5>
						<div class="competition-description">
							<?= $this->element('competitions/description_rendered', [
								'competition' => $competition,
							]) ?>
						</div>
					</div>

					<?php /* Under meta on desktop / bottom on mobile: application inputs */ ?>
					<div class="c-form">
						<?php if ($application): ?>
							<h5 class="mt-0"><?= __('Application details') ?></h5>
							<?php if ($canEditApplication): ?>
								<?= $this->Form->create($application, [
									'url' => ['action' => 'updateApplication', $competition->id],
									'id' => 'form-horizontal',
									'class' => 'js-competition-update',
								]) ?>
									<?= $this->element('competitions/application_fields', [
										'competition' => $competition,
										'application' => $application,
										'readonly' => false,
										'feeUser' => $feeUser ?? null,
									]) ?>
									<div class="form-footer-actions d-flex flex-wrap gap-2 align-items-center">
										<button type="submit" class="btn btn-success">
											<span class="btn-label"><i class="fa fa-check"></i></span><?= __('Save changes') ?>
										</button>
										<?php if ($canWithdraw): ?>
											<button type="button"
												class="btn btn-outline-danger js-withdraw-application"
												form="form-withdraw-application">
												<span class="btn-label"><i class="fa fa-trash"></i></span><?= __('Withdraw application') ?>
											</button>
										<?php endif; ?>
										<?= $this->Html->link(__('Back to list'), ['action' => 'index'], ['class' => 'btn btn-outline-secondary']) ?>
									</div>
								<?= $this->Form->end() ?>
							<?php else: ?>
								<?= $this->element('competitions/application_fields', [
									'competition' => $competition,
									'application' => $application,
									'readonly' => true,
								]) ?>
								<div class="mt-3 form-footer-actions d-flex flex-wrap gap-2 align-items-center">
									<?php if ($canWithdraw): ?>
										<button type="button"
											class="btn btn-outline-danger js-withdraw-application"
											form="form-withdraw-application">
											<span class="btn-label"><i class="fa fa-trash"></i></span><?= __('Withdraw application') ?>
										</button>
									<?php endif; ?>
									<?= $this->Html->link(__('Back to list'), ['action' => 'index'], ['class' => 'btn btn-outline-secondary']) ?>
								</div>
							<?php endif; ?>

						<?php elseif ($canApply): ?>
							<h5><?= __('Apply to this competition') ?></h5>
							<?= $this->Form->create(null, [
								'url' => ['action' => 'apply', $competition->id],
								'id' => 'form-horizontal',
								'class' => 'js-competition-apply',
							]) ?>
								<?= $this->element('competitions/application_fields', [
									'competition' => $competition,
									'application' => null,
									'readonly' => false,
									'feeUser' => $feeUser ?? null,
								]) ?>
								<button type="submit" class="btn btn-success"><span class="btn-label"><i class="fa fa-check"></i></span><?= __('Submit application') ?></button>
							<?= $this->Form->end() ?>
						<?php elseif ($pastDeadline): ?>
							<button type="button" class="btn btn-secondary disabled" disabled><?= __('Applications closed') ?></button>
						<?php elseif (!$clubFeePaid): ?>
							<div class="alert alert-warning mb-0">
								<?= h(__('You can only apply to competitions after your club membership fee is paid for this year.')) ?>
							</div>
							<button type="button" class="btn btn-secondary disabled mt-3" disabled><?= __('Apply') ?></button>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
