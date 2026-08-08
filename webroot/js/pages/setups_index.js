/**
 * Working country Select2 — change → reload with ?country_id= (keeps other query params).
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
			minimumResultsForSearch: 0
		});

		$el.on('change', function () {
			var id = $el.val();
			if (!id) {
				return;
			}
			var base = $el.data('change-url') || window.location.pathname;
			var url;
			try {
				url = new URL(base, window.location.origin);
			} catch (e) {
				url = new URL(window.location.href);
				url.pathname = String(base).split('?')[0];
			}
			// Preserve current list query (sort / q / limit), replace country + page.
			var current = new URL(window.location.href);
			current.searchParams.forEach(function (value, key) {
				if (!url.searchParams.has(key)) {
					url.searchParams.set(key, value);
				}
			});
			url.searchParams.set('country_id', String(id));
			url.searchParams.set('page', '1');
			window.location.href = url.pathname + url.search;
		});
	});
})(jQuery);
