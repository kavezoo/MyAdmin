/**
 * Admin Event logs index — country Select2 + user AJAX Select2.
 */
(function ($) {
	'use strict';

	$(function () {
		var cfg = window.EventLogsIndex || {};
		var $country = $('#event-log-country');
		var $user = $('#event-log-user');

		if ($country.length && $.fn.select2) {
			$country.select2({ theme: 'bootstrap-5', width: '100%' });
		}

		if (!$user.length || !$.fn.select2) {
			return;
		}

		var ajaxUrl = $user.data('ajax-url') || '';
		$user.select2({
			theme: 'bootstrap-5',
			width: '100%',
			allowClear: true,
			placeholder: $user.data('placeholder') || '',
			minimumInputLength: 2,
			language: {
				noResults: function () {
					return cfg.noResults || 'No results found.';
				},
				searching: function () {
					return cfg.searching || 'Search...';
				},
				inputTooShort: function () {
					return cfg.inputTooShort || 'Please enter 2 or more characters';
				}
			},
			ajax: {
				url: ajaxUrl,
				dataType: 'json',
				delay: 250,
				data: function (params) {
					return {
						q: params.term || '',
						page: params.page || 1,
						country_id: $country.length ? $country.val() : ($user.data('country-id') || '')
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
			}
		});

		// Changing country invalidates the selected user (different country scope).
		$country.on('change', function () {
			$user.val(null).trigger('change');
		});
	});
})(jQuery);
