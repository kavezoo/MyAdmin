/**
 * Optional international phone input: + prefix, digits only, country default prefix.
 */
(function ($) {
	'use strict';

	function normalizePrefix(prefix) {
		var digits = String(prefix || '').replace(/\D/g, '');
		return digits ? '+' + digits : '';
	}

	function normalizePhoneValue(raw) {
		var value = String(raw || '').trim();
		if (!value) {
			return '';
		}
		var digits = value.replace(/[^\d+]/g, '');
		if (!digits || digits === '+') {
			return '';
		}
		if (digits.charAt(0) !== '+') {
			digits = '+' + digits.replace(/\+/g, '');
		} else {
			digits = '+' + digits.slice(1).replace(/\D/g, '');
		}
		return digits;
	}

	function isOnlyPrefix(value, prefix) {
		var normalized = normalizePhoneValue(value);
		var p = normalizePrefix(prefix);
		if (!normalized) {
			return true;
		}
		if (!p) {
			return normalized === '+';
		}
		return normalized === p;
	}

	function applyInputMask($input) {
		if (!$.fn.inputmask) {
			return;
		}
		$input.inputmask({
			regex: '^\\+[0-9]*$',
			placeholder: '',
			showMaskOnHover: false,
			showMaskOnFocus: false
		});
	}

	function initPhoneField($input, defaultPrefix) {
		var prefix = normalizePrefix(defaultPrefix) || '+';

		$input.attr('data-default-prefix', prefix);

		if (!$input.val() || String($input.val()).trim() === '') {
			$input.val(prefix);
		} else {
			$input.val(normalizePhoneValue($input.val()));
		}

		applyInputMask($input);

		$input.on('focus', function () {
			var $el = $(this);
			var current = normalizePhoneValue($el.val());
			var def = normalizePrefix($el.attr('data-default-prefix')) || '+';
			if (!current) {
				$el.val(def);
			}
		});

		$input.on('blur', function () {
			var $el = $(this);
			var def = normalizePrefix($el.attr('data-default-prefix')) || '+';
			if (isOnlyPrefix($el.val(), def)) {
				$el.val('');
			} else {
				$el.val(normalizePhoneValue($el.val()));
			}
		});

		$input.on('input', function () {
			var $el = $(this);
			var raw = String($el.val() || '');
			if (raw === '') {
				return;
			}
			if (raw.charAt(0) !== '+') {
				$el.val(normalizePhoneValue(raw));
			}
		});
	}

	function initAll(cfg) {
		cfg = cfg || {};
		var prefixes = cfg.phonePrefixes || {};
		var defaultPrefix = normalizePrefix(cfg.defaultPhonePrefix) || '+';
		var $country = $('#country-id');

		$('.js-phone-intl').each(function () {
			var $input = $(this);
			var fieldPrefix = normalizePrefix($input.data('default-prefix'));
			if (!fieldPrefix && $country.length && $country.val()) {
				fieldPrefix = normalizePrefix(prefixes[$country.val()]);
			}
			if (!fieldPrefix) {
				fieldPrefix = defaultPrefix;
			}
			initPhoneField($input, fieldPrefix);
		});

		$country.on('change.usersPhone', function () {
			var id = $(this).val();
			var newPrefix = normalizePrefix(prefixes[id]) || defaultPrefix;
			var $phone = $('.js-phone-intl');
			if (!$phone.length) {
				return;
			}
			var current = normalizePhoneValue($phone.val());
			var oldPrefix = normalizePrefix($phone.attr('data-default-prefix'));
			if (!current || current === oldPrefix || current === '+') {
				$phone.val(newPrefix);
			}
			$phone.attr('data-default-prefix', newPrefix);
		});
	}

	$(function () {
		var cfg = window.UsersPhone || {};
		if (!cfg.phonePrefixes && window.UsersAuthCountry && window.UsersAuthCountry.phonePrefixes) {
			cfg.phonePrefixes = window.UsersAuthCountry.phonePrefixes;
		}
		if (!cfg.defaultPhonePrefix && window.UsersAuthCountry && window.UsersAuthCountry.defaultPhonePrefix) {
			cfg.defaultPhonePrefix = window.UsersAuthCountry.defaultPhonePrefix;
		}
		initAll(cfg);

		// form.js may register after this file — resolve API inside the timeout
		window.setTimeout(function () {
			if (window.MyAdmin && typeof window.MyAdmin.recaptureFormBaseline === 'function') {
				window.MyAdmin.recaptureFormBaseline();
			}
		}, 0);
		window.setTimeout(function () {
			if (window.MyAdmin && typeof window.MyAdmin.recaptureFormBaseline === 'function') {
				window.MyAdmin.recaptureFormBaseline();
			}
		}, 350);

		$('form').on('submit', function () {
			var $phone = $(this).find('.js-phone-intl');
			if (!$phone.length) {
				return;
			}
			var def = normalizePrefix($phone.attr('data-default-prefix')) || '+';
			if (isOnlyPrefix($phone.val(), def)) {
				$phone.val('');
			} else {
				$phone.val(normalizePhoneValue($phone.val()));
			}
		});
	});
})(jQuery);
