<?php
/**
 * Member panel — Dashboard (profile + competitions).
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Competition> $competitions
 * @var array<string, \App\Model\Entity\CompetitionsUser> $myApplications
 * @var int $countryId
 * @var bool $clubFeePaid
 */
use App\Utility\CompetitionApplication;
use App\Utility\PanelNav;

$this->assign('title', __('Dashboard'));
$this->Html->css(['pages/index'], ['block' => true]);

$competitions = $competitions ?? [];
$myApplications = $myApplications ?? [];
$countryId = (int)($countryId ?? 0);
$clubFeePaid = !empty($clubFeePaid);

$cards = PanelNav::forPrefix('Member', $this->getRequest());
?>
<div class="row">
	<div class="col-12 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<h3 class="fw-bold"><i class="fa fa-tachometer"></i> <?= __('Dashboard') ?></h3>
			</div>
			<div class="card-body">
				<?= $this->element('panel/club_fee_unpaid_alert') ?>
				<p class="mb-3"><?= __('Welcome to the Member panel. Choose where you want to go — each card explains the destination.') ?></p>
				<?= $this->element('panel/dashboard_nav_cards', ['cards' => $cards]) ?>
			</div>
		</div>

		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-trophy"></i> <?= __('Competitions') ?></h3>
					<?= __('Open and upcoming competitions in your country') ?>
				</div>
				<div class="float-right">
					<?= $this->Html->link(__('All competitions'), ['prefix' => 'Member', 'controller' => 'Competitions', 'action' => 'index'], ['class' => 'btn btn-outline-primary']) ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-2">
				<?php if ($countryId < 1): ?>
					<p class="text-muted mb-0 p-3"><?= __('Your profile has no country set, so competitions cannot be listed.') ?></p>
				<?php elseif (!is_object($competitions) || $competitions->count() === 0): ?>
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
										<?php if ($registered): ?>
											<span class="badge text-bg-success"><?= __('Registered') ?></span>
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
									<div class="card-footer bg-transparent">
										<?= $this->Html->link(
											$mayApply ? __('Apply / details') : __('View'),
											['prefix' => 'Member', 'controller' => 'Competitions', 'action' => 'view', $c->id],
											['class' => 'btn btn-sm ' . ($mayApply ? 'btn-primary' : 'btn-outline-primary')]
										) ?>
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
