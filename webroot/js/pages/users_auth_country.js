/**
 * Auth country Select2 (register / complete-profile): searchable + flag icons + reload.
 */
(function ($) {
	'use strict';

	function flagOptionFactory(cfg) {
		return function (opt) {
			if (!opt.id) {
				return opt.text;
			}
			var $wrap = $('<span class="select2-flag-option"></span>');
			var iso = cfg.flags && cfg.flags[opt.id] ? String(cfg.flags[opt.id]) : '';
			if (iso && cfg.flagBase) {
				$wrap.append(
					$('<img>', {
						'class': 'select2-flag',
						src: cfg.flagBase + iso + '.png',
						alt: '',
						width: 20,
						height: 20,
						loading: 'lazy'
					})
				);
			}
			$wrap.append(document.createTextNode(opt.text));
			return $wrap;
		};
	}

	$(function () {
		var cfg = window.UsersAuthCountry || window.UsersRegister || {};
		var $country = $('#country-id');
		if (!$country.length) {
			return;
		}

		if ($.fn.select2) {
			var format = flagOptionFactory(cfg);
			$country.select2({
				theme: 'bootstrap-5',
				width: '100%',
				minimumResultsForSearch: 0,
				dropdownParent: $(document.body),
				placeholder: $country.data('placeholder') || '',
				templateResult: format,
				templateSelection: format,
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
