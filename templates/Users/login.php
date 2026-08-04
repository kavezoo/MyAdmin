<?php
/**
 * App Users — Login (App\Controller\UsersController).
 *
 * @var \App\View\AppView $this
 * @var array<int, string> $countryOptions
 * @var int $selectedCountryId
 */
use Cake\Core\Configure;
use CakeDC\Users\Utility\UsersUrl;

$this->assign('title', __('Login'));

$socialEnabled = (bool)Configure::read('Users.Social.login');
$registrationActive = (bool)Configure::read('Users.Registration.active');
$countryOptions = $countryOptions ?? [];
$selectedCountryId = (int)($selectedCountryId ?? 0);
$loginUrl = $this->Url->build(UsersUrl::actionUrl('login'));

$this->Html->css([
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
], ['block' => true]);
$this->Html->script('/plugins/select2-4.1.0/js/select2.full.min', ['block' => true]);
?>
<div class="local-login-form users-auth-form">
	<h3 class="login-head"><i class="bi bi-person me-2"></i><?= __('Login') ?></h3>

	<?php // Country is outside the login POST (only switches UI locale via GET). ?>
	<div class="mb-3">
		<label class="form-label" for="country-id"><?= __('Country') ?></label>
		<?= $this->Form->select('country_id', $countryOptions, [
			'empty' => __('Select country...'),
			'class' => 'form-select js-country-select',
			'id' => 'country-id',
			'value' => $selectedCountryId > 0 ? $selectedCountryId : null,
			'data-reload-url' => $loginUrl,
			'data-placeholder' => __('Select country...'),
		]) ?>
	</div>

	<?= $this->Form->create(null, [
		'url' => UsersUrl::actionUrl('login'),
		'id' => 'users-login-form',
	]) ?>

	<?php if ($socialEnabled): ?>
		<div class="mb-3">
			<?= implode(' ', $this->User->socialLoginList()) ?>
		</div>
		<hr>
	<?php endif; ?>

	<div class="mb-3">
		<label class="form-label" for="email"><?= __('Email') ?></label>
		<?= $this->Form->control('email', [
			'label' => false,
			'type' => 'email',
			'class' => 'form-control',
			'id' => 'email',
			'required' => true,
			'autofocus' => true,
			'placeholder' => __('Email'),
			'autocomplete' => 'username',
		]) ?>
	</div>
	<div class="mb-3">
		<label class="form-label" for="password"><?= __('Password') ?></label>
		<?= $this->Form->control('password', [
			'label' => false,
			'type' => 'password',
			'class' => 'form-control',
			'id' => 'password',
			'required' => true,
			'placeholder' => __('Password'),
			'autocomplete' => 'current-password',
		]) ?>
	</div>

	<?php if (Configure::read('Users.RememberMe.active')): ?>
		<div class="mb-3 form-check">
			<?= $this->Form->control(Configure::read('Users.Key.Data.rememberMe'), [
				'type' => 'checkbox',
				'label' => __('Remember me'),
				'checked' => (bool)Configure::read('Users.RememberMe.checked'),
				'class' => 'form-check-input',
				'templates' => [
					'inputContainer' => '{{content}}',
					'nestingLabel' => '{{input}} <label class="form-check-label"{{attrs}}>{{text}}</label>',
				],
			]) ?>
		</div>
	<?php endif; ?>

	<?php if (Configure::read('Users.reCaptcha.login')): ?>
		<div class="mb-3"><?= $this->User->addReCaptcha() ?></div>
	<?php endif; ?>

	<div class="mb-3 btn-container d-grid">
		<button type="submit" class="btn btn-primary btn-block">
			<i class="bi bi-box-arrow-in-right me-2 fs-5"></i><?= __('Login') ?>
		</button>
	</div>

	<div class="users-auth-links">
		<?php if ($registrationActive): ?>
			<?= $this->Html->link(__('Register'), UsersUrl::actionUrl('register')) ?>
		<?php endif; ?>
		<?php if (Configure::read('Users.Email.required')): ?>
			<?= $this->Html->link(__('Forgot password?'), UsersUrl::actionUrl('requestResetPassword')) ?>
		<?php endif; ?>
	</div>

	<?= $this->Form->end() ?>
</div>

<?php
$this->Html->scriptBlock(
	sprintf(
		'window.UsersAuthCountry = %s;',
		json_encode([
			'reloadUrl' => $loginUrl,
			'searchPlaceholder' => __('Search...'),
			'noResults' => __('No results found.'),
		], JSON_UNESCAPED_UNICODE)
	),
	['block' => true]
);
$this->Html->script('pages/users_auth_country', ['block' => true]);
?>
