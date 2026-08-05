/**
 * Club president members — club fee SWAL + enable/disable AJAX + SWAL.
 *
 * Disable confirm: warning; Enable confirm: success.
 */
(function ($) {
	'use strict';

	var App = window.MyAdmin || {};

	function csrfToken() {
		return $('meta[name="csrfToken"]').attr('content') || '';
	}

	function submitFeeForm(formId) {
		var $form = $('#' + formId);
		if ($form.length) {
			$form.trigger('submit');
		}
	}

	function replaceNamed(template, name) {
		var text = String(template || '');
		if (name && text.indexOf('{0}') !== -1) {
			return text.replace('{0}', name);
		}
		return text;
	}

	function updateEnabledUi($btn, enabled) {
		var $row = $btn.closest('tr');
		var $cell = $row.find('.js-member-enabled-cell');
		var name = $btn.data('name') || '';
		var id = $btn.data('id');
		var url = $btn.data('url');
		var cfg = window.ClubpresidentMembers || {};

		if ($cell.length) {
			$cell.html(enabled
				? '<i class="fa fa-check text-success"></i>'
				: '<i class="fa fa-times text-danger"></i>');
		}
		$row.toggleClass('table-secondary', !enabled);

		var $newBtn;
		if (enabled) {
			$newBtn = $('<button type="button" class="btn btn-outline-warning js-member-toggle-enabled">'
				+ '<i class="fa fa-ban"></i></button>');
			$newBtn.attr({
				'data-id': id,
				'data-enabled': '1',
				'data-name': name,
				'data-url': url,
				'data-bs-toggle': 'tooltip',
				'data-bs-placement': 'top',
				'data-bs-html': 'true',
				title: '<b>' + (cfg.disableLabel || 'Disable') + '</b>'
			});
		} else {
			$newBtn = $('<button type="button" class="btn btn-outline-success js-member-toggle-enabled">'
				+ '<i class="fa fa-check"></i></button>');
			$newBtn.attr({
				'data-id': id,
				'data-enabled': '0',
				'data-name': name,
				'data-url': url,
				'data-bs-toggle': 'tooltip',
				'data-bs-placement': 'top',
				'data-bs-html': 'true',
				title: '<b>' + (cfg.enableLabel || 'Enable') + '</b>'
			});
		}
		$btn.replaceWith($newBtn);
		if (window.bootstrap && bootstrap.Tooltip) {
			document.querySelectorAll('.js-member-toggle-enabled[data-bs-toggle="tooltip"]').forEach(function (el) {
				bootstrap.Tooltip.getOrCreateInstance(el);
			});
		}
	}

	function postToggle($btn, enabledTarget) {
		var url = $btn.data('url');
		if (!url) {
			return;
		}
		$btn.prop('disabled', true);
		$.ajax({
			url: url,
			method: 'POST',
			dataType: 'json',
			headers: {
				'X-CSRF-Token': csrfToken(),
				'Accept': 'application/json'
			},
			data: {
				enabled: enabledTarget ? '1' : '0',
				_csrfToken: csrfToken()
			}
		}).done(function (res) {
			if (res && res.ok) {
				updateEnabledUi($btn, !!res.enabled);
				if (res.message && App.flashSwal) {
					App.flashSwal({
						icon: 'success',
						title: App.messages && App.messages.successTitle ? App.messages.successTitle : 'Success',
						text: res.message
					});
				}
			} else {
				$btn.prop('disabled', false);
				var msg = (res && res.message) || (window.ClubpresidentMembers || {}).toggleError || 'Error';
				if (App.alertError) {
					App.alertError(msg);
				} else {
					window.alert(msg);
				}
			}
		}).fail(function () {
			$btn.prop('disabled', false);
			var msg = (window.ClubpresidentMembers || {}).toggleError || 'Error';
			if (App.alertError) {
				App.alertError(msg);
			} else {
				window.alert(msg);
			}
		});
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

		$(document).on('click', '.js-member-toggle-enabled', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var currentlyEnabled = String($btn.data('enabled')) === '1';
			var name = $btn.data('name') || '';
			var enable = !currentlyEnabled;

			var title = enable
				? (cfg.enableTitle || 'Enable member?')
				: (cfg.disableTitle || 'Disable member?');
			var text = enable
				? replaceNamed(cfg.enableText || 'Do you really want to enable {0}?', name)
				: replaceNamed(cfg.disableText || 'Do you really want to disable {0}?', name);
			var confirmText = enable
				? (cfg.enableConfirm || 'Yes, enable')
				: (cfg.disableConfirm || 'Yes, disable');
			var confirmColor = enable ? '#198754' : '#dc3545';
			// Enable = success SWAL; Disable = warning SWAL
			var icon = enable ? 'success' : 'warning';

			var run = function () {
				postToggle($btn, enable);
			};

			if (!App.swal) {
				run();
				return;
			}

			App.swal({
				icon: icon,
				title: title,
				text: text,
				showCancelButton: true,
				focusCancel: true,
				confirmButtonText: confirmText,
				cancelButtonText: cfg.recordCancel || App.messages.cancelButton || 'Cancel',
				confirmButtonColor: confirmColor,
				cancelButtonColor: '#6c757d',
				reverseButtons: true
			}).then(function (result) {
				if (result.isConfirmed) {
					run();
				}
			});
		});
	});
})(jQuery);
