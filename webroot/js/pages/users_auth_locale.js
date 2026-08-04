/**
 * Auth language Select2 (login): searchable + reload with ?locale=.
 */
(function ($) {
	'use strict';

	$(function () {
		var cfg = window.UsersAuthLocale || {};
		var $locale = $('#locale');
		if (!$locale.length) {
			return;
		}

		if ($.fn.select2) {
			$locale.select2({
				theme: 'bootstrap-5',
				width: '100%',
				minimumResultsForSearch: 0,
				dropdownParent: $(document.body),
				placeholder: $locale.data('placeholder') || '',
				language: {
					noResults: function () {
						return cfg.noResults || 'No results found.';
					},
					searching: function () {
						return cfg.searchPlaceholder || 'Search...';
					}
				}
			});

			$locale.on('select2:open', function () {
				var $search = $('.select2-container--open .select2-search__field');
				if ($search.length) {
					$search.trigger('focus');
				}
			});
		}

		$locale.on('change', function () {
			var code = $(this).val();
			if (!code) {
				return;
			}
			var base = cfg.reloadUrl
				|| $(this).data('reload-url')
				|| window.location.pathname;
			var url = base + (base.indexOf('?') >= 0 ? '&' : '?') + 'locale=' + encodeURIComponent(code);
			window.location.href = url;
		});
	});
})(jQuery);
