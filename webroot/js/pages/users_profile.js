/**
 * Profile edit — club Select2, phone format, title-case names, avatar delete Swal.
 */
(function ($) {
	'use strict';

	var App = window.MyAdmin || {};

	function titleCaseWord(word) {
		if (!word) {
			return '';
		}
		return word.charAt(0).toLocaleUpperCase() + word.slice(1).toLocaleLowerCase();
	}

	function titleCaseName(value) {
		return String(value || '')
			.trim()
			.split(/\s+/)
			.filter(Boolean)
			.map(titleCaseWord)
			.join(' ');
	}

	function normalizePhoneInput($input) {
		var raw = String($input.val() || '').trim();
		if (raw === '') {
			return;
		}
		var digits = raw.replace(/[^\d+]/g, '');
		if (digits.charAt(0) !== '+') {
			digits = '+' + digits.replace(/\+/g, '');
		} else {
			digits = '+' + digits.slice(1).replace(/\+/g, '');
		}
		digits = digits.replace(/[^\d+]/g, '');
		if (digits.length > 1) {
			digits = '+' + digits.slice(1).replace(/\D/g, '');
		}
		$input.val(digits);
	}

	$(function () {
		var $club = $('#club-id');
		if ($club.length && $.fn.select2) {
			$club.select2({
				theme: 'bootstrap-5',
				width: '100%',
				minimumResultsForSearch: 0,
				dropdownParent: $(document.body),
				placeholder: $club.data('placeholder') || '',
				allowClear: false
			});

			$club.on('select2:open', function () {
				var $search = $('.select2-container--open .select2-search__field');
				if ($search.length) {
					$search.trigger('focus');
				}
			});
		}

		$('.js-title-case-name').on('blur', function () {
			var $el = $(this);
			$el.val(titleCaseName($el.val()));
		});

		var $phone = $('.js-phone-intl');
		$phone.each(function () {
			normalizePhoneInput($(this));
		});
		$phone.on('input blur', function () {
			normalizePhoneInput($(this));
		});

		var $deleteBtn = $('#btn-delete-avatar');
		var $deleteForm = $('#delete-avatar-form');
		if ($deleteBtn.length && $deleteForm.length) {
			$deleteBtn.on('click', function (e) {
				e.preventDefault();
				var cfg = window.UsersProfile || {};
				var title = cfg.deleteAvatarTitle || App.messages.deleteAvatarTitle || 'Remove picture';
				var text = cfg.deleteAvatarConfirm || App.messages.deleteAvatarConfirm || 'Do you really want to delete your profile picture?';
				var confirmText = cfg.deleteAvatarButton || App.messages.deleteButton || 'Delete';
				var cancelText = cfg.deleteAvatarCancel || App.messages.cancelButton || 'Cancel';
				var onConfirm = function () {
					$deleteForm.trigger('submit');
				};

				if (App.swal) {
					App.swal({
						icon: 'warning',
						title: title,
						text: text,
						showCancelButton: true,
						focusCancel: true,
						confirmButtonText: confirmText,
						cancelButtonText: cancelText,
						confirmButtonColor: '#dc3545',
						cancelButtonColor: '#6c757d',
						reverseButtons: true
					}).then(function (result) {
						if (result.isConfirmed) {
							onConfirm();
						}
					});
				} else if (App.confirmDelete) {
					App.confirmDelete({
						title: title,
						text: text,
						icon: 'warning',
						confirmButtonText: confirmText,
						onConfirm: onConfirm
					});
				} else if (window.confirm(text)) {
					onConfirm();
				}
			});
		}

		var $avatarInput = $('#avatar');
		if ($avatarInput.length) {
			$avatarInput.on('change', function () {
				var file = this.files && this.files[0];
				if (!file) {
					return;
				}
				var reader = new FileReader();
				reader.onload = function (ev) {
					var $img = $('.users-profile-avatar-preview');
					if ($img.is('img')) {
						$img.attr('src', ev.target.result);
					}
				};
				reader.readAsDataURL(file);
			});
		}

		var profileCfg = window.UsersProfile || {};
		var $profileForm = $('#profile-form');
		if ($profileForm.length && $club.length) {
			var originalClubId = String(profileCfg.originalClubId || '0');
			var clubSwitchConfirmed = false;

			$profileForm.on('submit', function (e) {
				if (clubSwitchConfirmed) {
					return;
				}
				var newClubId = String($club.val() || '');
				if (
					originalClubId !== '0'
					&& newClubId !== ''
					&& newClubId !== '0'
					&& newClubId !== originalClubId
				) {
					e.preventDefault();
					if (!App.swal) {
						clubSwitchConfirmed = true;
						$profileForm.trigger('submit');
						return;
					}
					App.swal({
						icon: 'warning',
						title: profileCfg.clubChangeSwalTitle || 'Apply to a different club?',
						text: profileCfg.clubChangeSwalText || '',
						showCancelButton: true,
						focusCancel: true,
						confirmButtonText: profileCfg.clubChangeConfirm || 'Yes, apply to this club',
						cancelButtonText: profileCfg.clubChangeCancel || 'Cancel',
						confirmButtonColor: '#dc3545',
						cancelButtonColor: '#6c757d',
						reverseButtons: true
					}).then(function (result) {
						if (result.isConfirmed) {
							clubSwitchConfirmed = true;
							$profileForm.trigger('submit');
						}
					});
				}
			});
		}
	});
})(jQuery);
