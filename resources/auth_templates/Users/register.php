<?php
/**
 * App Users — Register.
 *
 * @var \App\View\AppView $this
 * @var \CakeDC\Users\Model\Entity\User $user
 * @var array<int, string> $countryOptions
 * @var array<int, string> $countryLocales
 * @var int $selectedCountryId
 */
use Cake\Core\Configure;
use CakeDC\Users\Utility\UsersUrl;

$this->assign('title', __('Register'));

$countryOptions = $countryOptions ?? [];
$countryLocales = $countryLocales ?? [];
$selectedCountryId = (int)($selectedCountryId ?? 0);
$registerUrl = $this->Url->build(UsersUrl::actionUrl('register'));

$this->Html->css([
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
], ['block' => true]);
$this->Html->script('/plugins/select2-4.1.0/js/select2.full.min', ['block' => true]);
?>
<div class="local-login-form users-auth-form">
	<?= $this->Form->create($user, [
		'url' => UsersUrl::actionUrl('register'),
		'id' => 'users-register-form',
	]) ?>
	<h3 class="login-head"><i class="bi bi-person-plus me-2"></i><?= __('Register') ?></h3>

	<div class="mb-3">
		<label class="form-label" for="country-id"><?= __('Country') ?></label>
		<?= $this->Form->control('country_id', [
			'label' => false,
			'type' => 'select',
			'options' => $countryOptions,
			'empty' => __('Select country...'),
			'class' => 'form-select js-country-select',
			'id' => 'country-id',
			'required' => true,
			'value' => $selectedCountryId > 0 ? $selectedCountryId : null,
			'data-reload-url' => $registerUrl,
			'data-placeholder' => __('Select country...'),
		]) ?>
		<div class="form-text"><?= __('Choose your country by its local name. The country language becomes the site language for your account.') ?></div>
	</div>

	<div class="mb-3">
		<label class="form-label" for="first-name"><?= __('Name') ?></label>
		<?= $this->Form->control('first_name', [
			'label' => false,
			'type' => 'text',
			'class' => 'form-control',
			'id' => 'first-name',
			'required' => true,
			'placeholder' => __('Name'),
			'autocomplete' => 'name',
			'inputmode' => 'text',
			'autocapitalize' => 'words',
		]) ?>
	</div>

	<div class="mb-3">
		<label class="form-label" for="email"><?= __('Email') ?></label>
		<?= $this->Form->control('email', [
			'label' => false,
			'type' => 'email',
			'class' => 'form-control',
			'id' => 'email',
			'required' => true,
			'placeholder' => __('Email'),
			'autocomplete' => 'email',
			'inputmode' => 'email',
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
			'autocomplete' => 'new-password',
		]) ?>
	</div>

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

	<?php if (Configure::read('Users.Tos.required')): ?>
		<div class="mb-3 form-check">
			<?= $this->Form->control('tos', [
				'type' => 'checkbox',
				'label' => __('I accept the terms of service'),
				'required' => true,
				'class' => 'form-check-input',
			]) ?>
		</div>
	<?php endif; ?>

	<?php if (Configure::read('Users.reCaptcha.registration')): ?>
		<div class="mb-3"><?= $this->User->addReCaptcha() ?></div>
	<?php endif; ?>

	<div class="mb-3 btn-container d-grid">
		<button type="submit" class="btn btn-primary btn-block" id="btn-submit">
			<i class="bi bi-check2-circle me-2 fs-5"></i><?= __('Register') ?>
		</button>
	</div>

	<div class="users-auth-links">
		<?= $this->Html->link(__('Already have an account? Login'), UsersUrl::actionUrl('login')) ?>
	</div>

	<?= $this->Form->end() ?>
</div>

<?php
$this->Html->scriptBlock(
	sprintf(
		'window.UsersAuthCountry = %s;',
		json_encode([
			'reloadUrl' => $registerUrl,
			'flagBase' => $this->Url->build('/img/flags/'),
			'flags' => \App\Utility\AdminCountry::registerFlagMap(),
			'searchPlaceholder' => __('Search...'),
			'noResults' => __('No results found.'),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	),
	['block' => true]
);
$this->Html->script('pages/users_auth_country', ['block' => true]);
?>
