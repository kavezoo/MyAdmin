/**
 * Club president applicants — SweetAlert before approve / reject POST.
 */
(function ($) {
	'use strict';

	var App = window.MyAdmin || {};

	function submitActionForm(formId) {
		var $form = $('#' + formId);
		if ($form.length) {
			$form.trigger('submit');
		}
	}

	function confirmApplicantAction($btn, options) {
		if (!App.swal) {
			submitActionForm($btn.data('formId'));
			return;
		}

		App.swal({
			icon: options.icon || 'warning',
			title: options.title,
			text: options.text,
			showCancelButton: true,
			focusCancel: true,
			confirmButtonText: options.confirmText,
			cancelButtonText: options.cancelText || App.messages.cancelButton || 'Cancel',
			confirmButtonColor: options.confirmColor || '#dc3545',
			cancelButtonColor: '#6c757d',
			reverseButtons: true
		}).then(function (result) {
			if (result.isConfirmed) {
				submitActionForm($btn.data('formId'));
			}
		});
	}

	$(function () {
		var cfg = window.ClubpresidentApplicants || {};

		$(document).on('click', '.js-applicant-approve', function (e) {
			e.preventDefault();
			var $btn = $(this);
			confirmApplicantAction($btn, {
				icon: 'question',
				title: cfg.approveTitle || 'Approve membership?',
				text: cfg.approveText || 'Do you really want to approve this applicant as a full member?',
				confirmText: cfg.approveConfirm || 'Yes, approve',
				confirmColor: '#198754'
			});
		});

		$(document).on('click', '.js-applicant-reject', function (e) {
			e.preventDefault();
			var $btn = $(this);
			confirmApplicantAction($btn, {
				icon: 'warning',
				title: cfg.rejectTitle || 'Reject application?',
				text: cfg.rejectText || 'Do you really want to reject this application? The user will be disabled and cannot log in.',
				confirmText: cfg.rejectConfirm || 'Yes, reject',
				confirmColor: '#dc3545'
			});
		});
	});
})(jQuery);
