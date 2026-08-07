<?php
/**
 * Complete profile (role `new` — mandatory after registration).
 *
 * @var \App\View\AppView $this
 * @var \CakeDC\Users\Model\Entity\User $user
 * @var array<int, string> $countryOptions
 * @var array<int, string> $clubOptions
 * @var int $selectedCountryId
 * @var string $completeProfileUrl
 */
use App\Utility\PhoneNumber;

$this->assign('title', __('Complete your profile'));
$this->Html->css([
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
	'pages/index',
], ['block' => true]);
$this->Html->script('/plugins/select2-4.1.0/js/select2.full.min', ['block' => 'scriptBottom']);
$this->Html->script('/plugins/inputmask/jquery.inputmask.min', ['block' => 'scriptBottom']);
$this->Html->script('pages/users_auth_country', ['block' => 'scriptBottom']);
$this->Html->script('pages/users_phone', ['block' => 'scriptBottom']);
$this->Html->script('pages/complete_profile', ['block' => 'scriptBottom']);

$countryOptions = $countryOptions ?? [];
$clubOptions = $clubOptions ?? [];
$clubOptionsEmpty = (bool)($clubOptionsEmpty ?? false);
$selectedCountryId = (int)($selectedCountryId ?? 0);
$completeProfileUrl = (string)($completeProfileUrl ?? $this->Url->build('/complete-profile'));
$defaultPhonePrefix = (string)($defaultPhonePrefix ?? '');
$countryPhonePrefixes = $countryPhonePrefixes ?? [];
$phoneInputValue = PhoneNumber::formatForInput($user->get('phone'), $defaultPhonePrefix);
?>
<div class="row mt-3">
	<div class="col-12 col-lg-10 col-xxl-8 p-2 pt-0">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-id-card"></i> <?= __('Complete your profile') ?></h3>
					<?= __('Please fill in the missing details so we can send your membership application to the club president.') ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<?= $this->Form->create($user, [
				'url' => '/complete-profile',
				'id' => 'complete-profile-form',
			]) ?>
			<div class="card-body">
				<?= $this->element('users/form_errors', ['entity' => $user]) ?>
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
					<label class="form-label" for="phone"><?= __('Phone') ?></label>
					<?= $this->Form->control('phone', [
						'label' => false,
						'type' => 'tel',
						'class' => 'form-control js-phone-intl',
						'id' => 'phone',
						'value' => $phoneInputValue,
						'data-default-prefix' => $defaultPhonePrefix !== '' ? $defaultPhonePrefix : '+',
						'autocomplete' => 'tel',
						'inputmode' => 'tel',
						'placeholder' => $defaultPhonePrefix !== '' ? $defaultPhonePrefix . '301234567' : '+36301234567',
					]) ?>
					<div class="form-text"><?= __('Optional. Starts with + and your country calling code (e.g. {0}). Enter your number after the prefix.', $defaultPhonePrefix !== '' ? $defaultPhonePrefix : '+36') ?></div>
				</div>
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
						'data-placeholder' => __('Select country...'),
						'data-clubs-url' => $this->Url->build('/clubs-for-country'),
						'data-include-club-id' => (string)(int)($user->club_id ?? 0),
					]) ?>
				</div>
				<div class="mb-3">
					<label class="form-label" for="club-id"><?= __('Club') ?></label>
					<div class="form-text mb-2 js-club-need-country<?= $selectedCountryId < 1 ? '' : ' d-none' ?>"><?= __('Select your country first to see available clubs.') ?></div>
					<div class="alert alert-warning mb-2 js-club-empty-warning<?= ($selectedCountryId > 0 && $clubOptionsEmpty) ? '' : ' d-none' ?>">
						<?= __('There are no active clubs with a paid national membership fee for this country yet. Please choose another country from the list above.') ?>
					</div>
					<?= $this->Form->control('club_id', [
						'label' => false,
						'type' => 'select',
						'options' => $clubOptions,
						'empty' => __('Select club...'),
						'class' => 'form-select js-club-select',
						'id' => 'club-id',
						'required' => $clubOptions !== [],
						'disabled' => false,
						'value' => (int)($user->club_id ?? 0) > 0 ? (int)$user->club_id : null,
						'data-placeholder' => __('Select club...'),
					]) ?>
				</div>
				<div class="mb-0">
					<label class="form-label" for="language-id"><?= __('Language') ?></label>
					<?= $this->Form->control('language_id', [
						'label' => false,
						'type' => 'select',
						'options' => $languageOptions ?? [],
						'empty' => __('Same as country (default)'),
						'class' => 'form-select js-language-select',
						'id' => 'language-id',
						'required' => false,
						'value' => (int)($user->language_id ?? 0) > 0 ? (int)$user->language_id : null,
						'data-placeholder' => __('Same as country (default)'),
					]) ?>
					<div class="form-text"><?= __('Emails and messages use this language when set; otherwise your country’s language.') ?></div>
				</div>
			</div>
			<div class="card-footer">
				<button type="submit" class="btn btn-primary">
					<span class="btn-label"><i class="fa fa-check"></i></span>
					<?= h(__('Submit application')) ?>
				</button>
			</div>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>
<script>
window.UsersAuthCountry = window.UsersAuthCountry || {};
window.UsersAuthCountry.clubsUrl = <?= json_encode($this->Url->build('/clubs-for-country'), JSON_UNESCAPED_SLASHES) ?>;
window.UsersAuthCountry.includeClubId = <?= (int)($user->club_id ?? 0) ?>;
window.UsersAuthCountry.clubPlaceholder = <?= json_encode(__('Select club...'), JSON_UNESCAPED_UNICODE) ?>;
window.UsersAuthCountry.clubsLoadFailed = <?= json_encode(__('Failed to load clubs.'), JSON_UNESCAPED_UNICODE) ?>;
window.UsersAuthCountry.flagBase = <?= json_encode($this->Url->build('/img/flags/'), JSON_UNESCAPED_SLASHES) ?>;
window.UsersAuthCountry.flags = <?= json_encode(
	\App\Utility\AdminCountry::iso2Map(array_map('intval', array_keys($countryOptions))),
	JSON_UNESCAPED_UNICODE
) ?>;
window.UsersAuthCountry.noResults = <?= json_encode(__('No results found.')) ?>;
window.UsersAuthCountry.searchPlaceholder = <?= json_encode(__('Search...')) ?>;
window.UsersAuthCountry.phonePrefixes = <?= json_encode($countryPhonePrefixes, JSON_UNESCAPED_UNICODE) ?>;
window.UsersAuthCountry.defaultPhonePrefix = <?= json_encode($defaultPhonePrefix, JSON_UNESCAPED_UNICODE) ?>;
window.UsersPhone = {
	phonePrefixes: window.UsersAuthCountry.phonePrefixes,
	defaultPhonePrefix: window.UsersAuthCountry.defaultPhonePrefix
};
</script>
