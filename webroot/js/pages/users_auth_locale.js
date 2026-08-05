/**
 * Auth language Select2 (login): searchable + flag icons + reload with ?locale=.
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
		var cfg = window.UsersAuthLocale || {};
		var $locale = $('#locale');
		if (!$locale.length) {
			return;
		}

		if ($.fn.select2) {
			var format = flagOptionFactory(cfg);
			$locale.select2({
				theme: 'bootstrap-5',
				width: '100%',
				minimumResultsForSearch: 0,
				dropdownParent: $(document.body),
				placeholder: $locale.data('placeholder') || '',
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
