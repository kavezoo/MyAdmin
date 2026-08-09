/**
 * Competition staff — AJAX Select2 user search (.js-staff-user-ajax).
 */
(function ($) {
	'use strict';

	function cfg() {
		return (window.MyAdmin && window.MyAdmin.config && window.MyAdmin.config.competitionStaff) || {};
	}

	function initStaffUserSelect2() {
		var $sel = $('.js-staff-user-ajax');
		if (!$sel.length || !$.fn.select2) {
			return;
		}
		var c = cfg();
		$sel.each(function () {
			var $el = $(this);
			if ($el.hasClass('select2-hidden-accessible')) {
				$el.select2('destroy');
			}
			$el.select2({
				theme: 'bootstrap-5',
				width: '100%',
				allowClear: true,
				placeholder: $el.data('placeholder') || '',
				minimumInputLength: 2,
				ajax: {
					url: $el.data('ajax-url') || '',
					dataType: 'json',
					delay: 250,
					data: function (params) {
						return {
							q: params.term || '',
							page: params.page || 1,
							country_id: $el.data('country-id') || 0
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
					noResults: function () { return c.noResults || 'No results found.'; },
					searching: function () { return c.searching || 'Searching…'; },
					inputTooShort: function () { return c.inputTooShort || 'Please enter 2 or more characters'; }
				}
			});
		});
	}

	$(function () {
		initStaffUserSelect2();
	});
})(jQuery);
