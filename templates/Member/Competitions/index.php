<?php
/**
 * Member — open competitions list.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Competition> $competitions
 * @var array<string, \App\Model\Entity\CompetitionsUser> $myApplications
 * @var int $countryId
 * @var bool $clubFeePaid
 */
use App\Utility\CompetitionApplication;

$this->Html->css(['pages/index'], ['block' => true]);
$myApplications = $myApplications ?? [];
$countryId = (int)($countryId ?? 0);
$clubFeePaid = !empty($clubFeePaid);

$withdrawConfirm = [
	'title' => __('Withdraw application'),
	'text' => __('Withdraw your application? This permanently deletes your application record.'),
	'confirm' => __('Yes, withdraw'),
	'cancel' => __('Cancel'),
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode(['withdrawConfirm' => $withdrawConfirm], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);
$this->Html->scriptBlock(<<<'JS'
(function ($) {
	$(function () {
		if (typeof window.MyAdmin === 'undefined' || typeof window.MyAdmin.swal !== 'function') {
			return;
		}
		var cfg = (window.MyAdmin.config && window.MyAdmin.config.withdrawConfirm) || {};
		$(document).on('click', '.js-withdraw-application', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var $form = $btn.closest('form');
			if (!$form.length) {
				return;
			}
			window.MyAdmin.swal({
				icon: 'warning',
				title: cfg.title || 'Withdraw application',
				text: cfg.text || 'Withdraw your application?',
				showCancelButton: true,
				focusCancel: true,
				confirmButtonText: cfg.confirm || 'Yes, withdraw',
				cancelButtonText: cfg.cancel || 'Cancel',
				confirmButtonColor: '#dc3545',
				cancelButtonColor: '#6c757d',
				reverseButtons: true
			}).then(function (result) {
				if (result.isConfirmed) {
					$form.trigger('submit');
				}
			});
		});
	});
})(jQuery);
JS
, ['block' => 'scriptBottom']);
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-trophy"></i> <?= __('Competitions') ?></h3>
					<?= __('Open and upcoming competitions in your country') ?>
				</div>
				<div class="float-right">
					<?= $this->Html->link(__('Archive'), ['action' => 'archive'], ['class' => 'btn btn-outline-secondary']) ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-2">
				<?php if (!$clubFeePaid): ?>
					<div class="alert alert-warning m-3 mb-2">
						<?= h(__('You can only apply to competitions after your club membership fee is paid for this year.')) ?>
					</div>
				<?php endif; ?>
				<?php if ($countryId < 1): ?>
					<p class="text-muted mb-0 p-3"><?= __('Your profile has no country set, so competitions cannot be listed.') ?></p>
				<?php elseif ($competitions->count() === 0): ?>
					<p class="text-muted mb-0 p-3"><?= __('No competitions available right now.') ?></p>
				<?php else: ?>
					<div class="row g-3 p-2">
						<?php foreach ($competitions as $c): ?>
							<?php
							$app = $myApplications[(string)$c->id] ?? null;
							$hasApplication = CompetitionApplication::hasApplication($app);
							$open = CompetitionApplication::isApplicationOpen($c->first_date_of_application, $c->application_deadline);
							$past = CompetitionApplication::isPastDeadline($c->application_deadline);
							$registered = $hasApplication && CompetitionApplication::isRegistered((string)$app->status, $app->competition_club_id);
							$mayApply = $open && !$past && $clubFeePaid && !$hasApplication;
							?>
							<div class="col-12 col-md-6 col-xl-4">
								<div class="card h-100 border">
									<div class="card-body">
										<h5 class="card-title"><?= h((string)$c->name) ?></h5>
										<p class="card-text text-muted small mb-2"><?= h((string)$c->title) ?></p>
										<p class="small mb-1">
											<?= __('Apply:') ?>
											<?= $c->first_date_of_application ? h(\App\Utility\LocaleDateParser::format($c->first_date_of_application, 'date')) : '—' ?>
											–
											<?= $c->application_deadline ? h(\App\Utility\LocaleDateParser::format($c->application_deadline, 'date')) : '—' ?>
										</p>
										<?php if ($registered): ?>
											<span class="badge text-bg-success"><?= __('Registered') ?></span>
											<?php if (!empty($app->competitions_club->subclub->name)): ?>
												<span class="small text-muted ms-1"><?= h((string)$app->competitions_club->subclub->name) ?></span>
											<?php endif; ?>
										<?php elseif ($hasApplication): ?>
											<span class="badge text-bg-warning"><?= __('Awaiting team assignment') ?></span>
										<?php elseif ($past): ?>
											<span class="badge text-bg-secondary"><?= __('Applications closed') ?></span>
										<?php elseif ($open && !$clubFeePaid): ?>
											<span class="badge text-bg-warning"><?= __('Club fee unpaid') ?></span>
										<?php elseif ($open): ?>
											<span class="badge text-bg-primary"><?= __('Open for applications') ?></span>
										<?php else: ?>
											<span class="badge text-bg-light text-dark"><?= __('Not open yet') ?></span>
										<?php endif; ?>
									</div>
									<div class="card-footer bg-transparent d-flex flex-wrap gap-2">
										<?php if ($hasApplication): ?>
											<?= $this->Html->link(
												__('View'),
												['action' => 'view', $c->id],
												['class' => 'btn btn-sm btn-outline-primary']
											) ?>
											<?php if (!$past): ?>
												<?= $this->Form->create(null, [
													'url' => ['action' => 'withdraw', $c->id],
													'class' => 'd-inline',
												]) ?>
													<button type="button" class="btn btn-sm btn-outline-danger js-withdraw-application">
														<span class="btn-label"><i class="fa fa-trash"></i></span><?= __('Withdraw application') ?>
													</button>
												<?= $this->Form->end() ?>
											<?php endif; ?>
										<?php elseif ($mayApply): ?>
											<?= $this->Html->link(
												__('Apply / details'),
												['action' => 'view', $c->id],
												['class' => 'btn btn-sm btn-primary']
											) ?>
										<?php else: ?>
											<?= $this->Html->link(
												__('View'),
												['action' => 'view', $c->id],
												['class' => 'btn btn-sm ' . ($past ? 'btn-secondary' : 'btn-outline-secondary')]
											) ?>
											<?php if ($past || ($open && !$clubFeePaid)): ?>
												<button type="button" class="btn btn-sm btn-secondary disabled" disabled><?= __('Apply') ?></button>
											<?php endif; ?>
										<?php endif; ?>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
