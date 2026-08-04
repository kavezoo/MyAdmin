/**
 * Complete profile — club Select2.
 */
(function ($) {
	'use strict';

	$(function () {
		var $club = $('#club-id');
		if (!$club.length || !$.fn.select2) {
			return;
		}

		$club.select2({
			theme: 'bootstrap-5',
			width: '100%',
			minimumResultsForSearch: 0,
			dropdownParent: $(document.body),
			placeholder: $club.data('placeholder') || '',
			allowClear: false
		});
	});
})(jQuery);
