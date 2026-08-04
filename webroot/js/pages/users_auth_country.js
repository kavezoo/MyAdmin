/**
 * Auth country Select2 (login + register): searchable + reload with ?country_id=.
 */
(function ($) {
	'use strict';

	$(function () {
		var cfg = window.UsersAuthCountry || window.UsersRegister || {};
		var $country = $('#country-id');
		if (!$country.length) {
			return;
		}

		if ($.fn.select2) {
			$country.select2({
				theme: 'bootstrap-5',
				width: '100%',
				minimumResultsForSearch: 0,
				dropdownParent: $(document.body),
				placeholder: $country.data('placeholder') || '',
				language: {
					noResults: function () {
						return cfg.noResults || 'No results found.';
					},
					searching: function () {
						return cfg.searchPlaceholder || 'Search...';
					}
				}
			});

			$country.on('select2:open', function () {
				var $search = $('.select2-container--open .select2-search__field');
				if ($search.length) {
					$search.trigger('focus');
				}
			});
		}

		$country.on('change', function () {
			var id = $(this).val();
			if (!id) {
				return;
			}
			var base = cfg.reloadUrl
				|| $(this).data('reload-url')
				|| cfg.registerUrl
				|| $(this).data('register-url')
				|| window.location.pathname;
			var url = base + (base.indexOf('?') >= 0 ? '&' : '?') + 'country_id=' + encodeURIComponent(id);
			window.location.href = url;
		});
	});
})(jQuery);
