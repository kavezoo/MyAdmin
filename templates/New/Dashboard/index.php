<?php
/**
 * New panel — Dashboard.
 *
 * @var \App\View\AppView $this
 * @var bool $pending
 */
$pending = (bool)($pending ?? false);
$this->assign('title', __('Dashboard'));
?>
<div class="row">
	<div class="col-12 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<h3 class="fw-bold"><i class="fa fa-tachometer"></i> <?= __('Dashboard') ?></h3>
			</div>
			<div class="card-body">
				<?php if ($pending): ?>
					<div class="alert alert-info mb-0">
						<h5 class="alert-heading"><?= __('Application submitted') ?></h5>
						<p class="mb-0">
							<?= __('Your profile is complete. The club president has been notified and will review your membership application. You will receive an email when you are approved.') ?>
						</p>
					</div>
				<?php else: ?>
					<p class="mb-0"><?= __('Welcome. Please complete your profile to apply for membership.') ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
