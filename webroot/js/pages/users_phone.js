/**
 * Optional international phone input: digits with optional +, country prefix only as placeholder.
 */
(function ($) {
	'use strict';

	function normalizePrefix(prefix) {
		var digits = String(prefix || '').replace(/\D/g, '');
		return digits ? '+' + digits : '';
	}

	/**
	 * @param {string} raw
	 * @param {string} [defaultPrefix]
	 * @returns {string}
	 */
	function normalizePhoneValue(raw, defaultPrefix) {
		var value = String(raw || '').trim();
		if (!value) {
			return '';
		}
		var prefix = normalizePrefix(defaultPrefix);
		var hasPlus = value.charAt(0) === '+';
		var digits = value.replace(/\D/g, '');
		if (!digits) {
			return '';
		}
		if (hasPlus) {
			return '+' + digits;
		}
		// Local number without +: prepend country calling code.
		if (prefix) {
			var prefixDigits = prefix.replace(/\D/g, '');
			if (prefixDigits && digits.indexOf(prefixDigits) === 0) {
				return '+' + digits;
			}
			return prefix + digits;
		}
		return '+' + digits;
	}

	function isOnlyPrefix(value, prefix) {
		var p = normalizePrefix(prefix);
		var normalized = normalizePhoneValue(value, p);
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
		if ($input.data('inputmask')) {
			$input.inputmask('remove');
		}
		// Allow empty or +digits (prefix is placeholder, not forced into the value).
		$input.inputmask({
			regex: '^(\\+[0-9]*)?$',
			placeholder: '',
			showMaskOnHover: false,
			showMaskOnFocus: false,
			clearIncomplete: false
		});
	}

	function setPrefixHint($input, prefix) {
		var p = normalizePrefix(prefix);
		$input.attr('data-default-prefix', p || '+');
		$input.attr('placeholder', p || '+');
	}

	function initPhoneField($input, defaultPrefix) {
		var prefix = normalizePrefix(defaultPrefix) || '+';
		setPrefixHint($input, prefix);

		var current = String($input.val() || '').trim();
		if (!current || isOnlyPrefix(current, prefix)) {
			$input.val('');
		} else {
			$input.val(normalizePhoneValue(current, prefix));
		}

		applyInputMask($input);

		$input.off('.usersPhone').on('blur.usersPhone', function () {
			var $el = $(this);
			var def = normalizePrefix($el.attr('data-default-prefix')) || '+';
			if (isOnlyPrefix($el.val(), def)) {
				$el.val('');
			} else {
				$el.val(normalizePhoneValue($el.val(), def));
			}
		});

		$input.on('input.usersPhone', function () {
			var $el = $(this);
			var raw = String($el.val() || '');
			if (raw === '') {
				return;
			}
			// Keep typing free; only soft-fix if user pasted junk without leading +
			if (raw.indexOf('+') > 0) {
				$el.val(normalizePhoneValue(raw, $el.attr('data-default-prefix')));
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

		$country.off('change.usersPhone').on('change.usersPhone', function () {
			var id = $(this).val();
			var newPrefix = normalizePrefix(prefixes[id]) || defaultPrefix;
			var $phone = $('.js-phone-intl');
			if (!$phone.length) {
				return;
			}
			var current = String($phone.val() || '').trim();
			var oldPrefix = normalizePrefix($phone.attr('data-default-prefix'));
			if (!current || isOnlyPrefix(current, oldPrefix)) {
				$phone.val('');
			}
			setPrefixHint($phone, newPrefix);
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
				$phone.val(normalizePhoneValue($phone.val(), def));
			}
		});
	});
})(jQuery);
