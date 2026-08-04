/**
 * Setups index — working country Select2 change → reload with ?country_id=
 */
(function ($) {
	'use strict';

	$(function () {
		var $el = $('#working-country-id');
		if (!$el.length || !$.fn.select2) {
			return;
		}

		$el.select2({
			theme: 'bootstrap-5',
			width: '100%',
			// Always allow typing — options are page-locale country names (Translate).
			minimumResultsForSearch: 0
		});

		$el.on('change', function () {
			var url = $el.data('change-url') || window.location.pathname;
			var id = $el.val();
			if (!id) {
				return;
			}
			var sep = url.indexOf('?') >= 0 ? '&' : '?';
			window.location.href = url + sep + 'country_id=' + encodeURIComponent(id);
		});
	});
})(jQuery);
