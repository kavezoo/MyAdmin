/**
 * Admin Cities index — AJAX country Select2 filter (flags).
 */
(function ($) {
	'use strict';

	function flagFactory(cfg) {
		return function (opt) {
			if (!opt.id && opt.id !== 0 && opt.id !== '0') {
				return opt.text;
			}
			var $wrap = $('<span class="select2-flag-option"></span>');
			var iso = '';
			if (opt.iso2) {
				iso = String(opt.iso2).toLowerCase();
			} else if (cfg.flags && cfg.flags[opt.id]) {
				iso = String(cfg.flags[opt.id]);
			}
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
			$wrap.append(document.createTextNode(opt.text || ''));
			return $wrap;
		};
	}

	$(function () {
		var cfg = window.AdminCitiesIndex || {};
		var $sel = $('#cities-country-id');
		var $form = $('#cities-country-filter');
		if (!$sel.length || !$.fn.select2) {
			return;
		}

		var format = flagFactory(cfg);
		$sel.select2({
			theme: 'bootstrap-5',
			width: 'style',
			allowClear: false,
			placeholder: $sel.data('placeholder') || cfg.countryPlaceholder || '',
			minimumInputLength: 0,
			templateResult: format,
			templateSelection: format,
			ajax: {
				url: $sel.data('ajax-url') || cfg.countryAjaxUrl || '',
				dataType: 'json',
				delay: 200,
				data: function (params) {
					return {
						q: params.term || '',
						page: params.page || 1
					};
				},
				processResults: function (data) {
					return {
						results: (data && data.results) ? data.results : [],
						pagination: {
							more: !!(data && data.pagination && data.pagination.more)
						}
					};
				},
				cache: true
			},
			language: {
				noResults: function () { return cfg.noResults || 'No results found.'; },
				searching: function () { return cfg.searching || 'Search...'; }
			}
		});

		$sel.on('change', function () {
			if ($form.length) {
				$form.trigger('submit');
			}
		});
	});
})(jQuery);
