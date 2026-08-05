<?php
/**
 * Club president — membership applicants.
 *
 * @var \App\View\AppView $this
 * @var iterable<\CakeDC\Users\Model\Entity\User> $applicants
 * @var string $clubName
 * @var int $clubId
 */
use App\Auth\MembershipProfile;
use App\Utility\AdminCountry;

$this->Html->css(['pages/index'], ['block' => true]);
$this->assign('title', __('Applicants'));

$clubName = (string)($clubName ?? '');
?>
<div class="row">
	<div class="col-12 p-2">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-user-plus"></i> <?= __('Applicants') ?></h3>
					<?php if ($clubName !== ''): ?>
						<div class="text-muted"><?= h(__('Pending membership applications for {0}', $clubName)) ?></div>
					<?php endif; ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<?= $this->element('admin/index_pagination') ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-2">
				<div class="table-responsive">
					<table class="table table-bordered table-hover table-striped mb-0 align-middle">
						<thead>
							<tr>
								<th><?= __('Name') ?></th>
								<th><?= __('Email') ?></th>
								<th><?= __('Phone') ?></th>
								<th><?= __('Country') ?></th>
								<th><?= __('Submitted') ?></th>
								<th class="text-end"><?= __('Actions') ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$empty = true;
							foreach ($applicants as $applicant):
								$empty = false;
								$name = MembershipProfile::displayName($applicant);
								if ($name === '') {
									$name = (string)($applicant->email ?? '');
								}
								$countryLabel = AdminCountry::label((int)($applicant->country_id ?? 0));
								?>
								<tr>
									<td><?= h($name) ?></td>
									<td><?= h((string)$applicant->email) ?></td>
									<td><?= h((string)($applicant->phone ?? '')) ?></td>
									<td><?= h($countryLabel) ?></td>
									<td><?= $applicant->modified ? h(\App\Utility\LocaleDateParser::format($applicant->modified, 'datetime_short')) : '' ?></td>
									<td class="text-end">
										<?= $this->Form->postLink(
											'<span class="btn-label"><i class="fa fa-check"></i></span>' . h(__('Approve')),
											['action' => 'approve', $applicant->id],
											[
												'escape' => false,
												'class' => 'btn btn-sm btn-success',
												'confirm' => __('Approve this applicant as a full member?'),
											]
										) ?>
									</td>
								</tr>
							<?php endforeach; ?>
							<?php if ($empty): ?>
								<tr>
									<td colspan="6" class="text-center text-muted py-4">
										<?= __('No pending applicants.') ?>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
