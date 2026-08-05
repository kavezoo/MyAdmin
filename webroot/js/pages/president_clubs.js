/**
 * President clubs — national association fee SWAL confirm.
 */
(function ($) {
	'use strict';

	var App = window.MyAdmin || {};

	function submitFeeForm(formId) {
		var $form = $('#' + formId);
		if ($form.length) {
			$form.trigger('submit');
		}
	}

	$(function () {
		var cfg = window.PresidentClubs || {};

		$(document).on('click', '.js-record-club-national-fee', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var clubName = $btn.data('memberName') || '';
			var title = cfg.recordTitle || 'Record club annual membership fee?';
			var text = clubName
				? (cfg.recordTextNamed || 'Do you confirm that {0} has paid the annual national membership fee for this year? The payment date will be set to today.')
				: (cfg.recordText || 'Do you confirm that this club has paid the annual national membership fee for this year? The payment date will be set to today.');

			if (clubName && text.indexOf('{0}') !== -1) {
				text = text.replace('{0}', clubName);
			}

			if (!App.swal) {
				submitFeeForm($btn.data('formId'));
				return;
			}

			App.swal({
				icon: 'question',
				title: title,
				text: text,
				showCancelButton: true,
				focusCancel: true,
				confirmButtonText: cfg.recordConfirm || 'Yes, record payment',
				cancelButtonText: cfg.recordCancel || App.messages.cancelButton || 'Cancel',
				confirmButtonColor: '#198754',
				cancelButtonColor: '#6c757d',
				reverseButtons: true
			}).then(function (result) {
				if (result.isConfirmed) {
					submitFeeForm($btn.data('formId'));
				}
			});
		});
	});
})(jQuery);
