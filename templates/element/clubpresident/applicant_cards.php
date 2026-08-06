<?php
/**
 * Pending membership applicants — outer panel card + nested applicant cards (ValiAdmin card sample).
 *
 * @var \App\View\AppView $this
 * @var iterable<\CakeDC\Users\Model\Entity\User> $applicants
 */
use App\Auth\MembershipProfile;
use App\Utility\AdminCountry;
use App\Utility\UserAvatar;

$applicants = $applicants ?? [];
$list = is_array($applicants) ? $applicants : iterator_to_array($applicants);
if ($list === []) {
	return;
}
$count = count($list);
?>
<div class="row applicants-cards-panel">
	<div class="col-12">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<h3>
					<i class="fa fa-user-plus"></i>
					<?= __('New applicants') ?>
					<span class="badge text-bg-primary"><?= (int)$count ?></span>
				</h3>
				<?= __('Pending membership applications — review and approve or reject below.') ?>
			</div>
			<div class="card-body">
				<div class="d-flex flex-wrap gap-3 applicants-cards">
					<?php foreach ($list as $applicant):
						$name = MembershipProfile::displayName($applicant);
						if ($name === '') {
							$name = (string)($applicant->email ?? '');
						}
						$applicantId = (string)$applicant->id;
						$countryLabel = AdminCountry::label((int)($applicant->country_id ?? 0));
						$phone = trim((string)($applicant->phone ?? ''));
						$clubName = '';
						if (!empty($applicant->club) && is_object($applicant->club)) {
							$clubName = trim((string)($applicant->club->name ?? ''));
						}
						$avatarPath = UserAvatar::displayPath($applicantId, (string)($applicant->avatar ?? ''));
						$avatarUrl = $avatarPath !== ''
							? $this->Url->build(UserAvatar::publicUrlFor($applicantId, (string)($applicant->avatar ?? '')))
							: '';
						$approveFormId = 'applicant-approve-form-' . $applicantId;
						$rejectFormId = 'applicant-reject-form-' . $applicantId;
						$submitted = $applicant->modified
							? \App\Utility\LocaleDateParser::format($applicant->modified, 'datetime_short')
							: '';
						?>
						<div class="card applicant-card border shadow">
							<?php if ($avatarUrl !== ''): ?>
								<img class="card-img-top applicant-card__img" src="<?= h($avatarUrl) ?>" alt="<?= h($name) ?>">
							<?php else: ?>
								<div class="card-img-top applicant-card__img applicant-card__img--placeholder d-flex align-items-center justify-content-center text-secondary bg-light">
									<i class="fa fa-user" aria-hidden="true"></i>
								</div>
							<?php endif; ?>
							<div class="card-body d-flex flex-column">
								<h4 class="card-title fw-bold mb-1"><?= h($name) ?></h4>
								<div class="text-muted small mb-2"><?= h(\App\Auth\AppRoles::label(\App\Auth\AppRoles::NEW)) ?></div>
								<p class="card-text small mb-3 flex-grow-1">
									<span class="d-block"><i class="fa fa-envelope fa-fw text-muted"></i> <?= h((string)$applicant->email) ?></span>
									<?php if ($phone !== ''): ?>
										<span class="d-block"><i class="fa fa-phone fa-fw text-muted"></i> <?= h($phone) ?></span>
									<?php endif; ?>
									<?php if ($clubName !== ''): ?>
										<span class="d-block"><i class="fa fa-users fa-fw text-muted"></i> <?= h($clubName) ?></span>
									<?php endif; ?>
									<?php if ($countryLabel !== ''): ?>
										<span class="d-block"><i class="fa fa-flag fa-fw text-muted"></i> <?= h($countryLabel) ?></span>
									<?php endif; ?>
									<?php if ($submitted !== ''): ?>
										<span class="d-block text-muted mt-1"><i class="fa fa-clock-o fa-fw"></i> <?= h($submitted) ?></span>
									<?php endif; ?>
								</p>
								<div class="d-flex flex-wrap gap-2 mt-auto">
									<?= $this->Form->create(null, [
										'url' => ['action' => 'approve', $applicantId],
										'id' => $approveFormId,
										'class' => 'd-none applicant-action-form',
									]) ?>
									<?= $this->Form->end() ?>
									<button
										type="button"
										class="btn btn-success js-applicant-approve"
										data-form-id="<?= h($approveFormId) ?>"
									>
										<span class="btn-label"><i class="fa fa-check"></i></span><?= h(__('Approve')) ?>
									</button>

									<?= $this->Form->create(null, [
										'url' => ['action' => 'reject', $applicantId],
										'id' => $rejectFormId,
										'class' => 'd-none applicant-action-form',
									]) ?>
									<?= $this->Form->end() ?>
									<button
										type="button"
										class="btn btn-outline-danger js-applicant-reject"
										data-form-id="<?= h($rejectFormId) ?>"
									>
										<span class="btn-label"><i class="fa fa-times"></i></span><?= h(__('Reject')) ?>
									</button>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
</div>
