/**
 * President Clubs form — AJAX Select2 for club president.
 */
(function ($) {
	'use strict';

	$(function () {
		var cfg = window.PresidentClubsForm || {};
		var $select = $('#club-president-id');
		if (!$select.length || !$.fn.select2) {
			return;
		}

		var ajaxUrl = $select.data('ajax-url') || cfg.ajaxUrl || '';
		$select.select2({
			theme: 'bootstrap-5',
			width: '100%',
			allowClear: true,
			placeholder: $select.data('placeholder') || cfg.placeholder || '',
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
			}
		});
	});
})(jQuery);
