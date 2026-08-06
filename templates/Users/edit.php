<?php
/**
 * App Users — Edit own profile (details + avatar).
 *
 * @var \App\View\AppView $this
 * @var \CakeDC\Users\Model\Entity\User $user
 * @var bool $canManageAvatar
 * @var string $countryLabel
 * @var array<int, string> $countryOptions
 * @var array<int, string> $clubOptions
 * @var int $selectedCountryId
 * @var int $selectedClubId
 * @var string $profileUrl
 * @var string $editUrl
 * @var string $deleteAvatarUrl
 * @var int $profileOriginalClubId
 */
use App\Auth\AppRoles;
use App\Auth\MembershipProfile;
use App\Utility\PhoneNumber;
use App\Utility\UserAvatar;
use CakeDC\Users\Utility\UsersUrl;

$this->assign('title', __('Edit profile'));
$this->Html->css([
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
	'pages/index',
	'pages/form',
	'pages/users_profile',
], ['block' => true]);

$this->Html->script('/plugins/inputmask/jquery.inputmask.min', ['block' => 'scriptBottom']);
$this->Html->script('pages/users_phone', ['block' => 'scriptBottom']);
$this->Html->script('/plugins/select2-4.1.0/js/select2.full.min', ['block' => 'scriptBottom']);
$this->Html->script('pages/users_auth_country', ['block' => 'scriptBottom']);
$this->Html->script('pages/form', ['block' => 'scriptBottom']);
$this->Html->script('pages/users_profile', ['block' => 'scriptBottom']);

$canManageAvatar = (bool)($canManageAvatar ?? false);
$countryOptions = $countryOptions ?? [];
$clubOptions = $clubOptions ?? [];
$clubOptionsEmpty = (bool)($clubOptionsEmpty ?? false);
$selectedCountryId = (int)($selectedCountryId ?? 0);
$selectedClubId = (int)($selectedClubId ?? (int)($user->club_id ?? 0));
$profileUrl = (string)($profileUrl ?? $this->Url->build(UsersUrl::actionUrl('profile')));
$editUrl = (string)($editUrl ?? $this->Url->build(UsersUrl::actionUrl('edit')));
$deleteAvatarUrl = (string)($deleteAvatarUrl ?? $this->Url->build(UsersUrl::actionUrl('deleteAvatar')));

