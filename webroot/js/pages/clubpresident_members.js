/**
 * Club president members — record club fee payment (SWAL → today).
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
		var cfg = window.ClubpresidentMembers || {};

		$(document).on('click', '.js-record-club-fee', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var memberName = $btn.data('memberName') || '';
			var title = cfg.recordTitle || 'Record membership fee payment?';
			var text = memberName
				? (cfg.recordTextNamed || 'Do you confirm that {0} has paid the club membership fee for this year? The payment date will be set to today.')
				: (cfg.recordText || 'Do you confirm that this member has paid the club membership fee for this year? The payment date will be set to today.');

			if (memberName && text.indexOf('{0}') !== -1) {
				text = text.replace('{0}', memberName);
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
