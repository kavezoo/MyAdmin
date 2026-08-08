<?php
/**
 * Panel club profile (read-only) — Clubpresident / Member.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Club $club
 * @var \Cake\Datasource\EntityInterface|null $president
 * @var string $countryLabel
 * @var bool $isMyClub
 * @var int $myClubId
 */
use App\Auth\MembershipProfile;

$this->Html->css(['pages/index', 'pages/club_logo'], ['block' => true]);
$isMyClub = (bool)($isMyClub ?? false);
$countryLabel = (string)($countryLabel ?? '');
$president = $president ?? null;
$presidentName = $president ? MembershipProfile::displayName($president) : '';
if ($presidentName === '' && $president !== null) {
	$presidentName = (string)($president->get('email') ?? '');
}
?>
<div class="row">
	<div class="col-12 col-xxl-10 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left d-flex align-items-center gap-3">
					<?php
					$logoStored = $club->get('logo');
					$clubLogoUrl = \App\Utility\ClubLogo::publicUrlFor(
						(int)$club->id,
						is_string($logoStored) ? $logoStored : null
					);
					?>
					<?php if ($clubLogoUrl !== ''): ?>
						<img src="<?= h($clubLogoUrl) ?>" alt="" class="club-logo-preview club-logo-preview--view" width="64" height="64">
					<?php else: ?>
						<span class="club-logo-placeholder club-logo-placeholder--view d-inline-flex align-items-center justify-content-center text-secondary" aria-hidden="true">
							<i class="fa fa-shield fa-2x"></i>
						</span>
					<?php endif; ?>
					<div>
						<h3 class="mb-0">
							<i class="fa fa-sitemap"></i> <?= h((string)$club->name) ?>
							<?php if ($isMyClub): ?>
								<span class="badge text-bg-primary ms-1"><?= __('My club') ?></span>
							<?php endif; ?>
						</h3>
						<?php if ((string)$club->short_name !== ''): ?>
							<div class="text-muted"><?= h((string)$club->short_name) ?></div>
						<?php endif; ?>
					</div>
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
					<div class="record-view-row"><dt><?= __('Country') ?></dt><dd><?= h($countryLabel) ?></dd></div>
					<div class="record-view-row"><dt><?= __('City') ?></dt><dd><?= h((string)($club->city->name ?? '—')) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Address') ?></dt><dd><?= h((string)$club->address !== '' ? (string)$club->address : '—') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Email') ?></dt><dd><?= (string)$club->email !== '' ? $this->Html->link((string)$club->email, 'mailto:' . (string)$club->email) : '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Phone') ?></dt><dd><?= h((string)$club->phone !== '' ? (string)$club->phone : '—') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Web') ?></dt><dd>
						<?php if ((string)$club->web !== ''): ?>
							<?= $this->Html->link((string)$club->web, (string)$club->web, ['target' => '_blank', 'rel' => 'noopener']) ?>
						<?php else: ?>
							—
						<?php endif; ?>
					</dd></div>
					<div class="record-view-row"><dt><?= __('Facebook') ?></dt><dd>
						<?php if ((string)$club->facebook !== ''): ?>
							<?= $this->Html->link((string)$club->facebook, (string)$club->facebook, ['target' => '_blank', 'rel' => 'noopener']) ?>
						<?php else: ?>
							—
						<?php endif; ?>
					</dd></div>
					<div class="record-view-row"><dt><?= __('Instagram') ?></dt><dd>
						<?php if ((string)$club->insta !== ''): ?>
							<?= $this->Html->link((string)$club->insta, (string)$club->insta, ['target' => '_blank', 'rel' => 'noopener']) ?>
						<?php else: ?>
							—
						<?php endif; ?>
					</dd></div>
					<div class="record-view-row"><dt><?= __('Club president') ?></dt><dd><?= h($presidentName !== '' ? $presidentName : '—') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Members') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::formatCount((int)$club->user_count, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Competitions') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::formatCount((int)($club->competition_count ?? 0), decimals: 0)) ?></dd></div>
				</dl>
			</div>
			<div class="card-footer">
				<div class="record-view-footer-actions">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-list"></i></span>' . __('Back to list'),
						$this->get('indexListUrl') ?? ['action' => 'index'],
						['escape' => false, 'class' => 'btn btn-outline-secondary']
					) ?>
				</div>
			</div>
		</div>
	</div>
</div>
