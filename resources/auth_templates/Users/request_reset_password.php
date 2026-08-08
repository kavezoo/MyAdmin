<?php
/**
 * App Users — Request reset password.
 *
 * @var \App\View\AppView $this
 * @var \CakeDC\Users\Model\Entity\User $user
 */
use CakeDC\Users\Utility\UsersUrl;

$this->assign('title', __('Forgot password?'));
?>
<div class="local-login-form users-auth-form">
	<?= $this->Form->create($user, [
		'url' => UsersUrl::actionUrl('requestResetPassword'),
		'id' => 'users-reset-form',
	]) ?>
	<h3 class="login-head"><i class="bi bi-key me-2"></i><?= __('Forgot password?') ?></h3>

	<p class="mb-3"><?= __('Enter your email or username and we will send you a link to reset your password.') ?></p>

	<div class="mb-3">
		<label class="form-label" for="reference"><?= __('Email or username') ?></label>
		<?= $this->Form->control('reference', [
			'label' => false,
			'type' => 'email',
			'class' => 'form-control',
			'id' => 'reference',
			'required' => true,
			'autofocus' => true,
			'placeholder' => __('Email or username'),
			'autocomplete' => 'username',
			'inputmode' => 'email',
		]) ?>
	</div>

	<div class="mb-3 btn-container d-grid">
		<button type="submit" class="btn btn-primary btn-block">
			<i class="bi bi-envelope me-2 fs-5"></i><?= __('Send reset link') ?>
		</button>
	</div>

	<div class="users-auth-links">
		<?= $this->Html->link(__('Back to login'), UsersUrl::actionUrl('login')) ?>
	</div>

	<?= $this->Form->end() ?>
</div>
