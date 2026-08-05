/**
 * Complete profile — club Select2 + optional phone format.
 */
(function ($) {
	'use strict';

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

		var $phone = $('.js-phone-intl');
		$phone.each(function () {
			normalizePhoneInput($(this));
		});
		$phone.on('input blur', function () {
			normalizePhoneInput($(this));
		});
	});
})(jQuery);