$displayName = MembershipProfile::displayName($user);
if ($displayName === '') {
	$displayName = (string)($user->username ?? $user->email ?? '');
}
$roleKey = strtolower(trim((string)($user->role ?? '')));
$roleLabel = $roleKey !== '' ? AppRoles::labeled($roleKey) : '';
$isSuperuser = \App\Auth\CurrentUser::truthyFlag($user->get('is_superuser'));
$userIdStr = trim((string)($user->id ?? ''));
$avatarPath = $userIdStr !== '' ? UserAvatar::displayPath($userIdStr, (string)($user->avatar ?? '')) : '';
$avatarUrl = $avatarPath !== '' ? $this->Url->build(UserAvatar::publicUrlFor($userIdStr, (string)($user->avatar ?? ''))) : '';
$profileOriginalClubId = (int)($profileOriginalClubId ?? 0);
$defaultPhonePrefix = (string)($defaultPhonePrefix ?? '');
$countryPhonePrefixes = $countryPhonePrefixes ?? [];
$phoneInputValue = PhoneNumber::formatForInput($user->get('phone'), $defaultPhonePrefix);
?>
<div class="row mt-3">
	<div class="col-12 col-lg-10 col-xxl-9 p-2 pt-0">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-pencil"></i> <?= __('Edit profile') ?></h3>
					<?= __('Update your personal details and profile picture.') ?>
				</div>
				<div class="float-right">
					<a role="button" href="<?= h($profileUrl) ?>" class="btn btn-outline-secondary" title="<?= h(__('Back to profile')) ?>">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>

			<?php if ($canManageAvatar && $avatarUrl !== ''): ?>
				<?= $this->Form->create(null, [
					'url' => UsersUrl::actionUrl('deleteAvatar'),
					'id' => 'delete-avatar-form',
					'class' => 'd-none',
				]) ?>
				<?= $this->Form->end() ?>
			<?php endif; ?>
			<?= $this->Form->create($user, [
				'url' => UsersUrl::actionUrl('edit'),
				'id' => 'form-horizontal',
				'type' => 'file',
				'enctype' => 'multipart/form-data',
			]) ?>
			<div class="card-body">
				<?= $this->element('users/form_errors', ['entity' => $user]) ?>

				<?php if ($canManageAvatar): ?>
					<div class="users-profile-avatar-block mb-4">
						<h5 class="mb-2"><?= __('Profile picture') ?></h5>
						<div class="alert alert-info users-profile-avatar-hint mb-3">
							<i class="fa fa-info-circle me-1" aria-hidden="true"></i>
							<?= __('For a consistent look across the site, use a square image of 1000×1000 pixels. Other sizes or aspect ratios may not display well.') ?>
						</div>
						<div class="d-flex align-items-start gap-3 flex-wrap">
							<div class="users-profile-avatar-preview-wrap">
								<?php if ($avatarUrl !== ''): ?>
									<img src="<?= h($avatarUrl) ?>" alt="<?= h($displayName) ?>" class="users-profile-avatar-preview rounded-circle" width="120" height="120">
								<?php else: ?>
									<div class="users-profile-avatar-preview users-profile-avatar-placeholder rounded-circle d-flex align-items-center justify-content-center text-secondary" aria-hidden="true">
										<i class="fa fa-user fa-3x"></i>
									</div>
								<?php endif; ?>
							</div>
							<div class="flex-grow-1">
								<label class="form-label" for="avatar"><?= __('Upload image') ?></label>
								<?= $this->Form->control('avatar', [
									'label' => false,
									'type' => 'file',
									'class' => 'form-control',
									'id' => 'avatar',
									'accept' => 'image/jpeg,image/png,image/webp',
								]) ?>
								<div class="form-text"><?= __('JPEG, PNG or WebP. Max. 5 MB. Recommended: 1000×1000 px square.') ?></div>
								<?php
								$avatarErrors = $user->getError('avatar');
								if ($avatarErrors):
									$avatarErrorText = is_array($avatarErrors)
										? implode(' ', array_map('strval', $avatarErrors))
										: (string)$avatarErrors;
								?>
									<div class="form-error"><?= h($avatarErrorText) ?></div>
								<?php endif; ?>
								<?php if ($avatarUrl !== ''): ?>
									<button type="button" class="btn btn-outline-danger btn-sm mt-2" id="btn-delete-avatar">
										<i class="fa fa-trash"></i> <?= __('Remove picture') ?>
									</button>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<div class="mb-3">
					<label class="form-label" for="first-name"><?= __('Name') ?> <span class="text-danger">*</span></label>
					<?= $this->Form->control('first_name', [
						'label' => false,
						'type' => 'text',
						'class' => 'form-control js-title-case-name',
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
					<label class="form-label" for="country-id"><?= __('Country') ?> <span class="text-danger">*</span></label>
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
						'data-include-club-id' => (string)(int)$profileOriginalClubId,
					]) ?>
					<div class="form-text"><?= __('You can correct your registered country if you chose the wrong one.') ?></div>
				</div>

				<div class="mb-3">
					<label class="form-label" for="club-id"><?= __('Club') ?> <span class="text-danger">*</span></label>
					<div class="users-profile-club-warning alert alert-danger border border-danger mb-3" role="alert">
						<div class="fw-bold users-profile-club-warning-title">
							<i class="fa fa-exclamation-triangle me-1" aria-hidden="true"></i>
							<?= __('Changing your club has important consequences') ?>
						</div>
						<p class="mb-0 mt-2 users-profile-club-warning-text">
							<?= __('If you choose a different club and save, your role will be set to “new”. You will not be able to use this system until the president of the chosen club approves your membership application and registers you as a member.') ?>
						</p>
					</div>
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
						// Never disabled: omitted from POST → false “Please select your club.”
						'disabled' => false,
						'value' => $selectedClubId > 0 ? $selectedClubId : null,
						'data-placeholder' => __('Select club...'),
					]) ?>
				</div>

				<dl class="record-view-fields mb-0">
					<div class="record-view-row"><dt><?= __('Email') ?></dt><dd><?= h((string)$user->email) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Username') ?></dt><dd><?= h((string)$user->username) ?></dd></div>
					<?php if ($roleLabel !== ''): ?>
						<div class="record-view-row"><dt><?= __('Role') ?></dt><dd><?= h($roleLabel) ?></dd></div>
					<?php endif; ?>
					<?php if ($isSuperuser): ?>
						<div class="record-view-row"><dt><?= __('Superuser') ?></dt><dd><?= h(__('Yes')) ?></dd></div>
					<?php endif; ?>
				</dl>

			</div>
			<div class="card-footer">
				<div class="record-view-footer-actions">
					<button type="submit" class="btn btn-primary" form="form-horizontal" id="btn-save">
						<span class="btn-label"><i class="fa fa-save"></i></span><?= h(__('Save')) ?>
					</button>
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-eye"></i></span>' . h(__('View details')),
						$profileUrl,
						['escape' => false, 'class' => 'btn btn-info']
					) ?>
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-key"></i></span>' . h(__('Change password')),
						UsersUrl::actionUrl('changePassword'),
						['escape' => false, 'class' => 'btn btn-outline-secondary']
					) ?>
				</div>
			</div>
			<?= $this->Form->end() ?>

			<script>
			window.UsersAuthCountry = window.UsersAuthCountry || {};
			window.UsersAuthCountry.clubsUrl = <?= json_encode($this->Url->build('/clubs-for-country'), JSON_UNESCAPED_SLASHES) ?>;
			window.UsersAuthCountry.includeClubId = <?= (int)$profileOriginalClubId ?>;
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
			window.UsersProfile = {
				deleteAvatarUrl: <?= json_encode($deleteAvatarUrl) ?>,
				originalClubId: <?= (int)$profileOriginalClubId ?>,
				deleteAvatarTitle: <?= json_encode(__('Remove picture'), JSON_UNESCAPED_UNICODE) ?>,
				deleteAvatarConfirm: <?= json_encode(__('Do you really want to delete your profile picture?'), JSON_UNESCAPED_UNICODE) ?>,
				deleteAvatarButton: <?= json_encode(__('Delete picture'), JSON_UNESCAPED_UNICODE) ?>,
				deleteAvatarCancel: <?= json_encode(__('Cancel'), JSON_UNESCAPED_UNICODE) ?>,
				clubChangeSwalTitle: <?= json_encode(__('Apply to a different club?'), JSON_UNESCAPED_UNICODE) ?>,
				clubChangeSwalText: <?= json_encode(__('Do you really want to change your club? If you save, you will lose your current access and cannot use the system until the club president of the new club approves your application and registers you as a member.'), JSON_UNESCAPED_UNICODE) ?>,
				clubChangeConfirm: <?= json_encode(__('Yes, apply to this club'), JSON_UNESCAPED_UNICODE) ?>,
				clubChangeCancel: <?= json_encode(__('Cancel'), JSON_UNESCAPED_UNICODE) ?>
			};
			</script>
		</div>
	</div>
</div>
