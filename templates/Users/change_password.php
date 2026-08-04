<?php
/**
 * App Users — Change password.
 *
 * @var \App\View\AppView $this
 * @var \CakeDC\Users\Model\Entity\User $user
 * @var bool $validatePassword
 */
use Cake\Core\Configure;
use CakeDC\Users\Utility\UsersUrl;

$this->assign('title', __('Change password'));

$validatePassword = (bool)($validatePassword ?? false);
?>
<div class="local-login-form users-auth-form">
	<?= $this->Form->create($user, [
		'url' => UsersUrl::actionUrl('changePassword'),
		'id' => 'users-change-password-form',
	]) ?>
	<h3 class="login-head"><i class="bi bi-key me-2"></i><?= __('Change password') ?></h3>

	<?php if ($validatePassword): ?>
		<div class="mb-3">
			<label class="form-label" for="current-password"><?= __('Current password') ?></label>
			<?= $this->Form->control('current_password', [
				'label' => false,
				'type' => 'password',
				'class' => 'form-control',
				'id' => 'current-password',
				'required' => true,
				'autofocus' => true,
				'placeholder' => __('Current password'),
				'autocomplete' => 'current-password',
			]) ?>
		</div>
	<?php endif; ?>

	<div class="mb-3">
		<label class="form-label" for="new-password"><?= __('New password') ?></label>
		<?= $this->Form->control('password', [
			'label' => false,
			'type' => 'password',
			'class' => 'form-control',
			'id' => 'new-password',
			'required' => true,
			'autofocus' => !$validatePassword,
			'placeholder' => __('New password'),
			'autocomplete' => 'new-password',
		]) ?>
	</div>

	<?php if (Configure::read('Users.passwordMeter.enabled')): ?>
		<div class="mb-3"><?= $this->User->addPasswordMeter() ?></div>
	<?php endif; ?>

	<div class="mb-3">
		<label class="form-label" for="password-confirm"><?= __('Confirm password') ?></label>
		<?= $this->Form->control('password_confirm', [
			'label' => false,
			'type' => 'password',
			'class' => 'form-control',
			'id' => 'password-confirm',
			'required' => true,
			'placeholder' => __('Confirm password'),
			'autocomplete' => 'new-password',
		]) ?>
	</div>

	<div class="mb-3 btn-container d-grid">
		<button type="submit" class="btn btn-primary btn-block" id="btn-submit">
			<i class="bi bi-check2-circle me-2 fs-5"></i><?= __('Change password') ?>
		</button>
	</div>

	<div class="users-auth-links">
		<?= $this->Html->link(__('Back to profile'), UsersUrl::actionUrl('profile')) ?>
	</div>

	<?= $this->Form->end() ?>
</div>
