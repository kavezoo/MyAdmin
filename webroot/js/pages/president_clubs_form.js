/**
 * President Clubs form — country (flags) + city + club president AJAX Select2.
 */
(function ($) {
	'use strict';

	function flagFactory(cfg) {
		return function (opt) {
			if (!opt.id) {
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

	function destroySelect2($el) {
		if (!$el.length || !$.fn.select2) {
			return;
		}
		if ($el.data('select2') || $el.hasClass('select2-hidden-accessible')) {
			try {
				$el.select2('destroy');
			} catch (err) { /* ignore */ }
		}
	}

	function selectedCountryId() {
		var v = parseInt($('#country-id').val(), 10);
		return !isNaN(v) && v > 0 ? v : 0;
	}

	function clearCity() {
		var $city = $('#city-id');
		destroySelect2($city);
		$city.empty().append(new Option('', '', true, true)).val('');
		initCitySelect2();
	}

	function clearPresident() {
		var $pres = $('#club-president-id');
		destroySelect2($pres);
		$pres.empty().append(new Option('', '', true, true)).val('');
		initPresidentSelect2();
	}

	function initCountrySelect2() {
		var cfg = window.PresidentClubsForm || {};
		var $sel = $('#country-id');
		if (!$sel.length || !$.fn.select2) {
			return;
		}
		var format = flagFactory(cfg);
		destroySelect2($sel);
		$sel.select2({
			theme: 'bootstrap-5',
			width: '100%',
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
					var results = (data && data.results) ? data.results : [];
					results.forEach(function (r) {
						if (r.iso2 && cfg.flags) {
							cfg.flags[r.id] = r.iso2;
						}
					});
					return {
						results: results,
						pagination: {
							more: !!(data && data.pagination && data.pagination.more)
						}
					};
				},
				cache: true
			},
			language: {
				noResults: function () { return cfg.noResults || 'No results found.'; },
				searching: function () { return cfg.searching || 'Search...'; },
				inputTooShort: function () { return cfg.inputTooShort || ''; }
			}
		});

		$sel.off('change.presidentClubs').on('change.presidentClubs', function () {
			clearCity();
			clearPresident();
			var cid = selectedCountryId();
			var rememberUrl = cfg.rememberCountryUrl || '';
			if (cid > 0 && rememberUrl) {
				var token = $('meta[name="csrfToken"]').attr('content') || '';
				$.ajax({
					url: rememberUrl,
					method: 'POST',
					dataType: 'json',
					headers: token ? { 'X-CSRF-Token': token } : {},
					data: { country_id: cid, _csrfToken: token }
				});
			}
		});
	}

	function initCitySelect2() {
		var cfg = window.PresidentClubsForm || {};
		var $sel = $('#city-id');
		if (!$sel.length || !$.fn.select2) {
			return;
		}
		destroySelect2($sel);
		$sel.select2({
			theme: 'bootstrap-5',
			width: '100%',
			allowClear: true,
			placeholder: $sel.data('placeholder') || cfg.cityPlaceholder || '',
			minimumInputLength: 2,
			ajax: {
				url: $sel.data('ajax-url') || cfg.cityAjaxUrl || '',
				dataType: 'json',
				delay: 250,
				data: function (params) {
					return {
						q: params.term || '',
						page: params.page || 1,
						country_id: selectedCountryId()
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
				searching: function () { return cfg.searching || 'Search...'; },
				inputTooShort: function () {
					return cfg.cityPlaceholder || cfg.inputTooShort || 'Please enter 2 or more characters';
				}
			}
		});
	}

	function initPresidentSelect2() {
		var cfg = window.PresidentClubsForm || {};
		var $sel = $('#club-president-id');
		if (!$sel.length || !$.fn.select2) {
			return;
		}
		destroySelect2($sel);
		$sel.select2({
			theme: 'bootstrap-5',
			width: '100%',
			allowClear: true,
			placeholder: $sel.data('placeholder') || cfg.presidentPlaceholder || '',
			minimumInputLength: 2,
			ajax: {
				url: $sel.data('ajax-url') || cfg.userAjaxUrl || '',
				dataType: 'json',
				delay: 250,
				data: function (params) {
					return {
						q: params.term || '',
						page: params.page || 1,
						country_id: selectedCountryId()
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
				searching: function () { return cfg.searching || 'Search...'; },
				inputTooShort: function () { return cfg.inputTooShort || 'Please enter 2 or more characters'; }
			}
		});
	}

	$(function () {
		initCountrySelect2();
		initCitySelect2();
		initPresidentSelect2();
	});
})(jQuery);
